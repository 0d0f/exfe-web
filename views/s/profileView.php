<?php include "share/header.php" ?>
</head>
<body>
  <?php include "share/nav.php" ?>

  <!-- Container -->
  <div class="container" style="margin-top: 10px;">

    <div role="main">
      <section id="profile" class="x-profile">

        <div class="row settings-panel">
          <div class="pull-left user-avatar editable">
          </div>

          <div class="pull-right user-xstats">
            <div class="attended"></div>
            <div>
              <span class="x-sign">X</span> attended
            </div>
          </div>

          <div class="user-infos">
            <div class="user-name">
              <h3 class="pull-left"></h3>
              <div class="xbtn changepassword" data-widget="dialog" data-dialog-type="changepassword"><i class="icon14-lock"></i><span>Change Password...</span></div>
            </div>
            <ul class="unstyled identity-list">
            </ul>

          </div>
          <!--
          <button class="pull-right xbtn xbtn-addidentity" data-widget="dialog" data-dialog-type="addidentity">Add Identity…</button>
          -->

          <div class="pull-right identities-trash hide">
            <div class="pull-right trash-message">
              <span class="draged">Drop here to remove identity</span>
              <span class="removed">Remove Identity</span>
            </div>
            <i class="icon-trash"></i>
            <div class="trash-overlay"></div>
          </div>

        </div>

        <div class="row">
          <div class="gr-a">
            <div class="crosses">

            </div>

          </div>

          <div class="gr-b"><!--{{{-->

            <div class="siderbar invitations hide">
              <h3><i class="icon16-invitation"></i>Invitations</h3>
            </div>

            <div class="ios-app hide">
              <a class="pull-right exfe-dismiss" href="#">Dismiss</a>
              <div>
                <a class="x-sign" href="http://itunes.apple.com/app/exfe/id514026604" target="_blank">EXFE</a> is ready for iPhone - instant, mobile.
              </div>
            </div>

          </div><!--}}}-->

        </div>

      </section>
    </div>

  </div>
  <!--/Container -->

  <!-- JS Templates -->
  <!--
  {{#if __default__}}<span class="default">default</span>{{/if}}
  -->
  <script id="jst-identity-list" type="text/x-handlebars-template">
  {{#each identities}}
  <li data-identity-id="{{id}}" {{#editable provider status}}class="editable"{{/editable}} draggable="true">
    <i class="icon-move"></i>
    <span class="avatar"><img src="{{avatarFilename avatar_filename}}" alt="" width="20" height="20" />
    </span><span class="identity"><span class="identityname"><em>{{printName name external_id}}</em></span><span class="external">{{atName provider external_id}}</span> <i class="icon16-identity-{{provider}}"></i></span>
    {{#makeDefault __default__ status}}<a class="makedefault" href="#">Make default</a>{{/makeDefault}}
    {{#ifOauthVerifying provider status}}
    <span class="xlabel">
      <i class="icon16-warning"></i>
      <span>Authorization failed.</span>
      <button class="xbtn xbtn-reauthorize" data-widget="dialog" data-dialog-type="verification_twitter">Re-Authorize</button>
    </span>
    {{/ifOauthVerifying}}
    {{#ifVerifying provider status}}
    <span class="xlabel">
      <i class="icon16-warning"></i>
      <span>Pending verification, 5 days left.</span>
      <button class="xbtn xbtn-reverify" data-identity-id="{{id}}" data-widget="dialog" data-dialog-type="verification_email">Re-Verify...</button>
    </span>
    {{/ifVerifying}}
  </li>
  {{/each}}
  </script>
  <script id="jst-user-avatar" type="text/x-handlebars-template">
  {{#if avatar_filename}}
  <span class="avatar"><img src="{{avatar_filename}}" alt="" width="80" height="80" /></span>
  {{else}}
  <div class="add-avatar">
    <span class="plus">+</span>
    <span class="portrait">Portrait</span>
  </div>
  {{/if}}
  </script>
  <script id="jst-crosses-container" type="text/x-handlebars-template">
  <div class="clearfix crosses-container">
    <div class="pull-right cross-type">
      <span>{{cate_date}}</span>
      <span class="arrow rb"></span>
    </div>
    <div class="cross-list">
      {{#crosses}}
      {{> jst-cross-box}}
      {{/crosses}}
    </div>

    {{#if hasGatherAX}}
    <div class="gatherax-box">
      <a href="/x/gather">
        {{#if totalCrosses}}
        <i class="icon16-invitation"></i>
        {{else}}
        <span class="message">Oh~ Nothing upcoming. <em class="x-sign">EXFE</em> your friends, </span>
        {{/if}}
        <span class="underline">Gather a <em class="x-sign">X</em></span>
      </a>
    </div>
    {{/if}}

    {{#if hasMore}}
    <div class="more" data-cate="{{cate}}">
      <a href="#">more...</a>
    </div>
    {{/if}}
  </div>
  </script>
  <script id="jst-cross-box" type="text/x-handlebars-template">
  <div class="cross-box">
  <a href="/!{{id}}" data-id="{{id}}">
    <h5>{{title}}</h5>
    <time>{{printTime time}}</time>
    <address>{{place.title}} 
    {{#if place.description}}
      <span class="gray">({{place.description}})</span>
    {{/if}}
    </address>
    <div><span>{{{confirmed_nums exfee.invitations}}}</span> 
      <span class="gray">of</span> 
      {{{total exfee.invitations}}} 
      <span class="gray">accepted</span>: <span>{{{confirmed_identities exfee.invitations}}}</span>
    </div>
  </a>
  </div>
  </script>
  <script id="jst-invitations" type="text/x-handlebars-template">
    <div class="cross-list">
      {{#crosses}}
      <div class="cross-box">
        <a href="/!{{crossItem "id"}}" data-exfeeid="{{crossItem "exfeeid"}}" data-invitationid="{{crossItem "invitationid"}}" data-id="{{crossItem "id"}}">
          <h5>{{crossItem "title"}}</h5>
          <div>{{printTime2 "time"}}{{#ifPlace}} <span class="gray">at</span> {{crossItem "place"}}{{/ifPlace}} <span class="gray">by</span> {{by_identity.name}}</div>
          <div class="xbtn xbtn-accept">Accept</div>
        </a>
      </div>
      {{/crosses}}
    </div>
  </script>
  <script id="jst-updates" type="text/x-handlebars-template">
  {{#if updates}}
  <div class="siderbar updates">
    <h3><i class="icon16-updates"></i>Recent updates</h3>
    <div class="cross-list">
      {{#each updates}}
      {{#if updated}}
      <div class="cross-box">
        <a href="/!{{id}}" data-id="{{id}}">
          <time class="pull-right">{{printTime3 time}}</time>
          <h5>{{title}}</h5>

          {{#if updated.time}}
          <div><i class="icon-time"></i> {{printTime4 time}}</div>
          {{/if}}

          {{#if updated.place}}
          <div><i class="icon-place"></i> {{place.description}}</div>
          {{/if}}

          {{#if updated.title}}
          <div><i class="icon-cross"></i> {{title}}</div>
          {{/if}}

          {{#if updated.exfee}}
          {{{rsvpAction exfee.invitations updated.exfee.identity_id}}}
          {{/if}}

          {{#if updated.conversation.item}}
          {{#with updated.conversation.item}}
          <div><span class="blue">{{by_identity.name}}</span>:
          {{content}}</div>
          <div><i class="icon-conversation"></i> <span class="blue">{{__conversation_nums}}</span>
            new post in conversation.
          </div>
          {{/with}}
          {{/if}}

        </a>
      </div>
      {{/if}}
      {{/each}}
    </div>
  </div>
  {{/if}}
  </script>
  <?php include 'share/footer.php'; ?>
  <script src="/static/js/filehtml5/0.0.1/filehtml5.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/js/uploader/0.0.1/uploader.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/js/profile/0.0.1/profile.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/js/userpanel/0.0.1/userpanel.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

</body>
</html>
