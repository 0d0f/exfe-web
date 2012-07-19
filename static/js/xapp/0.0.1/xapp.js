/**
 * X webapp Bootstrap!
 */
// test
define(function (require, exports, module) {

  var Bus = require('bus');
  var Store = require('store');
  var Config = require('config');
  var odof = require('odof');
  var middleware = require('middleware');
  var Handlebars = require('handlebars');

  var lightsaber = require('lightsaber');

  //console.log('lightsaber.version', lightsaber.version);

  // Create App
  var app = lightsaber();

  app.use(middleware.login);

  app.configure(function () {
    app.set('timestamp', Config.timestamp);
    app.set('view engine', Handlebars);
    app.set('views', '/static/views');
    //console.log(app.set('views'));
  });

  app.configure('development', function () {
  });


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

  /**
   * Home
   */
  app.get('/', function (req, res, next) {
    //console.log('home');
    if (res.loginable) {
      //Bus.emit('xapp:home_profile');
      return;
    }

    $('#js-signin').show();

    $(document).find('head').eq(0).append('<link rel="stylesheet" type="text/css" href="/static/_css/home.css?t=' + Config.timestamp + '" />');

    res.render('index.html', function (tpl) {
      $('.container > div[role="main"]').html('');
      $('#home').append(tpl);
    });

  });


  /**
   * Cross
   * route middleware
   * param cross id
   */
  app.param('crossId', function (req, res, next, id) {
    //console.log('cross param corssId', id.toString());
    if (id !== '0') {
      next();
    } else {
      next(new Error('crossId fail'));
    }
  });

  app.get('/#!:crossId', function (req, res, next) {
    var cross_id = req.params.crossId;
    $('.container > div[role="main"]').html('');
    $('#home').html('');

    res.render('x.html', function (tpl) {
      $('.container > div[role="main"]').append(tpl);
      Bus.emit('xapp:cross:main');
      Bus.emit('xapp:cross', +cross_id);
    });

  });


  /**
   * Gather a X
   */
  app.get('/#gather', function (req, res, next) {
    $('.container > div[role="main"]').html('');
    $('#home').html('');

    res.render('x.html', function(tpl) {
      $('.container > div[role="main"]').append(tpl);
      Bus.emit('xapp:cross:main');
      Bus.emit('xapp:cross', 0);
      next();
    });
  });

  /*
   * Profile
   */
  app.get(/^\/#([^@\/\s\!]+)@([^@\/\s\.]+)/
    , function (req, res, next) {
      $('#home').html('');

      res.render('profile.html', function (tpl) {
        $('.container > div[role="main"]').empty().append(tpl);
        next();
      });
    }
   , function (req, res, next) {
      var user = Store.get('user');
      Bus.emit('app:signinsuccess', user);
      var dfd = $.Deferred();
      var signin = Store.get('signin');
      dfd.resolve(signin)
      Bus.emit('app:signinothers', dfd);
   });


  // startup app
  app.run();

  // NOTE: DOM EVENT 暂时放这里
  //$(function () {
    $(document.body).on('click.xapp', '[href^="/#"]', function (e) {
      // NOTE: 暂时放这里
      Bus.emit('xapp:cross:end');

      var $e = $(e.currentTarget);
      var path = $e.attr('href');

      //alert(path);

      if (path === '/#gather') {
        app.response.redirect(path, 'Gather a X', {id: 'gather' + 0});
      }
      else if (2 === path.indexOf('!')) {
        app.response.redirect(path, 'Cross', {id: 'cross' + path.substr(2)});
      } else {
        var last_external_id = Store.get('last_external_id');
        //alert(last_external_id);
        app.response.redirect('/#' + last_external_id, 'Profile', {id: 'profile'});
      }
      e.stopPropagation();
      e.preventDefault();
    });

  //});
});
