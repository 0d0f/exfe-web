define(function (require) {

  var $ = require('jquery');
  var R = require('rex');
  var Bus = require('bus');
  var Api = require('api');
  var Util = require('util');
  var Store = require('store');
  var Handlebars = require('handlebars');

  var userpanels = {
    // new identity
    '1': ''
      + '<div class="dropdown-menu user-panel">'
        + '<div class="header">'
          + '<h2>Browsing Identity</h2>'
        + '</div>'
        + '<div class="body">'
        + '<div>You are browsing this page as Email identity:</div>'
        + '<div class="identity">'
          + '<span class="pull-right avatar">'
            + '<img width="20" height="20" alt="" src="{{avatar_filename}}">'
          + '</span>'
          + '<i class="icon-envelope"></i>'
          + '<span>{{external_id}}</span>'
        + '</div>'
        + '</div>'
        + '<div class="footer">'
          + '<button class="xbtn xbtn-signin">Sign In</button>'
        + '</div>'
      + '</div>',

    // new identity & merge
    '2': ''
      + '<div class="dropdown-menu user-panel">'
        + '<div class="header">'
          + '<h2>Browsing Identity</h2>'
        + '</div>'
        + '<div class="body">'
          + '<div>You are browsing this page as Email identity:</div>'
          + '<div class="identity">'
            + '<span class="pull-right avatar">'
              + '<img width="20" height="20" alt="" src="{{avatar_filename}}">'
            + '</span>'
            + '<i class="icon-envelope"></i>'
            + '<span>{{external_id}}</span>'
          + '</div>'
          + '<div class="set-up">'
            + '<a href="#">Set Up</a> as your independent new <span class="x-sign">EXFE</span> identity.'
          + '</div>'
          + '<div class="spliterline"></div>'
          + '<div class="merge hide">'
            + '<a href="#">Merge</a> with your currently signed in identities:'
          + '</div>'
          + '<div class="identity hide">'
            + '<span class="pull-right avatar">'
              + '<img width="20" height="20" alt="" src="/img/users/u1x20.png">'
            + '</span>'
            + '<i class="icon-envelope"></i>'
            + '<span>steve@0d0f.com</span>'
          + '</div>'
          + '</div>'
        + '<div class="footer">'
          + '<button class="xbtn xbtn-gather" id="js-xgather">Gather</button>'
        + '</div>'
      + '</div>',

    // signin
    '3': ''
      + '<div class="dropdown-menu user-panel">'
        + '<div class="header">'
          + '<div class="meta">'
            + '<a class="pull-right avatar" href="/s/profile">'
              + '<img width="40" height="40" alt="" src="{{avatar_filename}}" />'
            + '</a>'
            + '<a class="attended" href="/s/profile">'
              + '<span class="attended-nums">{{cross_quantity}}</span>'
              + '<span class="attended-x"><em class="x-sign">X</em> attended</span>'
            + '</a>'
          + '</div>'
        + '</div>'
        + '<div class="body">'
        + '</div>'
        + '<div class="footer">'
          //+ '<button class="xbtn xbtn-gather">Gather</button>'
          + '<a href="#" class="xbtn xbtn-gather" id="js-xgather">Gather</a>'
          + '<div class="spliterline"></div>'
          + '<div class="actions">'
            + '<a href="/s/logout" class="pull-right" id="js-signout">Sign out</a>'
            //+ '<a href="#">Settings</a>'
          + '</div>'
        + '</div>'
      + '</div>'
  };

  // Start Up App
  var START_UP = 'app:startup';
  // 未登陆状态 / 跳转
  var NO_SIGN_IN = 'app:nosignin';
  // 可以登陆状态
  var SIGN_IN = 'app:signin';

  Bus.on(NO_SIGN_IN, function () {
    // rewrite login page
    if (/^\/profile\..*/.test(window.location.pathname)) {
      // 暂时先跳到首页
      window.location = '/';
    }
  });

  Bus.on('app:changename', function (d) {
    $('#user-name > span').html(d);
  });

  Bus.on('app:userpanel', function (d) {
    var action_status = d.action_status;

    var s = Handlebars.compile(userpanels[3]);

    var duser;

    if (d.d1.response) duser = d.d1.response.user;
    else if (d.d1 instanceof Array) duser = d.d1[0].response.user;

    $(s(duser)).appendTo($('div.dropdown-wrapper'))

    //if (d.action_status === 3) {
      var signin = Store.get('signin');
      var user_id = signin.user_id;
      Api.request('crosslist'
        , {
          resources: {user_id: user_id}
        }
        , function (data) {
          // NOTE:
          // now: 当前时间～cross发生时间(3hr)
          // 24hr: cross发生时间 ～ 当前时间(24hr)

          var crosses = data.crosses;
          if (crosses.length) {
            var now = +new Date();
            var ne = now + 3 * 60 * 60 * 1000;
            var n24 = now - 24 * 60 * 60 * 1000;
            var l = 5;
            var cs = {
              crosses: []
            };

            R.map(crosses, function (v, i) {
              if (v.exfee && v.exfee.invitations && v.exfee.invitations.length) {
                var t = R.filter(v.exfee.invitations, function (v2, j) {
                  if (v2.rsvp_status === 'ACCEPTED' && v2.identity.connected_user_id === user_id) return true;
                });
                if (t.length) {
                  cs.crosses.push(v);
                }
              }
            });

            cs.crosses = cs.crosses.slice(0, l);

            Handlebars.registerHelper('alink', function (ctx) {
              var s = '';
              var beginAt = ctx.time.begin_at;
              var dt = new Date(beginAt.date.replace(/\-/g, '/') + ' ' + beginAt.time).getTime();
              if (now <= dt && dt <= ne) {
                s = '<li class="tag">'
                      + '<i class="icon10-now"></i>'
              } else if (n24 <= dt && dt < now) {
                s = '<li class="tag">'
                      + '<i class="icon10-24hr"></i>'
              } else {
                s = '<li>'
              }
              s += '<a data-id="' + this.id + '" href="/!' + this.id + '">' + this.title + '</a>'
                  + '</li>';
              return s;
            });

            var s = '{{#if crosses}}'
                + '<div>Upcoming:</div>'
                + '<ul class="unstyled crosses">'
                + '{{#each crosses}}'
                  + '{{{alink this}}}'
                + '{{/each}}'
                + '</ul>'
              + '{{/if}}';

            var as = Handlebars.compile(s);
            $('.user-panel .body').html(as(cs));
          }

        }
      );
    //}

  });

  Bus.on(SIGN_IN, function (d) {
    // 不是 Profile 自动跳转
    // 暂时 从 index 调整到 profile.html
    if (/^\/$/.test(window.location.pathname)) {
      window.location = '/profile';
      return;
    }

    d.dfd.then(function (a1, a2) {
      Bus.emit('app:signinsuccess', a1);
      var SIGN_IN_OTHERS = 'app:signinothers';
      Bus.emit(SIGN_IN_OTHERS, d.dfd);
      Bus.emit('app:userpanel', {action_status: d.action_status, d1: a1, d2: a2});
    });

  });

  Bus.once(START_UP, function () {
    // get token
    var search = decodeURIComponent(window.location.search)
      , match = /token=([a-zA-Z0-9]{32})/.exec(search)
      , ntoken = (match && match[1]) || false;

    var userToken = Store.get('signin')
      , otoken = false;
    userToken && (otoken = userToken.token);
    var tokens = [];
    // `sign in`: 1, `set up or merge`: 2, `auto login`: 3, `fali`: 0
    var action_status;

    // new identity: sign in
    if (ntoken && !otoken) {
      action_status = 1;
      tokens.push(ntoken);
    // new identity: set up or merge
    } else if (ntoken && otoken && ntoken !== otoken) {
      action_status = 2;
      tokens.push(ntoken);
      tokens.push(otoken);
    // auto login
    } else if (ntoken && otoken && ntoken === otoken) {
      action_status = 3;
      tokens.push(ntoken);
    // auto login
    } else if (!ntoken && otoken) {
      action_status = 3;
      tokens.push(otoken);
    } else {
      action_status = 0;
      // 跳转到登陆页
      //return;
    }

    var dfd = $.when;
    var channel = SIGN_IN;

    if (action_status) {
      Api.request('checkAuthorization'
        , {
          type: 'POST',
          data: {
            tokens: JSON.stringify(tokens)
          }
        }
        , function (data) {
          var ds = [];
          if (tokens.length) {
            var token = tokens[0];

            if (token in data.statuses && !data.statuses[token]) {
              // token失效, 暂时跳转到首页
              window.location.href= '/';
              Store.remove('signin');
              return;
            }

            var user_id = data.statuses[token].user_id;
            Api.setToken(token);
            Store.set('signin', {token: token, user_id: user_id});
            //if (action_status === 1 || action_status === 3) {
            ds.push(
              Api.request('getUser'
                , {
                  resources: {user_id: user_id}
                }
                , function (data) {
                  //Store.set('user', data.response.user);
                  var last_identity = Store.get('last_identity');
                  var identity = R.filter(data.user.identities, function (v) {
                    if (last_identity === v.external_id) return true;
                  })[0] || data.user.default_identity;
                  Store.set('lastIdentity', identity);
                })
              );
            //}

            if (action_status === 2) {
              token = tokens[1];
              user_id = data.statuses[token].user_id;
              Store.set('osignin', {token: token, user_id: user_id});
              ds.push(
                Api.request('getUser'
                  , {
                    resources: {user_id: user_id}
                  }
                )
              );
            }
          }

          dfd = dfd.apply(null, ds);
          dfd.done(function (a1, a2) {
            Bus.emit(channel, {dfd: dfd, action_status: action_status});
          });
        }
      );

    } else {
      channel = NO_SIGN_IN;
      dfd().then(function () {
        Bus.emit(channel);
      })
    }

  });

  Bus.emit(START_UP);

  var $BODY = $(document.body);
  $(function () {

    /**
     *
     * User-Panel 下拉菜单动画效果
     */
    // 初始化高度
    var _i_ = false;
    function hover(e) {
      var self = $(this)
        , timer = self.data('timer')
        , $userPanel = self.find('div.user-panel')
        , h = -$userPanel.outerHeight();

      e.preventDefault();

      if (e.type === 'mouseleave') {
        timer = setTimeout(function () {
          $userPanel
            .stop()
            .animate({top: h}, 200, function () {
              self.prev().addClass('hide');
              self.parent().removeClass('user');
            });
          clearTimeout(timer);
          self.data('timer', timer = null);
        }, 500);

        self.data('timer', timer);
        return false;
      }

      if (timer) {
        clearTimeout(timer);
        self.data('timer', timer = null);
        return false;
      }

      if (!_i_) {
        $userPanel.css('top', h);
        self.find('.user-panel').addClass('show');
        _i_ = true;
      }

      self.prev().removeClass('hide');
      self.parent().addClass('user');
      $userPanel
        .stop()
        .animate({top: 56}, 100);
    }

    $BODY.on('mouseenter.dropdown mouseleave.dropdown', '.navbar .dropdown-wrapper', hover);

    // 兼容 iframe
    var isIframe = !(parent === window);
    var domain = /domain=([^&]+)/.exec(decodeURIComponent(window.location.search));
    domain = (domain && domain[1]) || '';
    $BODY.on('click', '#js-xgather', function (e) {
      e.preventDefault();
      // 兼容 iframe
      if (isIframe) {
        parent.postMessage('gather', domain);
      }
    });

    $BODY.on('click', '.meta > a', function (e) {
      var p = $(this).attr('href');
      e.preventDefault();
      // 兼容 iframe
      if (isIframe) {
        parent.postMessage('profile:' + p, domain);
      }
    });

    $BODY.on('click', 'a[data-id]', function (e) {
      var id_base62 = $(this).attr('href').substr(2);
      e.preventDefault();
      // 兼容 iframe
      if (isIframe) {
        parent.postMessage('cross:' + id_base62, domain);
      }
    });

    $BODY.on('click', '#js-signout', function (e) {
      e.preventDefault();
      // 兼容 iframe
      if (isIframe) {
        parent.postMessage('logout', domain);
      } else {
        Store.remove('signin');
        window.location = '/';
      }
    });
  });

});
