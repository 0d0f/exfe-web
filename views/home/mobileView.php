<?php
  $frontConfigData = json_decode(file_get_contents('static/package.json'));
  if (!$frontConfigData) {
      header('location: /500');
      return;
  }

  include "share/header_mobile.php";
?>
  <style>.hide { display: none; }</style>
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
          <div class="action subscribe hide">
            <div class="subscribe-title">Subscribe to updates and engage in:</div>
            <div class="subscribe-frame">
              <input type="text" class="email" id="email" placeholder="Enter your email">
              <button class="btn_mail">OK</button>
            </div>
          </div>
          <div class="action error-info hide"></div>
          <div class="action get-button">
            <button>Open <span class="exfe">EXFE</span> app</button>
          </div>
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
              <img class="place_mark" alt="" src="http://img.exfe.com/web/map_mark_diamond_blue@2x.png" />
            </div>
          </a>
          {{/if}}
        </div>
        {{#unless read_only}}
        <div class="rsvp_toolbar {{#if hide_rsvp}}rsvp_toolbar_off{{/if}}">
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
      <div class="error-info hide" style="margin-top: 10px;"><span class="t">Token expired.</span> Please request to reset password again.</div>
      <div class="done-info hide"><span class="status">Password set successfully.</span></div>
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

  <!-- Route-X -->
  <script id="routex-tmpl" type="text/x-handlebars-template">
    <div id="routex">
      <div id="map"></div>
      <!--svg xmlns="http://www.w3.org/2000/svg" version="1.1" id="svg"></svg-->
      <canvas id="canvas"></canvas>
      <div id="identities-overlay">
        <div id="isme" class="identity">
          <div class="abg"><img src="" alt="" class="avatar"><div class="avatar-wrapper"></div></div>
          <div class="detial unknown">
            <i class="icon icon-dot-grey"></i>
            <span class="distance">未知方位</span>
          </div>
        </div>
        <div id="identities"></div>
      </div>
      <div id="my-info" class="info-windown hide">
        <div class="splitline show">秀这张“活点地图”</div>
        <div class="splitline discover">发现更多…</div>
      </div>
      <div id="other-info" class="info-windown hide">
        <div class="splitline info">
          <div class="name"></div>
          <div class="update hide"><span class="time"></span>前所处方位</div>
          <table border="0" cellpadding="0" cellspacing="0">
            <tr class="dest hide"><td class="label">至目的地</td><td class="m"></td></tr>
            <tr class="dest-me hide"><td class="label">距您的位置</td><td class="m"></td></tr>
          </table>
        </div>
        <div class="splitline please-update hide">请对方更新方位<div class="loading"><div class="spinner"><div class="mask"><div class="maskedCircle"></div></div></div></div></div>
      </div>
      <div id="open-exfe"><div class="btn-openexfe"></div></div>
      <div id="locate" class="load">
        <div class="btn-locate">
          <div class="loading"><div class="spinner"><div class="mask"><div class="maskedCircle"></div></div></div></div>
        </div>
      </div>
      <div id="privacy-dialog" class="dialog hide">
        <div class="main">
          <h3>隐私至关重要</h3>
          <p class="p0">您刚刚拒绝开启这张“活点地图”：Threshold of the odyssey。它将不会展现您的位置，您也无法用它看到别人的位置。但这不会影响您已开启的其它“活点地图”页面，每张地图中是否展现您的位置是各自独立的设置。 </p>
          <p class="p1">像“活点地图”这样能获知您方位的工具，应以最高标准尊重个人隐私和数据安全。我们对此非常重视。</p>
          <div class="btn">
            <div class="tip">要改变主意与朋友们互相看到位置和轨迹？</div>
            <button id="turn-on">开启这张活点地图</button>
          </div>
        </div>
      </div>
      <div id="wechat-guide" class="dialog wechat-dialog hide">
        <div class="main">
          <div class="ibox">
            <p><img src="/static/img/wechatbtn_accverified@2x.png" alt="" width="40" height="40" align="right" />请从这里找到右侧 图标“查看公众号”。</p>
            <p><span>关注</span>服务号以便正常使用，也方便您及时收到朋友们的邀请和提醒通知。</p>
          </div>
        </div>
      </div>
      <div id="wechat-share" class="dialog wechat-dialog hide">
        <div class="main">
          <div class="ibox">
            <p><img src="/static/img/wechatbtn_sendmsg@2x.png" alt="" width="40" height="40" align="right" />请从这里找到右侧 图标“发送给朋友”， 复制粘贴下面消息发送。</p>
            <p><div class="share-input" contenteditable="true"></div><div class="share-app">抱歉受微信限制操作繁琐  <span class="open-app">请用<span class="shuady">水滴·汇</span>应用</span></div></p>
          </div>
        </div>
      </div>
    </div>
  </script>

  <!-- Wechat About -->
  <script id="wechat-about-tmpl" type="text/x-handlebars-template">
      <div id="shuidi-dialog" class="dialog">
        <div class="main">
          <h3 class="title">在“活点地图”上绘制路径?</h3>
          <p class="desc"><span class="name">水滴·汇</span> (Shuady ·X·) 是一个群组工具，它能在“活点地图”上作标记绘制路径，还有更多便捷有趣的实用功能，助您组织群组活动。</p>
          <div class="app-btn">
            <img class="app-icon" src="/static/img/exfe_512.png" alt="" width="60" height="60" />
            <div class="app-info">
              <h3 class="app-title">水滴·汇</h3>
              <div class="app-keywords">Shuady ·X·</div>
            </div>
          </div>
          <div class="notify">
            <div class="notify-title">请输入资料以便朋友们向您发送提醒：</div>
            <div class="notify-frame">
              <input type="text" class="email" id="notify-provider" placeholder="您的手机号或电子邮件" />
              <button class="notify-ok">确定</button>
            </div>
          </div>
          <div id="cleanup-cache">重启活点地图</div>
        </div>
      </div>
  </script>

  <!--/Templates }}}-->

  <noscript>EXFE.COM can't load if JavaScript is disabled</noscript>
  <?php include 'share/footer_mobile.php'; ?>
</body>
</html>
