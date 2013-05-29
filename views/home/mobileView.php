<?php include "share/header_mobile.php" ?>
  <style>
    .hide { display: none; }
  </style>
</head>
<body>
  <!-- iframe {{{ -->
  <div id="mframe" style="text-align: center;">
    <h5>Loading...</h5>
    <iframe id="xframe" frameborder="0" src="" style="display:none"></iframe>
  </div>
  <!-- /iframe }}} -->

  <!-- Container {{{-->
  <div class="container" id="app-container">
    <div role="main" id="app-main">
      <div id="app-header" class="page-header hide">
        <div class="center">
          <div class="welcome">Welcome to <span class="exfe">EXFE</span></div>
          <div class="exfe-logo">
            <img src="/static/img/EXFE_glossy_50@2x.png" width="96" height="50" />
          </div>
        </div>
      </div>
      <div id="app-body" class="page-body"></div>
      <div id="app-footer" class="page-footer hide">
        <div class="actions">
          <div class="action subscribe hide">
            <div class="subscribe-title">Subscribe to updates and engage in:</div>
            <div class="subscribe-frame">
              <input type="text" class="email" id="email" placeholder="Enter your email">
              <button class="btn_mail">OK</button>
            </div>
          </div>
          <div class="action error-info hide"></div>
          <div class="action get-button">
            <!--button>Get <span class="exfe">EXFE</span> app <span class="free">free</span></button-->
            <button>Open <span class="exfe">EXFE</span> app</button>
          </div>
          <div class="action redirecting hide">Redirecting to EXFE app in <span class="sec">0</span>s.</div>
          <div class="action web-version hide"><span class="underline">Proceed</span> with desktop web version.</div>
        </div>
      </div>
    </div>
  </div>
  <!--/Container }}}-->

  <!-- Templates {{{-->
  <script id="home-tmpl" type="text/x-handlebars-template">
  <div class="page home-page hide" id="app-home">
    <div class="logo-box">
      <div class="title">
        <div class="normal">
          <h1>EXFE</h1>
          <p>The group utility for gathering.</p>
        </div>
        <div class="invalid hide">
          <h1>Invalid link</h1>
          <p>Requested page was not found.</p>
        </div>
      </div>
      <div class="inner">
        <img id="big-logo" class="big-logo" width="320" height="300" src="/static/img/EXFE_glossy@2x.png" />
      </div>
    </div>
    <div id="home-card" class="card">
      <div class="avatar"></div>
      <div class="tap-tip">Start Live</div>
    </div>
  </div>
  </script>

  <script id="cross-tmpl" type="text/x-handlebars-template">
  <div class="page cross-page hide" id="app-cross">
    <!--div class="cross redirecting">Redirecting to <span class="exfe_blue3">EXFE</span> app in <span class="sec">0</span>s.</div-->
    <div class="content">
      <div class="title_area" style="background: url(/static/img/xbg/{{background}}) no-repeat 50% 50%;">
        <div class="title_wrap_a">
          <div class="title_wrap_b">
            <div class="title_text">{{{title}}}</div>
          </div>
        </div>
        {{#if inviter}}
        <div class="inviter">
          <div class="ribbon"></div>
          <div class="textoverflow">Invited by <span class="inviter_highlight">{{inviter.name}}</span></div>
        </div>
        {{/if}}
        <div class="title_overlay"></div>
      </div>
      <div class="inf_area">
        <div class="description">{{{description}}}<div class="xbtn-more hide"><span class="rb hidden"></span><span class="lt hidden"></span></div></div>
        <div class="time_area">
          <div class="time_major">{{time.title}}</div>
          <div class="time_minor{{#if time.tobe}} {{time.tobe}}{{/if}}">{{time.content}}</div>
        </div>
        <div class="place_area">
          <div class="place_major">{{place.title}}</div>
          <div class="place_minor{{#if place.tobe}} {{place.tobe}}{{/if}}">{{place.description}}</div>
          {{#if place.map}}
          <a class="map_link" href="{{#if place.href}}{{place.href}}{{else}}#{{/if}}">
            <div class="map{{#unless place.map}} {{hide}}{{/unless}}" {{#if place.map}}style="background-image: url({{place.map}});"{{/if}}>
              <img class="place_mark" alt="" src="http://img.exfe.com/web/map_pin_blue@2x.png" />
            </div>
          </a>
          {{/if}}
        </div>
        {{#unless read_only}}
        <div class="rsvp_toolbar{{#unless inviter}} rsvp_toolbar_off{{/unless}}">
          <div class="tri"></div>
          <table>
            <tr>
              {{#if identity.isphone}}
              <td class="rsvp accepted" width="98">I'm in</td>
              <td class="rsvp unavailable" width="98">Unavailable</td>
              {{#if change_name}}
              <td class="rsvp changename" width="98">Change my display name</td>
              {{/if}}
              {{else}}
              <td class="rsvp accepted">I'm in</td>
              <td class="rsvp unavailable">Unavailable</td>
              {{/if}}
            </tr>
          </table>
        </div>
        {{/unless}}
        <div class="exfee">
          <table>
          {{#each invitations}}
            <tr>
            {{#each this}}
              {{#if this}}
              {{#if is_me}}
              <td>
                <div class="portrait me" style="background: url({{identity.avatar_filename}}); background-size: 50px 50px;"></div>
                <div class="portrait_rsvp_me {{rsvp_style}}"></div>
                {{#if mates}}
                <div class="portrait_mymate">{{mates}}</div>
                {{/if}}
                <div class="name_me textcut">{{{identity.name}}}</div>
              </td>
              {{else}}
              <td>
                <div class="portrait" style="background: url({{identity.avatar_filename}}); background-size: 50px 50px;">
                {{#if mates}}
                  <div class="portrait_mate">{{mates}}</div>
                {{/if}}
                </div>
                <div class="portrait_rsvp {{rsv_style}}"></div>
                <div class="name textcut">{{identity.name}}</div>
              </td>
              {{/if}}
              {{else}}
              <td></td>
              {{/if}}
            {{/each}}
            </tr>
          {{/each}}
          </table>
        </div>
      </div>
    </div>
  </div>
  </script>

  <script id="verify-tmpl" type="text/x-handlebars-template">
  <div class="page verify-page hide" id="app-verify">
    <div class="verify-actions">
      <div class="identity">
        <img class="avatar" alt="" width="40" height="40" src="" />
        <!--img class="provider" alt="" width="18" height="18" src="" /-->
        <span class="name"></span>
      </div>
      <div class="done-info">
        <span class="status">Verification succeeded.</span>
        <span class="redirecting hide">Redirecting to app in <span class="sec">0</span>s.</span>
      </div>
    </div>
  </div>
  </script>

  <script id="setpassword-tmpl" type="text/x-handlebars-template">
  <div class="page setpassword-page hide" id="app-setpassword">
    <div class="verify-actions">
      <div class="user-form">
        <div class="identity">
          <img class="avatar" alt="" width="40" height="40" src="" />
          <div class="name"></div>
        </div>
        <div class="password">
          <div class="lock"></div>
          <input type="password" id="password" placeholder="Set EXFE Password" />
          <div class="pass"></div>
        </div>
      </div>
      <div class="set-button">
        <button class="btn-done">Done</button>
      </div>
      <div class="error-info hide" style="margin-top: 10px"></div>
      <div class="done-info hide">
        <span class="status">Password set successfully.</span>
        <span class="redirecting">Redirecting to app in <span class="sec">0</span>s.</span>
      </div>
    </div>
  </div>
  </script>

  <script id="live-tmpl" type="text/x-handlebars-template">
  <div class="page live-page hide" id="app-live">

    <div class="live-form">
      <div id="live-discover" class="discover">Discover people nearby and share contacts.</div>

      <div id="icard" class="card">
        <div class="avatar"></div>
      </div>

      <div class="card-form" id="card-form">
        <!--form class="form-horizontal"><fieldset-->
        <div class="controls" style="-webkit-transform: matrix3d(1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 1, 1); -webkit-backface-visibility: hidden;">
          <input class="input-item" type="email" autocapitalize="none" tabindex="1" id="card-name" placeholder="Your email or mobile no."/>
          <button class="btn btn-start" type="button">Live</button>
        </div>
        <div class="hide" id="card-bio"></div>
        <div class="identities">
          <ul class="list"></ul>
          <input class="input-item hide" id="add-identity" autocapitalize="none" data-status="1" type="email" placeholder="Add email or mobile no." />
          <div class="identity facebook-identity hide" id="add-identity-facebook">
            <label id="facebook-label" for="facebook-identity">
              facebook.com/<input name="facebook-identity" autocapitalize="off" id="facebook-identity" class="input-item" type="text" />
            </label>
          </div>
          <div class="detail detail-invent hide">The best way to predict the future is to invent it.</div>
          <div class="detail detail-concat">*People nearby can see your profile.</div>
        </div>
        <!--/fieldset></form-->
      </div>
    </div>

    <div class="live-gather hide">

      <div class="live-title">
        <div class="back"><img width="20" height="44" src="/static/img/back@2x.png" alt="" /></div>
        <h2>Live <span class="x">·X·</span></h2>
        <!--button class="btn btn-confirm hide" type="button">Contact</button-->
        <div id="live-tip" class="live-tip live-tip-close">
          <h4>Gather people nearby</h4>
          <p>Close two phones together to capture people using Live ·X·. For those accessing exfe.com, max their speaker volume.</p>
        </div>
        <div id="wave" class="wave">
          <div class="win">
            <div class="circle"></div>
            <div class="circle"></div>
            <div class="circle"></div>
          </div>
        </div>
      </div>

      <div id="card-tip" class="card-tip hidden">
        <div class="bio"></div>
        <ul></ul>
        <div class="ang"></div>
      </div>

    </div>

  </div>
  </script>

  <!-- live-form -->
  <script id="live-li-identity-tmpl" type="text/x-handlebars-template">
  <li class="identity">
    <span class="provider">{{provider_alias}}</span>
    <input data-name="{{identity.name}}" data-external-username="{{identity.external_username}}" data-provider="{{identity.provider}}" style="" autocapitalize="none" class="external_username input-item normal" value="{{{identity.external_username}}}" type="email"/>
    <div class="delete hidden"><div class="delete-x">x</div></div>
  </li>
  </script>

  <!-- live-gather' card -->
  <script id="live-card-tmpl" type="text/x-handlebars-template">
  <div data-url="{{card.avatar}}" id="{{card.id}}" data-g="{{g}}" data-i="{{i}}" class="card {{class}}" style="-webkit-transform: matrix3d({{matrix}});">
    <div class="avatar" style="background-image: url({{card.avatar}})"></div>
    <div class="name">{{card.name}}</div>
  </div>
  </script>
  <!--/Templates }}}-->

  <noscript>EXFE.COM can't load if JavaScript is disabled</noscript>
  <?php include 'share/footer_mobile.php'; ?>
  <script>
  (function () {
    var now = Date.now || function () { return new Date().getTime(); }
      , _ENV_ = window._ENV_
      , apiUrl = _ENV_.api_url
      , app_scheme = _ENV_.app_scheme
      , JSFILE = _ENV_.JSFILE
      , supportHistory = window.history
      , localStorage = window.localStorage
      , eventType = supportHistory ? 'popstate' : 'hashchange'
      , location = window.location
      , empty = function () {}
      , mframe = document.getElementById('mframe')
      , xframe = document.getElementById('xframe')
      , app_url = app_scheme + '://crosses/'
      , routes = {
          home: /^\/+(?:\?)?#{0,}$/,
          resolveToken: /^\/+(?:\?)?#token=([a-zA-Z0-9]{64})\/?$/,
          crossTokenForPhone: /^\/+(?:\?)?#!([1-9][0-9]*)\/([a-zA-Z0-9]{4})\/?$/,
          crossToken: /^\/+(?:\?)?#!token=([a-zA-Z0-9]{32})\/?$/
        }
      , startTime, currentTime, failTimeout;

    var failBack = function (cb) {
        failTimeout = setTimeout(function () {
          clearTimeout(failTimeout);
          currentTime = now();
          if (currentTime - startTime < 500) {
            if (cb) {
              cb();
            } else {
              window.location = '/';
            }
          }
        }, 400);
      },

      launchApp = function (url, cb) {
        startTime = now();
        xframe.src = url || app_url;
        failBack(cb);
      },

      checkNodeExsits = function (id) {
        var n = document.getElementById(id);
        return !!n;
      },

      injectScript = function (src, id, done, fail) {
        var n = document.createElement('script');
        n.src = src;
        n.id = id;
        n.async = true;
        n.onload = done || empty;
        n.onerror = fail || empty;
        document.body.appendChild(n);
      },

      injectCss = function (href, id) {
        var n = document.createElement('link');
        n.id = id;
        n.rel = 'stylesheet';
        n.media = 'screen';
        n.type = 'text/css';
        n.href = href;
        document.getElementsByTagName('head')[0].appendChild(n);
      },

      inject = function (cb) {
        var css = 'mobile-css';
        if (!checkNodeExsits(css)) {
          injectCss('/static/css/exfe_mobile.min.css', css);
        }
        var js = 'mobile-js';
        if (!checkNodeExsits(js)) {
          injectScript('/static/js/' + JSFILE, js, cb);
        } else {
          cb();
        }
      },

      handle = function () {
        inject(function () {
          mframe.className = 'hide';
          App.request.updateUrl();
          App.handle(App.request, App.response);
        });
      },

      serialize = function (data) {
        var d = [], k;
        for (k in data) {
          d.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k]));
        }
        return d.join('&').replace(/%20/g, '+');
      },

      request = function (opts) {
        var xhr = new XMLHttpRequest()
          , done = opts.done || empty
          , fail = opts.fail || empty
          , abortTimeout;

        xhr.open(opts.type || 'GET', opts.url, true);
        xhr.overrideMimeType('application/json');
        if ('GET' !== opts.type && opts.data) {
          xhr.setRequestHeader('Accept', 'application/json, text/javascript, */*; q=0.01')
          xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        }
        xhr.withCredentials = true;
        xhr.onreadystatechange = function () {
          if (xhr.readyState === 4) {
            xhr.onreadystatechange = void 0;
            clearTimeout(abortTimeout);
            var result, error = false
            if ((xhr.status >= 200 && xhr.status < 300) || xhr.status == 304 || (xhr.status == 0 && protocol == 'file:')) {
              try {
                result = JSON.parse(xhr.responseText);
              } catch (e) { error = e; }
              if (error) {
                fail(result, 'parsererror', xhr, opts);
              } else {
                done(result, xhr, opts);
              }
            } else {
              fail(result, xhr.status ? 'error' : 'abort', xhr, opts);
            }
          }
        };

        abortTimeout = setTimeout(function(){
          xhr.onreadystatechange = void 0;
          xhr.abort();
          xhr = void 0;
        }, 5000);

        xhr.send(opts.data ? serialize(opts.data) : null);

        return xhr;
      },

      crossCallback = function (response) {
        var cross = response.cross;
        // user_id
        var user_id = 0;
        // identity_id
        var myIdId = 0;
        var authorization = response.authorization;
        if (authorization) {
          user_id = authorization.user_id;
          if (response.browsing_identity) {
            myIdId = response.browsing_identity.id;
          }
        } else if (response.browsing_identity
          && response.browsing_identity.connected_user_id) {
          user_id = response.browsing_identity.connected_user_id;
          myIdId = response.browsing_identity.id;
        }
        var invitations = cross.exfee.invitations;
        for (var i = 0, len = invitations.length; i < len; ++i) {
          var invitation = invitations[i];
          if ((user_id && user_id === invitation.identity.connected_user_id)
                || myIdId === invitation.identity.id) {
            myIdId = invitation.identity.id;
          }
        }

        var args = cross.id || '', token = '';
        if (user_id) {
          if (authorization) {
            args += '?user_id='     + user_id
                  + '&token='       + authorization.token
                  + '&identity_id=' + myIdId;
            token = authorization.token;
          } else if (authorization = localStorage.getItem('authorization')) {
            authorization = JSON.parse(authorization);
            if (authorization.user_id === user_id) {
              args += '?user_id='     + user_id
                    + '&token='       + authorization.token
                    + '&identity_id=' + myIdId;
              token = authorization.token;
            }
          }
        }
        return args;
      },

      crossFunc = function (data) {
        request({
            url: apiUrl + '/Crosses/GetCrossByInvitationToken'
          , type: 'POST'
          , data: data
          , done: function (data) {
              _ENV_._data_ = data;
              if (data.meta && data.meta.code === 200) {
                launchApp(app_url + crossCallback(data.response), function () {
                  handle();
                });
              } else {
                window.location = '/';
              }
            }
          , fail: function (data) {
              _ENV_._data_ = data;
              window.location = '/';
            }
        });
      };

    var Director = function () {};

    Director.dispatch = function (url, e) {
      mframe.className = '';
      delete _ENV_._data_;
      var params;
      if (routes.home.test(url)) {
        handle();
      } else if ((params = url.match(routes.resolveToken))) {
        request({
            url: apiUrl + '/Users/ResolveToken'
          , type: 'POST'
          , data: { token :  params[1] }
          , done: function (data) {
              _ENV_._data_ = data;
              if (data.meta && data.meta.code === 200) {
                handle();
              } else {
                window.location = '/';
              }
            }
          , fail: function (data) {
              _ENV_._data_ = data;
              handle();
            }
        });
      } else if ((params = url.match(routes.crossTokenForPhone))) {
        var cross_id = params[1]
          , ctoken = params[2]
          , cats = localStorage.cats
          , token
          , data = {};

        if (cats) {
          cats = JSON.parse(cats);
        }

        data = {
          invitation_token: ctoken,
          cross_id: cross_id
        };

        if (cats && (token = cats[ctoken])) {
          data.cross_access_token = token;
        }

        crossFunc(data);

      } else if ((params = url.match(routes.crossToken))) {
        var ctoken = params[1]
          , cats = localStorage.cats
          , data = { invitation_token: ctoken }
          , token;

        if (cats && (token = cats[ctoken])) {
          data.cross_access_token = token;
        }

        crossFunc(data);

      } else {
        window.location = '/';
      }
    };

    Director.handle = function (e) {
      var url = location.hash;
      Director.dispatch('/' + url, e);
    };

    Director.start = function () {
      // mobile
      window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
          handle();
        }
      });

      document.addEventListener('webkitvisibilitychange', function(e) {
        if (document.webkitVisibilityState === 'visible') {
          handle();
        }
      });

      window.addEventListener(eventType, function (e) {
        Director.handle(e);
        e.stopPropagation()
        e.preventDefault()
        return false;
      });
    };

    Director.start();
  })();
  </script>
</body>
</html>
