define('middleware', [], function (require, exports, module) {
  var $ = require('jquery');
  var R = require('rex');
  var Api = require('api');
  var Bus = require('bus');
  var Util = require('util');
  var Store = require('store');
  var Handlebars = require('handlebars');

  // token regexp
  var tokenRegExp = Util.tokenRegExp;

  var middleware = module.exports = {};

  /*
  middleware.basicAuth = function (req, res, next) {
    tokenRegExp.lastIndex = 0;
    var match = tokenRegExp.exec(req.url)
      , urlToken = 
  };
  */

  // Login Handle.
  middleware.login = function (req, res, next) {
    //console.log('middleware login');
    tokenRegExp.lastIndex = 0;
    var match = tokenRegExp.exec(req.url)
      // new token
      , ntoken = (match && match[1]) || false
      , signin = Store.get('signin')
      // old token
      , otoken = (signin && signin.token) || false
      , tokens = []
      // logintype:
      // -------------
      // 1           3
      //    login
      // -------------
      // 2           4
      // merge & setup
      // -------------
      // 0
      // fail
      // -------------
      , logintype = 0
      // tokens length
      , tokensLen;

    if (ntoken) {
      tokens.push(ntoken);
    }

    if (otoken && (ntoken !== otoken)) {
      tokens.push(otoken);
    }

    res.loginable = true;

    // login-able
    if (0 === (tokensLen = tokens.length)) {
      res.loginable = false;
      if (req.url !== '/' && req.url !== '/#gather') {
        Bus.emit('xapp:goto_home');
      }
      else {
        next();
      }
      return;
    }

    var signin = Store.get('signin');

    // checking auth
    Api.request('checkAuthorization', {
        type: 'POST',
        data: {
          tokens: JSON.stringify(tokens)
        }
      },

      function (data) {
        var statuses = data.statuses
          , token = tokens[0]
          , status0 = statuses[token];

        if (status0) {
            logintype = 3;

          if (2 === tokensLen) {
            logintype = 4;
            //delete statuses[tokens[1]];
          }
        }

        Bus.emit(XAPP_USER_STATUS, {
            tokens: tokens
          , type: logintype
          , statuses: statuses
        });

        next();

      }
    );

  };

  // XAPP_USER_TOKEN
  var XAPP_USER_TOKEN = 'xapp:usertoken';

  Bus.on(XAPP_USER_TOKEN, function (token, user_id, type) {
    var dfd;

    // set token
    Api.setToken(token);

    Store.set('signin', {token: token, user_id: user_id});

    dfd = Api.request('getUser'
      , {
        resources: {user_id: user_id}
      }
      , function (data) {
        var last_external_id = Store.get('last_external_id')
          , identity;

        if (last_external_id) {
          identity = R.filter(data.user.identities, function (v) {
            if (last_external_id === v.external_id) {
              return true;
            }
          })[0];
        } else {
          identity = data.user.default_identity;
        }

        // set last identity
        Store.set('user', data.user);
        Store.set('lastIdentity', identity);
        Store.set('last_external_id', identity.external_id);
      }
    );

    // login done
    dfd.done(function (d) {
      Bus.emit(XAPP_LOGIN_DONE, type, dfd);
    });

  });

  // XAPP_USER_STATUS
  var XAPP_USER_STATUS = 'xapp:userstatus';

  Bus.on(XAPP_USER_STATUS, function (d) {
    switch (d.type) {
      case 1:
      case 2:
        Bus.emit(XAPP_CROSS_TOKEN, d.tokens, d.type, d.statuses);
        break;
      case 3:
        var token = d.tokens[0]
          , status = d.statuses[token]
          , user_id = status.user_id;
        Bus.emit(XAPP_USER_TOKEN, token, user_id, d.type);
        break;
      default:
        // fail
        break;
    }
  });

  // XAPP_LOGIN_DONE
  var XAPP_LOGIN_DONE = 'xapp:logindone';

  Bus.on(XAPP_LOGIN_DONE, function (type, dfd) {
    dfd.then(function (data) {
      Bus.emit(XAPP_USER_PANEL, type, data.response.user);
      Bus.emit('xapp:home_profile');
    });

  });

  // XAPP_USER_PANEL
  var XAPP_USER_PANEL = 'xapp:userpanel';

  Bus.on(XAPP_USER_PANEL, function (type, user) {
    if($('.user-panel').length) return;
    createUserPanel(user, type);

    $('#user-name').attr('href', '/#' + user.default_identity.external_id);
    $('#user-name > span').html(user.name);

    var signin = Store.get('signin')
      , user_id = signin.user_id;

    Api.request('crosslist'
      , {
        resources: { user_id: user_id }
      }
      , function (data) {
        // NOTE:
        // now: 当前时间～cross发生时间(3hr)
        // 24hr: cross发生时间 ～ 当前时间(24hr)

        var crosses = data.crosses;
        if (0 === crosses.length) { return; }
        var now = +new Date()
          , ne = now + 3 * 60 * 60 * 1000
          , n24 = now - 24 * 60 * 60 * 1000
          , l = 5
          , cs = {
            crosses: []
          };

        R.map(crosses, function (v, i) {
          if (v.exfee && v.exfee.invitations && v.exfee.invitations.length) {
            var t = R.filter(v.exfee.invitations, function (v2, j) {
              if (v2.rsvp_status === 'ACCEPTED' && v2.identity.connected_user_id === user_id) {
                return true;
              }
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
          s += '<a data-id="' + this.id + '" href="/#!' + this.id + '">' + this.title + '</a>'
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
    );
  });

  // helper

  function createUserPanel(user, type) {
    if (type) {
      $('#js-signin').hide();
      var $up = $('.nav li.dropdown').remove('user').show();

      var s = Handlebars.compile(userpanelTmps[type]);

      $(s(user)).appendTo($up.find('.dropdown-wrapper'));
      //$('.dropdown-wrapper').find('.user-panel').addClass('show');
    }
  }

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
            + '<a class="pull-right avatar" href="/#{{default_identity.external_id}}">'
              + '<img width="40" height="40" alt="" src="{{avatar_filename}}" />'
            + '</a>'
            + '<a class="attended" href="/#{{default_identity.external_id}}">'
              + '<span class="attended-nums">{{cross_quantity}}</span>'
              + '<span class="attended-x"><em class="x-sign">X</em> attended</span>'
            + '</a>'
          + '</div>'
        + '</div>'
        + '<div class="body">'
        + '</div>'
        + '<div class="footer">'
          //+ '<button class="xbtn xbtn-gather">Gather</button>'
          + '<a href="/#gather" class="xbtn xbtn-gather" id="js-xgather">Gather</a>'
          + '<div class="spliterline"></div>'
          + '<div class="actions">'
            + '<a href="#" class="pull-right" id="js-signout">Sign out</a>'
            //+ '<a href="#">Settings</a>'
          + '</div>'
        + '</div>'
      + '</div>'
  };

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
        , $userPanel = self.find('div.user-panel').addClass('show')
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
        //_i_ = true;
      }

      self.prev().removeClass('hide');
      self.parent().addClass('user');
      $userPanel
        .stop()
        .animate({top: 56}, 100);
    }

    $BODY.on('mouseenter.dropdown mouseleave.dropdown', '.navbar .dropdown-wrapper', hover);

    $BODY.on('click.dropdown', '.navbar .dropdown-wrapper a[href^="/#"]', function (e) {
      var self = $('.navbar .dropdown-wrapper')
        , $userPanel = self.find('div.user-panel').addClass('show')
        , timer = self.data('timer')
        , h = -$userPanel.outerHeight();

        clearTimeout(timer);
        self.data('timer', timer = null);
        $userPanel.css('top', h);

        self
        .prev()
        .addClass('hide')
       .end()
       .parent()
       .removeClass('user');
    });

    $BODY.on('click', '#js-signout', function (e) {
      e.preventDefault();
      // NOTE: 暂时放这里
      Bus.emit('xapp:cross:end');
      $('.navbar .dropdown-wrapper').find('.user-panel').remove();;
      $('#js-signin')
        .show()
        .next().hide()
        .removeClass('user')
        .find('.fill-left').addClass('hide')
        .end()
        .find('#user-name span').text('');;
      Store.remove('signin');
      Bus.emit('xapp:goto_home');
    });
  });

});
