/**
 * Routes
 */
define('routes', function (require, exports, module) {
  var Api = require('api');
  var Bus = require('bus');
  var Util = require('util');
  var Store = require('store');

  require('user');

  var routes = module.exports = {};


  // index
  routes.index = function (req, res, next) {

    // redirect to `profile`
    if (req.session.authorization) {
      redirectToProfile(req, res);
      return;
    }

    // is `home`
    Bus.emit('app:page:home', true);

    res.render('index.html', function (tpl) {
      var $appMain = $('#app-main');
      $appMain.append(tpl);
      var $pageMain = $appMain.find('div.page-main');
      var $img = $('<img class="exfe-toy" id="js-exfe-toy" src="/static/img/exfe.png" alt="" />');
      $pageMain
        .append($img);
      $img.load(function () {

        $.ajax({ dataType: 'script', cache: true,
          url: "/static/js/home/0.0.1/home.js?t=" + req.app.set('timestamp')
        });
      });

    });
  };


  // gather a x
  routes.gather = function (req, res, next) {
    var session = req.session;

    // is not `home` page
    Bus.emit('app:page:home', false);

    if (!session.initMenuBar) {

      var authorization = session.authorization
        , user = session.user;

      Bus.emit('app:page:usermenu', !!authorization);

        if (authorization) {
          session.initMenuBar = true;
          Bus.emit('app:usermenu:updatenormal', user);

          Bus.emit('app:usermenu:crosslist'
            , authorization.token
            , authorization.user_id
          );
        }
    }

    res.render('x.html', function(tpl) {
      $('#app-main').append(tpl);
      Bus.emit('xapp:cross:main');
      Bus.emit('xapp:cross', 0);
    });
  };


  // resolve token
  routes.resolveToken = function (req, res, next) {
    req.origin = 'resolveToken';
    var token = req.params[0];

    if (token) {
      var session = req.session
        , authorization = session.authorization
        , browsing_authorization
        , action;

      Api.request('resolveToken',
        {
          type: 'POST',
          data: { token: token }
        }
        // data:
        //    user_id
        //    token
        //    action
        , function (data) {

          action = session.action = data.action;
          delete data.action;

          if (authorization) {
            session.browsing_authorization = browsing_authorization = data;
          }
          else {
            // 正常登录
            if (action === 'VERIFY') {
              Store.set('authorization', session.authorization = data);
            }
            else if (action === 'INPUT_NEW_PASSWORD') {
              session.browsing_authorization = browsing_authorization = data;
            }
          }

          // 如果 browsing 与 之前登录过的身份相等，则清除 browsing
          if (authorization
              && browsing_authorization
              && authorization.token === browsing_authorization.token
              && authorization.user_id === browsing_authorization.user_id) {

            browsing_authorization = null;
            delete session.browsing_authorization;
          }

          Bus.emit('app:api:getuser'
            , data.token
            , data.user_id
            , function done(data) {
                var user = data.user
                  // external_username
                  , eun = Util.printExtUserName(user.default_identity);

                if (browsing_authorization) {
                  session.browsing_user = user;
                  eun += '/token=' + token;
                }

                res.redirect('/#' + eun);
              }
            //, function fail(err) {}
          );
        }
        , function (data) {
          res.redirect('/#invalid/token=' + token);
        }
      );

    }
    else {
      res.redirect('/#invalid');
    }
  };


  // cross
  routes.cross = function (req, res, next) {
    var session = req.session
      , authorization = session.authorization
      , user = session.user;

    if (!authorization) {
      res.redirect('/');
    }

    Bus.emit('app:page:home', false);

    Bus.emit('app:page:usermenu', true);

    if (!session.initMenuBar) {
      //session.initMenuBar = true;
      Bus.emit('app:usermenu:updatenormal', user);

      Bus.emit('app:usermenu:crosslist'
        , authorization.token
        , authorization.user_id
      );
    }

    var cross_id = req.params[0];
    res.render('x.html', function (tpl) {
      $('#app-main').append(tpl);
      Bus.emit('xapp:cross:main');
      Bus.emit('xapp:cross', cross_id);
    });
  };


  // cross-token
  routes.crossToken = function (req, res, next) {
    var session = req.session
      , authorization = session.authorization
      , user = session.user
      , authToken = authorization.token
      , ctoken = req.params[0]
      , params = {};

    if (authToken) {
      params.token = authToken;
    }

    Api.request('getCrossByInvitationToken',
      {
        type: 'POST',
        params: params,
        data: { invitation_token: ctoken }
      }
      , function (data) {
        var auth = data.signin
          , browsing_identity = data.browsing_identity
          , action = data.action
          , cross = data.cross
          , read_only = data.read_only;

        Bus.emit('app:page:home', false);

        Bus.emit('app:page:usermenu', true);

        if (auth) {

          if (!session.initMenuBar) {
            //session.initMenuBar = true;
            Bus.emit('app:usermenu:updatenormal', user);

            Bus.emit('app:usermenu:crosslist'
              , auth.token
              , auth.user_id
            );
          }
        }
        else if (browsing_identity) {
          Bus.emit('app:usermenu:updatebrowsing',
            {   normal: user
              , browsing: { default_identity: browsing_identity, name: browsing_identity.name }
              , action: action
              , setup: action === 'setup'
            }
            , 'browsing_identity');
        }

        res.render('x.html', function (tpl) {
          $('#app-main').append(tpl);
          Bus.emit('xapp:cross:main');
          Bus.emit('xapp:cross', null, browsing_identity, cross, read_only, ctoken);
        });
      }
    );

  };


  // profile
  routes.profile = function (req, res, next) {
    var session = req.session
      , authorization = session.authorization
      , user = session.user
      , browsing_authorization = session.browsing_authorization
      , browsing_user = session.browsing_user
      , action = session.action;

    Bus.emit('app:page:home', false);

    // 先检查 token
    var param = req.params[2]
      , match = param && param.match(Util.tokenRegExp)
      , token = match && match[1];

    // 跳转倒 `resolveToken`, 解析 `token`，解析成功跳回来
    if (token && !browsing_authorization) {
      res.redirect('/#token=' + token);
      return;
    }

    if (authorization || browsing_authorization) {

      document.title = 'Profile';

      Bus.emit('app:page:usermenu', true);

      // 正常登录
      if (authorization && !browsing_authorization) {

        if (!session.initMenuBar) {
          //session.initMenuBar = true;
          Bus.emit('app:usermenu:updatenormal', user);

          Bus.emit('app:usermenu:crosslist'
            , authorization.token
            , authorization.user_id
          );
        }

        res.render('profile.html', function (tpl) {
          $('#app-main').append(tpl);
          Bus.emit('app:profile:identities', user);
          var dfd = $.Deferred();
          dfd.resolve(authorization);
          Bus.emit('app:profile:show', dfd);
        });
      }

      // `browser identity` 浏览身份登录
      else if (browsing_authorization) {

        Bus.emit('app:usermenu:updatebrowsing',
          {   normal: user
            , browsing: browsing_user
            , action: action
            , setup: action === 'setup'
          }
          , 'browsing_identity');

        res.render('profile.html', function (tpl) {
          $('#app-main').append(tpl);
          Bus.emit('app:profile:identities', browsing_user);
          var dfd = $.Deferred();
          dfd.resolve(browsing_authorization);
          Bus.emit('app:profile:show', dfd);
        });
      }

      else {
        // 跳回首页
        res.redirect('/');
      }

    }
  };


  // invalid
  routes.invalid = function (req, res, next) {
    document.title = 'Invalid Link'

    Bus.emit('app:page:home', false);

    Bus.emit('app:page:usermenu', false);

    res.render('invalid.html', function (tpl) {
      $('#app-main').append(tpl);
    });
  };



  // Helpers:
  // ----------------------------
  function redirectToProfile(req, res) {
    var user = Store.get('user')
      , external_username = Util.printExtUserName(user.default_identity);

    res.redirect('/#' + external_username);
  }

});
