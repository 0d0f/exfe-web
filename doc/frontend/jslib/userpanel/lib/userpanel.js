define(function (require) {

  var $ = require('jquery');
  var Bus = require('bus');
  var Util = require('util');
  var Store = require('store');
  var Handlebars = require('handlebars');

  var userpanels = {
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
          + '<div class="merge">'
            + '<a href="#">Merge</a> with your currently signed in identities:'
          + '</div>'
          + '<div class="identity">'
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

    '3': ''
      + '<div class="dropdown-menu user-panel">'
        + '<div class="header">'
          + '<div class="meta">'
            + '<a class="pull-right avatar">'
              + '<img width="40" height="40" alt="" src="{{avatar_filename}}" />'
            + '</a>'
            + '<a class="attended">'
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

  Bus.on('app:userpanel', function (d) {
    var action_status = d.action_status;

    var s = Handlebars.compile(userpanels[action_status]);

    $(s(d.d1.response.user)).appendTo($('#user-name').parent())

    if (d.action_status === 3) {
      var signin = Store.get('signin');
      var user_id = signin.user_id;
      var token = signin.token;
      var qqqq = '&upcoming_included=true&anytime_included=false&sevendays_include=false&later_included=false&past_included=false';
      $.ajax({
        url: Util.apiUrl + '/users/' + user_id + '/crosslist?token=' + token + qqqq,
        type: 'GET',
        dataType: 'JSON',
        xhrFields: { withCredentials: true}
      })
        .done(function (data) {
          if (data.meta.code === 200) {
            var crosses = data.response.crosses;
            if (crosses.length) {
              var now = new Date();
              var ns = now.getTime();
              var ne = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1).getTime();
              var n24 = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1).getTime();
              Handlebars.registerHelper('alink', function (ctx) {
                var s = '';
                var beginAt = ctx.time.begin_at;
                var dt = new Date(beginAt.date.replace(/\-/g, '/') + ' ' + beginAt.time).getTime();
                if (dt >= ns) {
                  s = '<li class="tag">'
                        + '<span class="now">NOW</span>'
                } else if (dt < ns && dt >= n24) {
                  s = '<li class="tag">'
                        + '<span class="hr24">24hr</span>'
                } else {
                  s = '<li>'
                }
                s += '<a data-id="' + this.id + '" href="/!' + this.id_base62 + '">' + this.title + '</a>'
                    + '</li>';
                return s;
              });
              var s = '<div>Upcoming:</div>'
                + '<ul class="crosses">'
                + '{{#each crosses}}'
                  + '{{{alink this}}}'
                + '{{/each}}'
                + '</ul>';

              var as = Handlebars.compile(s);
              $('.user-panel .body').html(as({crosses: crosses}));
            }

          }

        });
    }

  });

  Bus.on(SIGN_IN, function (d) {
    // 不是 Profile 自动跳转
    // 暂时 从 index 调整到 profile.html
    if (/^\/$/.test(window.location.pathname)) {
      window.location = '/profile.html';
      return;
    }

    d.dfd.then(function (a1, a2) {
      Bus.emit('app:signinsuccess', a1.response);
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
      $.ajax({
        url: Util.apiUrl + '/users/checkauthorization',
        type: 'POST',
        dataType: 'json',
        xhrFields: {withCredentials: true},
        data: {
          tokens: JSON.stringify(tokens)
        }
      })
        .done(function (data) {
          if (data.meta.code === 200) {
            var ds = [];
            if (tokens.length === 1) {
              var token = tokens[0];

              if (token in data.response.statuses && !data.response.statuses[token]) {
                // token失效, 暂时跳转到首页
                window.location.href= '/';
                Store.remove('signin');
                return;
              }

              var user_id = data.response.statuses[token].user_id;
              Store.set('signin', {token: token, user_id: user_id});
              //if (action_status === 1 || action_status === 3) {
              ds.push(
                  $.ajax({
                    url: Util.apiUrl + '/users/' + user_id + '?token=' + token,
                    type: 'GET',
                    dataType: 'JSON',
                    xhrFields: { withCredentials: true}
                  })
                    .done(function (data) {
                      if (data.meta.code === 200) {
                        //Store.set('user', data.response.user);
                      }
                    })
                );
              //}

              if (action_status === 2) {
                token = tokens[1];
                user_id = data.response.statuses[token].user_id;
                Store.set('osignin', {token: token, user_id: user_id});
                ds.push(
                  $.ajax({
                    url: Util.apiUrl + '/users/' + user_id + '?token=' + token,
                    type: 'GET',
                    dataType: 'JSON',
                    xhrFields: { withCredentials: true}
                  })
                );
              }
            }

            dfd = dfd.apply(null, ds);
            dfd.done(function (a1, a2) {
              Bus.emit(channel, {dfd: dfd, action_status: action_status});
            });
          }

        });
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

    function hover(e) {
      e.preventDefault();
      if (e.type === 'mouseenter') {
        $(this).find('.user-panel').addClass('show');
        $(this).find('#user-name').next().removeClass('hide');
        $(this).addClass('user');
      } else {
        $(this).removeClass('user');
        $(this).find('#user-name').next().addClass('hide');
        $(this).find('.user-panel').removeClass('show');
      }
    }

    $BODY.on('mouseenter.dropdown mouseleave.dropdown', '.navbar .dropdown', hover);

    $BODY.on('mouseenter.dropdown', '.navbar .fill-left', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).addClass('hide');
    });

    var domain = 'http://localexfe.me';
    var isIframe = !(parent === window);
    $BODY.on('click', '#js-xgather', function (e) {
      e.preventDefault();
      // 兼容 iframe
      if (isIframe) {
        parent.postMessage('gather', domain);
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

