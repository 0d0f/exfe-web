<?php include "share/header_mobile.php" ?>
</head>
<body>
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
          <div class="action subscribe hide">Subscribe to updates and engage in:
            <div class="subscribe-frame">
              <input type="text" class="email" id="email" placeholder="Enter your email">
              <button class="btn_mail">OK</button>
            </div>
          </div>
          <div class="action error-info hide">You just opened an invalid link.</div>
          <div class="action get-button hide">
            <button>Get <span class="exfe">EXFE</span> app <span class="free">free</span></button>
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
          <p>A utility for gathering with friends.</p>
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
    <div class="cross redirecting">Redirecting to <span class="exfe_blue3">EXFE</span> app in <span class="sec">0</span>s.</div>
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
              <td class="rsvp changename" width="98">Change my display name</td>
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
    <!--footer>
      <div class="footer-wrap">
        <div class="footer_frame">
          <div class="actions" id="cross_actions">
            <div class="subscribe">Subscribe to updates and engage in:
              <div class="subscribe-frame">
                <input type="text" class="email" id="email" placeholder="Enter your email">
                <button class="btn_mail">OK</button>
              </div>
            </div>
            <div class="get-button">
              <button class="btn_w">Get <span class="exfe">EXFE</span> app <span class="free">free</span></button>
            </div>
            <div class="web-version"><span class="underline">Proceed</span> with desktop web version.</div>
          </div>
        </div>
      </div>
    </footer-->
  </div>
  </script>

  <script id="verify-tmpl" type="text/x-handlebars-template">
  <div class="page setpassword-page hide" id="app-setpassword">
    <div class="verify-actions">
      <div class="identity">
        <img class="avatar" alt="" width="40" height="40" src="" />
        <img class="provider" alt="" width="18" height="18" src="" />
        <span class="name"></span>
      </div>
    </div>
    <div class="done-info hide">
      <span class="status">Verification succeeded.</span>
      <span class="redirecting">Redirecting to app in <span class="sec">5</span>s.</span>
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
</body>
</html>
