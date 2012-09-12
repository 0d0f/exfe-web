/**
 * Routes
 */
define('routes', function (require, exports, module) {
  var R = require('rex');
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
    var originToken = req.params[0];

    Bus.emit('app:page:home', false);
    Bus.emit('app:page:usermenu', true);

    if (originToken) {
      next();
    }
    else {
      res.redirect('/#invalid/token=' + originToken);
    }
  };

  routes.resolveRequest = function (req, res, next) {
    var session = req.session
      , originToken = req.params[0]
      , authorization = session.authorization
      , user = session.user
      , browsing_authorization
      , token_type
      , action
      , tab = 0
      , user_name;

    var dfd = Api.request('resolveToken',
        { type: 'POST', data: { token: originToken } }
        , function (data) {
          // token_type
          //  verify
          //  forgot password: SET_PASSWORD
          token_type = data.token_type;
          action = data.action;
          user_name = data.user_name;
          session.browsing_authorization = browsing_authorization = data;
          session.originToken = originToken;

          // 如果 token 进来的身份和之前已经登录过的 user 相等，则正常登录 2333ms 跳到 profile
          //res.redirect('/#' + Util.printExtUserName(user.default_identity));
          if (authorization
              && authorization.token === data.token
              && authorization.user_id === data.user_id) {
          // 如果之前没有 user 登录，及 token_type 为 `VERIFIED`, 则正常登录, 2333ms 跳到 profile
            tab = 0;
            delete session.browsing_authorization;
            delete session.originToken;
          }
          else if (!authorization
                && token_type === 'VERIFY'
                && action === 'VERIFIED') {

            Store.set('authorization', authorization = session.authorization = session.browsing_authorization);
            delete session.browsing_authorization;
            delete session.originToken;

            tab = 1;
          }
          else if (authorization && token_type === 'VERIFY' && action === 'VERIFIED') {

            tab = 2;
          }
          else if (!authorization && token_type === 'VERIFY' && action === 'INPUT_NEW_PASSWORD') {

            tab = 3;
          }
          else if (authorization && token_type === 'VERIFY' && action === 'INPUT_NEW_PASSWORD') {

            tab = 4;
          }
          else if (!authorization && token_type === 'SET_PASSWORD') {

            tab = 5;
          }
          else if (authorization && token_type === 'SET_PASSWORD') {

            tab = 6;
          }
          else {

            tab = 7;
          }

          session.resolveTab = tab;

          Bus.emit('app:api:getuser'
            , data.token
            , data.user_id
            , function done(data2) {
              if (tab === 0 || tab === 1) {
                Store.set('user', user = session.user = data2.user);
                Bus.emit('app:usermenu:updatenormal', user);
                Bus.emit('app:usermenu:crosslist'
                  , authorization.token
                  , authorization.user_id
                );
              } else {
                session.browsing_user = data2.user;
                var eun = Util.printExtUserName(data2.user.default_identity);
                var forwardUrl = '';
                if (authorization) {
                  forwardUrl = '/#' + eun + '/token=' + originToken;
                }
                else {
                  forwardUrl = '/#' + eun;
                }
                Bus.emit('app:usermenu:updatebrowsing',
                  {   normal: user
                    , browsing: data2.user
                    , action: action
                    , setup: action === 'INPUT_NEW_PASSWORD'
                    , originToken: originToken
                    , tokenType: 'user'
                    , page: 'resolve'
                    , readOnly: true
                    , user_name: user_name
                    , forward: forwardUrl
                  }
                  , 'browsing_identity');
              }
              next();
            }
          );
        }
        , function () {
          res.redirect('/#invalid/token=' + originToken);
        }
      );
  };

  routes.resolveShow = function (req, res, next) {
    var session = req.session
      , tab = session.resolveTab
      , tpl_url;

    switch (tab) {
      case 0:
      case 1:
      case 2:
      case 3:
      case 4:
        tpl_url = 'identity_verified.html';
        res.render(tpl_url, function (tpl) {
          var $main = $('#app-main');
          $main.append(tpl);
          if (tab === 0 || tab === 1) {
            $main.find('.tab01').removeClass('hide');
            $main.find('.tab01 > p').animate({opacity: 0}, 2333, function () {
              res.redirect('/');
            });
          } else if (tab === 2) {
            $('#app-browsing-identity').trigger('click.data-api');
            $('.modal-bi').css('top', 200);
          } else if (tab === 3) {
            $('#app-user-menu').find('.set-up').trigger('click.dialog.data-api');
            $('.modal-su').css('top', 200);
          } else {
            $('#app-user-menu').find('.set-up').trigger('click.dialog.data-api');
            $('.modal-su').css('top', 200);
          }
        });
        break;
      case 5:
      case 6:
        tpl_url = 'forgot_password.html';
        res.render(tpl_url, function (tpl) {
          var $main = $('#app-main');
          $main.append(tpl);
          $('#app-user-menu').find('.set-up').trigger('click.dialog.data-api');
          $('.modal-su').css('top', 230);
        });
        break;
      default:
        break;
    }
  };


  // cross
  routes.cross = function (req, res, next) {
    var session = req.session
      , authorization = session.authorization
      , user = session.user;

    if (!authorization) {
      //res.redirect('/');
      Bus.emit('app:page:home', false);
      Bus.emit('app:page:usermenu', false);
      Bus.emit('app:cross:forbidden', req.params[0], null);
      return;
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

  // cross forbidden
  // TODO: 整合 cross 逻辑
  Bus.on('app:cross:forbidden', function (cross_id, data) {
    $('#app-main').load('/static/views/forbidden.html', function () {
      var authorization = Store.get('authorization');
      var settings = {
        options: {
          keyboard: false,
          backdrop: false,

          viewData: {
            // class
            cls: 'modal-id'
          }
        }
      };
      if (data) {
        $('.sign-in').data('source', data.external_username);
      }
      $('.sign-in').data('dialog-settings', settings);
      $('.sign-in').trigger('click.dialog.data-api');
      $('.sign-in').data('dialog-settings', null);
      $('.popmenu').addClass('hide');
      if (!authorization) {
        $('.please-signin').removeClass('hide');
        $('.modal-id').css('top', 260);
      }
      else {
        var user = Store.get('user');
        $('.details').removeClass('hide');
        $('.details .avatar img').attr('src', user.avatar_filename);
        $('.details .identity-name').text(user.name);
        $('.please-access').removeClass('hide');
        $('.modal-id').css({
          top: 380
        });
      }
    });
  });


  // Opening a private invitation X.
  routes.crossInvitation = function (req, res, next) {
    var session = req.session
      , authorization = session.authorization
      , user = session.user
      , user_id = user && user.id
      , cross_id = req.params[0]
      // invitation token
      , shortToken = req.params[1];

    Bus.emit('app:page:home', false);

    Bus.emit('app:page:usermenu', !!authorization);

    if (authorization) {
      //session.initMenuBar = true;
      Bus.emit('app:usermenu:updatenormal', user);

      Bus.emit('app:usermenu:crosslist'
        , authorization.token
        , authorization.user_id
      );
    }

    Api.request('getInvitationByToken',
      {
        type: 'POST',
        resources: { cross_id: cross_id },
        data: { token: shortToken }
      }
      , function (data) {
        var invitation = data.invitation
          , identity = invitation.identity
          , by_identity = invitation.by_identity
          , _identity;

        if (user_id) {
          _identity = R.filter(user.identities, function (v) {
            if (v.connected_user_id === identity.connected_user_id
                && v.id === identity.id) {
              return true;
            }
          });

          if (_identity.length) {
            res.redirect('/#!' + cross_id);
            return;
          }
        }

        if (identity.provider === 'email') {
          Bus.emit('app:cross:forbidden', cross_id, identity);
          return;
        }

        res.render('invite.html', function (tpl) {
          $('#app-main').append(tpl);

          if (authorization) {
            $('.please-access').removeClass('hide');
            $('.form-horizontal').addClass('fh-left');
            $('.details').removeClass('hide');
            $('.details .avatar img').attr('src', user.avatar_filename);
            $('.details .identity-name').text(user.name);
          }
          else {
            $('.please-signin').removeClass('hide');
          }

          $('.invite-to')
            .find('img')
            .attr('src', identity.avatar_filename)
            .parent()
            .next()
            .text(Util.printExtUserName(identity));

          $('.invite-from')
            .find('img')
            .attr('src', by_identity.avatar_filename)
              .parent()
            .next()
            .text(Util.printExtUserName(by_identity));

          var $redirecting = $('.x-invite').find('.redirecting')
            , $fail = $redirecting.next();

          var clicked = false;
          $('.xbtn-authenticate').on('click', function (e) {
            e.stopPropagation();
            e.preventDefault();
            if (clicked) {
              return;
            }
            $.ajax({
              url: '/OAuth/twitterAuthenticate',
              type: 'POST',
              dataType: 'JSON',
              data: {
                callback: window.location.href
              },
              beforeSend: function (xhr) {
                clicked = true;
                $fail.addClass('hide');
                $redirecting.removeClass('hide');
              },
              success: function (data) {
                clicked = false;
                var code = data.meta.code;
                if (code === 200) {
                  window.location.href = data.response.redirect;
                } else {
                  $redirecting.addClass('hide');
                  $fail.removeClass('hide');
                }
              }
            });
          });


          // v2 做
          /*
          if (authorization) {
            $('label[for="follow"]').removeClass('hide');
          }
          */
        });

      },

      function (data) {
        if (data.meta.code === 404) {
          res.location('/404');
        }
      }
    );
  };


  // cross-token
  routes.crossToken = function (req, res, next) {
    var session = req.session
      , authorization = session.authorization
      , user = session.user
      , authToken = authorization && authorization.token
      , ctoken = req.params[0]
      , accept = req.params[1]
      // cat = cross_access_token
      , cat
      , params = {}
      , data;

    if (authToken) {
      params.token = authToken;
    }

    cat = Store.get('cat:' + ctoken);

    if (cat) {
      data = { cross_access_token: cat };
    }
    else {
      data = { invitation_token: ctoken };
    }

    Api.request('getCrossByInvitationToken',
      {
        type: 'POST',
        params: params,
        data: data
      }
      , function (data) {
        var auth = data.authorization
          , browsing_identity = data.browsing_identity
          , action = data.action
          , cross = data.cross
          , cross_access_token = data.cross_access_token
          , read_only = data.read_only;

        if (false === read_only && cross_access_token) {
          Store.set('cat:' + ctoken, cat = cross_access_token);
        }

        //
        function render() {
          res.render('x.html', function (tpl) {
            $('#app-main').append(tpl);
            Bus.emit('xapp:cross:main');
            Bus.emit('xapp:cross', null, browsing_identity, cross, read_only, cat, !!accept);
          });
        }

        Bus.emit('app:page:home', false);

        Bus.emit('app:page:usermenu', true);

        if (auth || !browsing_identity) {

          if (!session.initMenuBar) {
            //session.initMenuBar = true;
            if (auth) {
              Bus.once('app:user:signin:after', function () {
                res.redirect('/#!' + cross.id);
                //render();
              });
              Bus.emit('app:user:signin', auth.token, auth.user_id);

              return;
            }
            else {
              // 没有 browsing-identity
              res.redirect('/#!' + cross.id);
            }

          }
        }
        else if (browsing_identity) {
          Bus.emit('app:usermenu:updatebrowsing',
            {   normal: user
              , browsing: { default_identity: browsing_identity, name: browsing_identity.name }
              , action: action
              , setup: action === 'setup'
              , originToken: ctoken
              , tokenType: 'cross'
              , page: 'cross'
              , readOnly: read_only
            }
            , 'browsing_identity');
        }

        render();
      }
      , function (data) {
        if (data.meta.code === 404) {
          res.location('/404');
        }
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
      , action = session.action
      , oauth = session.oauth;

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

      document.title = 'EXFE - Profile';

      Bus.emit('app:page:usermenu', true);

      // 正常登录
      if (authorization && !browsing_authorization) {

        Bus.emit('app:usermenu:updatenormal', user);

        Bus.emit('app:usermenu:crosslist'
          , authorization.token
          , authorization.user_id
        );

        res.render('profile.html', function (tpl) {
          $('#app-main').append(tpl);
          Bus.emit('app:profile:identities', user);
          var dfd = $.Deferred();
          dfd.resolve(authorization);
          Bus.emit('app:profile:show', dfd);

          // 弹出 OAuth Welcome
          // revoked, new 暂时都弹 welcome 窗口
          if (oauth && oauth.identity_status !== 'connected') {
            var identities = user.identities;
            var identity = R.filter(identities, function (v) {
              if (v.id === oauth.identity_id) {
                return true;
              }
            })[0];

            $('<div id="app-oauth-welcome" class="hide" data-widget="dialog" data-dialog-type="welcome" data-oauth-provider="' + oauth.provider + '"></div>')
            .appendTo(document.body)
              .trigger({
                type: 'click',
                following: oauth.following,
                identity: identity,
                token: authorization.token
              })
              .remove();
            delete session.oauth;
          }
        });
      }

      // `browser identity` 浏览身份登录
      else if (browsing_authorization) {

        $(document.body).attr('data-browsing');

        Bus.emit('app:usermenu:updatebrowsing',
          {   normal: user
            , browsing: browsing_user
            , action: action
            , setup: action === 'INPUT_NEW_PASSWORD'
            , originToken: session.originToken
            , tokenType: 'user'
            , page: 'profile'
          }
          , 'browsing_identity');

        delete session.originToken;

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

    } else {
      // 跳回首页
      res.redirect('/');
    }
  };


  // invalid
  routes.invalid = function (req, res, next) {
    var session = req.session
      , authorization = session.authorization
      , user = session.user;

    document.title = 'EXFE - Invalid Link'

    Bus.emit('app:page:home', false);

    if (authorization) {
      Bus.emit('app:page:usermenu', true);
      Bus.emit('app:usermenu:updatenormal', user);
      Bus.emit('app:usermenu:crosslist'
        , authorization.token
        , authorization.user_id
      );
    }
    else {
      Bus.emit('app:page:usermenu', false);
    }

    res.render('invalid.html', function (tpl) {
      $('#app-main').append(tpl);
    });
  };


  // signout
  routes.signout = function (req, res, next) {
    Store.remove('user');
    Store.remove('authorization');
    window.location.href = '/';
  };


  // Get User Data
  routes.refreshAuthUser = function (req, res, next) {
    var session = req.session
      , authorization = session.authorization;

    if (!authorization) {
      next();
      return;
    }

    // Get User
    Bus.emit('app:api:getuser'
      , authorization.token
      , authorization.user_id
      , function (data) {
          var user = data.user;
          Store.set('user', session.user = user);

          next();
        }
        // 继续使用本地缓存
      , function (data) {
          next();
        }
    );
  };


  // Helpers:
  // ----------------------------
  function redirectToProfile(req, res) {
    var session = req.session;
    var user = Store.get('user');

    function done(user, res) {
      var external_username = Util.printExtUserName(user.default_identity);
      res.redirect('/#' + external_username);
    }

    if (user) {
      done(user, res);
      return;
    }

    var authorization = session.authorization;
    //Bus.once('app:user:signin:after', function (user) {
      //done(user, res);
    //});
    Bus.emit('app:user:signin', authorization.token, authorization.user_id, true);
  }

});
