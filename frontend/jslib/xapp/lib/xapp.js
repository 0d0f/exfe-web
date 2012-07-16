/**
 * X webapp Bootstrap!
 */
define(function (require, exports, module) {
  var Bus = require('bus');
  var Store = require('store');
  var Config = require('config');
  var odof = require('odof');
  var middleware = require('middleware');

  // create an odof application
  var app = odof()
    // login
    /*.use(function (req, res, next) {
      console.log('middle 0');
      console.dir(req);
      next();
    });
    */
   .use(middleware.login);

  var hi = 0;
  Bus.on('xapp:goto_home', function (next) {
    //console.log('fire goto home');
    //console.log('fire goto home', next);
    if (0 === hi++) {
      //alert('goto_home');
      app.response.redirect('/', 'EXFE.COM', {id: 'home'});
      next && next();
      hi = 0;
    }
  });

  var pi = 0;
  var XAPP_GOTO_PROFILE = 'xapp:goto_profile';
  Bus.on(XAPP_GOTO_PROFILE, function () {
      if (app.request.url !== '/') return;
      var last_external_id = Store.get('last_external_id');
      app.response.redirect('/#' + last_external_id, 'Profile', {id: 'profile'});
  });

  Bus.on('xapp:home_profile', function () {
    app.response.loginable = true;
    Bus.emit(XAPP_GOTO_PROFILE);
  });

  // home
  app.get('/'
    , function (req, res, next) {
      //console.log('loginable', res.loginable);
      // 已经登录
      if (res.loginable) {
        //Bus.emit('xapp:home_profile');
        return;
      }

      $('#js-signin').show();
      $(document).find('head').eq(0).append('<link rel="stylesheet" type="text/css" href="/static/_css/home.css?t=' + Config.timestamp + '" />');
      $.ajax({
        url: '/static/views/index.html',
        success: function (data) {
          $('.container > div[role="main"]').html('');
          $('#home').append(data);
        }
      });
    });

  // cross
  app.get('/#!:id', function (req, res, next) {
    var cross_id = req.params.id;
    //console.log('cross', 0);
    $('.container > div[role="main"]').html('');
    $('#home').html('');
    $.ajax({
      url: '/static/views/x.html?123',
      success: function (data) {
        $('.container > div[role="main"]').append(data);
        Bus.emit('xapp:cross', cross_id);
        next();
      }
    });
  });
  app.get(/^\/#!([\w\d]+)\/(@?[\w\d]+)/, function (req, res, next) {
    //console.log('cross', 1);
  });

  // profile
  app.get(/^\/#([^@\/\s\!]+)@([^@\/\s\.]+)/
    , function (req, res, next) {
      $('#home').html('');
      $.ajax({
        url: '/static/views/profile.html?123',
        success: function (data) {
          $('.container > div[role="main"]').empty().append(data);
          next();
        }
      });
    }
    , function (req, res, next) {
      var user = Store.get('user');
      Bus.emit('app:signinsuccess', user);
      var dfd = $.Deferred();
      var signin = Store.get('signin');
      dfd.resolve(signin)
      Bus.emit('app:signinothers', dfd);
    }
  );

  app.run();

  $(function () {
    $(document).on('click', '[href^="/#"]', function (e) {
      var $e = $(e.currentTarget);
      var path = $e.attr('href');
      alert(path);
      if (2 === path.indexOf('!')) {
        app.response.redirect(path, 'Cross', {id: 'cross'});
      } else {
        var last_external_id = Store.get('last_external_id');
        app.response.redirect('/#' + last_external_id, 'Profile', {id: 'profile'});
      }
    });
  });

  //console.dir(app);
});
