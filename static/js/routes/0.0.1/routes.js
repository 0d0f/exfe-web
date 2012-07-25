/**
 *
 */
define('routes', function (require, exports, module) {
  var Api = require('api')
    , Bus = require('bus')
    , Store = require('store');

  // user modle
  require('user');

  var routes = module.exports = {};

  // switch page DOM
  routes.switchPage = function (req, res, next) {
    // DOM
    var $body = $(document.body)
      , $navbar = $('#js-navbar');

    $('#js-main > div[role="main"]').empty();

    if (!req.session.token && (req.url === '/' || req.url === '/#')) {
      $body.addClass('hbg');
      $navbar.addClass('hide');
    }
    else {
      $body.removeClass('hbg');
      $navbar.removeClass('hide');
    }

    next();
  };

  // signin
  routes.signin = function (req, res, next) {
    //console.log(req.session.token, req.session.user_id);

    var session = req.session;

    // no token
    if (!session.token) {
      var $dw = $('#js-signin')
        .show()
        .next()
        .hide();

      $dw.find('.user-name > span')
        .removeClass('browsing-identity')
        .text('');

      $dw.find('.user-panel').remove();

      // profile
      if (req.url !== '/#gather') {
        res.redirect('/');
        return;
      }

    } else {
      if (!session.unRegenerate) {
        Bus.emit('xapp:usertoken', session.token, session.user_id, 2);
      }
      if (session.unRegenerate) {
        session.unRegenerate = false;
      }
    }

    next();
  };

  // index
  routes.index = function (req, res, next) {
    if (req.session.token) {
      redirectProfile(res);
      return;
    }

    var $main = $('#js-main > div[role="main"]');

    res.render('index.html', function (tpl) {
      $main.append(tpl);
      var $page_main = $main.find('div.page-main');
      var $img = $('<img class="exfe-toy" id="js-exfe-toy" src="/static/img/exfe-toy.png" alt="" />');
      $img.load(function () {
        $page_main
          .append($img)
          .find('.circle')
          .removeClass('hide');
      });
    });
  };

  // ghater a x
  routes.gather = function (req, res, next) {
    res.render('x.html', function(tpl) {
      $('#js-main > div[role="main"]').append(tpl);
      Bus.emit('xapp:cross:main');
      Bus.emit('xapp:cross', 0);
      next();
    });
  };

  // profile
  routes.profile = function (req, res, next) {
    document.title = 'Profile';
    res.render('profile.html', function (tpl) {
      $('#js-main > div[role="main"]').append(tpl);

      // TODO: opt
      var user = Store.get('user');
      Bus.emit('app:signinsuccess', user);
      var dfd = $.Deferred();
      var signin = Store.get('signin');
      dfd.resolve(signin)
      Bus.emit('app:signinothers', dfd);
    });
  };

  // cross
  // cross token
  routes.crossTokenParam = function (req, res, next, token) {
    if (token.match(/^[a-zA-Z0-9]{32}$/)) {
      var userToken = req.session.token
        , params = {};

      if (!userToken) {
        var signin = Store.get('signin')
        signin && (userToken = signin.token);
      }

      if (userToken) {
        params.token = userToken;
      }

      Api.request('getCrossByInvitationToken'
        , {
          type: 'POST',
          params: params,
          data: {
            invitation_token: token
          }
        }
        , function (data) {
          var signin = data.signin
            , browsing_identity = data.browsing_identity
            , action = data.action
            , cross = data.cross
            , read_only = data.read_only;

          if (signin) {
            Store.set('signin', signin);
            Bus.emit('xapp:usertoken', signin.token, signin.user_id, 2);
            req.session.token = signin.token;
            req.session.user_id = signin.user_id;
          }
          else if (browsing_identity) {
            if (action === 'setup') {
              browsing_identity.isSetup = true;
            }
            browsing_identity.isNotLogin = !req.session.token;
            Bus.emit('xapp:crosstoken', browsing_identity);
          }

          req.session.tmpData = data;
          req.session.unRegenerate = true;

          next();
        }
      );

      //next();
    }
    else {
      res.redirect('/');
      //redirectProfile(res);
      //next(new Error('invalid token'));
    }
  };
  routes.crossToken = function (req, res, next) {
    var data = req.session.tmpData
      , cross = data.cross
      , browsingIdentity = data.browsing_identity
      , read_only = data.read_only;

    delete req.session.tmpData;;

    res.render('x.html', function (tpl) {
      $('#js-main > div[role="main"]').append(tpl);
      Bus.emit('xapp:cross:main');
      Bus.emit('xapp:cross', null, browsingIdentity, cross, read_only);
    });
  };

  // normal cross
  routes.crossParam = function (req, res, next, cross_id) {
    if (!(/^0*$/.test(cross_id)) && /^[0-9]+$/.test(cross_id)) {
      req.params.cross_id = +cross_id;
      next();
    } else {
      redirectProfile(res);
      //next(new Error('invalid cross_id'));
    }
  };
  routes.cross = function (req, res, next) {
    var cross_id = req.params.cross_id;
    res.render('x.html', function (tpl) {
      $('#js-main > div[role="main"]').append(tpl);
      Bus.emit('xapp:cross:main');
      Bus.emit('xapp:cross', cross_id);
    });
  };


  // Helpers:
  // ----------------------
  function redirectProfile(res) {
    var external_id = Store.get('user').default_identity.external_id;
    res.redirect('/#' + external_id);
  }

});
