define(function (require) {

  var $ = require('jquery');
  var R = require('rex');
  var Api = require('api');
  var Bus = require('bus');
  var Util = require('util');
  var Store = require('store');
  var Handlebars = require('handlebars');

  var userpanelTmps = {
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
          + '<i class="icon16-identity-{{provider}}"></i>'
          + '<span>{{external_id}}</span>'
        + '</div>'
        + '</div>'
        + '<div class="footer">'
          + '<button class="xbtn xbtn-signin" data-widget="dialog" data-identity-id={{id}} data-dialog-tab="{{__tab__}}" data-dialog-type="{{__dialogtype__}}" data-source="{{external_id}}">Sign In</button>'
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
            + '<a href="#" data-widget="dialog" data-dialog-type="identification" data-dialog-tab="d02" data-source="{{external_id}}">Set Up</a> as your independent new <span class="x-sign">EXFE</span> identity.'
          + '</div>'
          //+ '<div class="spliterline"></div>'
          //+ '<div class="merge hide">'
          //  + '<a href="#">Merge</a> with your currently signed in identities:'
          //+ '</div>'
          //+ '<div class="identity hide">'
            //+ '<span class="pull-right avatar">'
            //  + '<img width="20" height="20" alt="" src="{{avatar_filename}}">'
            //+ '</span>'
            //+ '<i class="icon16-identity-{{provider}}"></i>'
            //+ '<span>{{external_id}}</span>'
          //+ '</div>'
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
          + '<a href="/x/gather" class="xbtn xbtn-gather" id="js-xgather">Gather</a>'
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
    if (/^s\/profile.*$/.test(window.location.pathname)) {
      //window.location = '/';
    }

    $('#js-signin').show();
  });

  Bus.on(SIGN_IN, function (dfd, type) {

    dfd.then(function (data) {
      Bus.emit('app:signinsuccess', data);
      var SIGN_IN_OTHERS = 'app:signinothers';
      Bus.emit(SIGN_IN_OTHERS, dfd);
      Bus.emit('app:userpanel', {type: type, data: data});
    });

  });

  Bus.on('app:changename', function (d) {
    $('#user-name > span').html(d);
  });

  function createUserPanel(data, type) {
    if (type) {
      $('#js-signin').remove();
      var $up = $('.nav li.dropdown').show();

      var s = Handlebars.compile(userpanelTmps[type]);

      $(s(data)).appendTo($up.find('.dropdown-wrapper'));
    }
  }

  Bus.on('app:crosstoken', function (statuses, token, type) {
    var status = statuses[token];
    var identity_id = status.identity_id;
    var dialogType, tab = '';
    switch (status.identity_registration) {
      case 'VERIFY':
        //dialogType = 'verification';
        dialogType = 'identification';
        tab = 'd01';
        break;
      case 'SIGN_UP':
        dialogType = 'identification';
        tab = 'd02';
        type = 2;
        break;
      case 'SIGN_IN':
        dialogType = 'identification';
        tab = 'd01';
    }
    var dfd = $.when(
      Api.request('getIdentityById',
        {
          resources: {identity_id: identity_id}
        },
        function (data) {
          var external_id = data.identity.external_id;
          $('#user-name > span').html(data.identity.name || external_id.split('@')[0]);
          if (dialogType === 'verification') {
            dialogType += '_' + data.identity.provider;
          }
          data.identity.__dialogtype__ = dialogType;
          data.identity.__tab__ = tab;
          createUserPanel(data.identity, type);
        }
      )
    );

    dfd.done(function (data) {
      Bus.emit('app:crossdata', token, status);
    });
  });

  Bus.on('app:userpanel', function (d) {
    var type = d.type, data = d.data;

    createUserPanel(data.response.user, type);
    $('#user-name > span').html(data.response.user.name);

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

  Bus.on('app:usertoken', function (statuses, token, type) {
    var status = statuses[token];
    var user_id = status.user_id;

    Api.setToken(token);
    Store.set('signin', {token: token, user_id: user_id});

    var dfd = $.when(
      Api.request('getUser'
      , {
        resources: {user_id: user_id}
      }
      , function (data) {
        var last_identity = Store.get('last_identity');
        var identity = R.filter(data.user.identities, function (v) {
          if (last_identity === v.external_id) return true;
        })[0] || data.user.default_identity;
        Store.set('lastIdentity', identity);
      })
    );
    dfd.done(function (d) {
      Bus.emit(SIGN_IN, dfd, type);
    });

  });

  Bus.on('app:userstatus', function (d) {
    var type= d.type, statuses = d.statuses, token = d.token;
    switch (type) {
      case 1:
      case 2:
        Bus.emit('app:crosstoken', statuses, token, type);
        break;
      case 3:
        Bus.emit('app:usertoken', statuses, token, type);
        break;
      default:
        //window.location.href = '/';
    }
  });

  // Start Up App
  Bus.once(START_UP, function () {
    // get token
    var search = decodeURIComponent(window.location.search),
      match = /token=([a-zA-Z0-9]{32})/.exec(search),
      ntoken = (match && match[1]) || false,
      signin = Store.get('signin'),
      otoken = false,
      tokens = [],
      // type: 1 signin, 2 setup & merge, 3 auto login, 0 fail
      type = 0,
      channel,
      tl;


    signin && (otoken = signin.token);

    if (ntoken) {
      tokens.push(ntoken);
    }

    if (otoken && (ntoken !== otoken)) {
      tokens.push(otoken);
    }

    if (!tokens.length) {
      channel = NO_SIGN_IN;
      $.when().then(function () {
        Bus.emit(channel);
      });
      return;
    }

    Api.request('checkAuthorization'
      , {
        type: 'POST',
        data: {
          tokens: JSON.stringify(tokens)
        }
      }
      , function (data) {
        var statuses = data.statuses,
            token = tokens[0],
            status0 = statuses[token];

        if (status0) {

          if (status0.type === 'CROSS_TOKEN') {
            type = 1;

            if (tl === 2) {
              type = 2;
            }
          } else if (status0.type === 'USER_TOKEN') {
            type = 3;

            if (tl === 2) {
              delete statuses[tokens[1]];
            }
          }
        }

        Bus.emit('app:userstatus', {token: token, type: type, statuses: statuses});
      }
    );

  });
  //Bus.emit(START_UP);

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

    $BODY.on('click', '#js-signout', function (e) {
      e.preventDefault();
      Store.remove('signin');
      window.location = '/s/logout';
    });
  });
});
