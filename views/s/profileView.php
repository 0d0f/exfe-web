<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en" dir="ltr"> <!--<![endif]-->
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="author" content="EXFE Inc." />
  <meta name="robots" content="index, follow" />
  <meta name="keywords" content="EXFE, X, cross, exfee, gather, Gather a X, hangout, gathering, invite, RSVP" />
  <meta name="description" content="EXFE, an utility for hanging out with friends." />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no" />
  <title>Cross</title>
  <link href="/static/1b/css/exfe.min.css?t=<?php echo STATIC_CODE_TIMESTAMP; ?>" rel="stylesheet" type="text/css" />
  <script src="/static/1b/js/modernizr/2.5.3/modernizr.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
</head>
<body>

  <!-- NavBar -->
  <div class="navbar">

    <!-- navbar-bg -->
    <div class="navbar-bg"></div>
    <!-- /navbar-bg -->

    <!-- navbar-inner -->
    <div class="navbar-inner">
      <div class="container">

        <!--  EXFE LOGO -->
        <a href="/" class="brand"><img src="/static/1b/img/exfe-logo.png" width="140" height="50" alt="EXFE" /></a>

        <a href="#" class="version" data-widget="dialog" data-dialog-type="sandbox">SANDBOX</a>

        <div class="nav-collapse">
          <ul class="nav pull-right">
            <li class="dropdown">
              <div class="pull-left fill-left hide"></div>
              <div class="pull-right dropdown-wrapper">
                <a class="dropdown-toggle user-name" data-togge="dropdown" href="#" id="user-name"><span></span></a>
              </div>
            </li>
          </ul>
        </div>

      </div>
    </div>
    <!-- /navbar-inner -->

  </div>
  <!-- /NavBar -->

  <!-- container -->
  <div class="container" style="margin-top: 10px;">

    <div role="main">
      <section id="profile" class="x-profile">

        <div class="row settings-panel">
          <div class="pull-left user-avatar">
          </div>

          <div class="pull-right user-xstats">
            <div class="attended"></div>
            <div>
              <span class="x-sign">X</span> attended
            </div>
          </div>

          <div class="user-infos">
            <div class="user-name">
              <button class="xbtn xbtn-changepassword hide" data-widget="dialog" data-dialog-type="changepassword">Change Password...</button>
              <h3 class="pull-left"></h3>
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
                <a class="x-sign" href="http://itunes.apple.com/cn/app/exfe/id514026604?l=en&mt=8" target="_blank">EXFE</a> is ready for iPhone - instant, mobile.
              </div>
            </div>

          </div><!--}}}-->

        </div>

      </section>
    </div>

  </div>

  <!-- JS Templates -->
  <!--
  {{#if __default__}}<span class="default">default</span>{{/if}}
  -->
  <script id="jst-identity-list" type="text/x-handlebars-template">
  {{#each identities}}
  <li data-identity-id="{{id}}" {{#editable provider status}}class="editable"{{/editable}} draggable="true">
    <i class="icon-move"></i>
    <span class="avatar"><img src="{{avatarFilename avatar_filename}}" alt="" width="20" height="20" />
    </span><span class="username"><em>{{printName name external_id}}</em></span><span class="identity">{{atName provider external_id}}</span> <i class="icon16-identity-{{provider}}"></i>
    {{#makeDefault __default__ status}}<a class="makedefault" href="#">Make default</a>{{/makeDefault}}
    {{#ifOauthVerifying provider status}}
    <span class="xlabel">
      <i class="icon16-warning"></i>
      <span>Authorization failed.</span>
      <button class="xbtn xbtn-reauthorize hide" data-widget="dialog" data-dialog-type="verification_twitter">Re-Authorize</button>
    </span>
    {{/ifOauthVerifying}}
    {{#ifVerifying provider status}}
    <span class="xlabel">
      <i class="icon16-warning"></i>
      <span>Pending verification, 5 days left.</span>
      <button class="xbtn xbtn-reverify hide" data-identity-id="{{id}}" data-widget="dialog" data-dialog-type="verification_email">Re-Verify...</button>
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
        {{#unless totalCrosses}}
        <span class="message">Oh~ Your list is empty. </span>
        {{/unless}}
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

  <!-- JavaScript at the bottom for fast page loading -->
  <script src="/static/1b/js/common/0.0.1/common.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script><?php include 'share/ftconfig.php'; ?></script>

  <script src="/static/1b/js/class/0.0.1/class.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/emitter/0.0.1/emitter.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/base/0.0.1/base.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/widget/0.0.1/widget.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/bus/0.0.1/bus.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/rex/0.0.1/rex.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/util/0.0.1/util.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

  <script src="/static/1b/js/handlebars/1.0.0/handlebars.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/store/1.3.3/store.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/moment/1.6.2/moment.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/jquery/1.7.2/jquery.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/jqfocusend/0.0.1/jqfocusend.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/jqoffset/0.0.1/jqoffset.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

  <!--
  <script src="/static/1b/js/jqdndsortable/0.0.1/jqdndsortable.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  -->

  <script src="/static/1b/js/api/0.0.1/api.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/dialog/0.0.1/dialog.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/typeahead/0.0.1/typeahead.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

  <script src="/static/1b/js/xidentity/0.0.1/xidentity.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/xdialog/0.0.1/xdialog.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

  <script src="/static/1b/js/filehtml5/0.0.1/filehtml5.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/uploader/0.0.1/uploader.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <!--
  <script src="/static/1b/js/editable/0.0.1/editable.js"></script>
  <script src="/static/1b/js/xeditable/0.0.1/xeditable.js"></script>
  -->

  <script src="/static/1b/js/profile/0.0.1/profile.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/userpanel/0.0.1/userpanel.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

</body>
</html>
