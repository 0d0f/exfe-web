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
            <li id="js-signin" style="display: none;">
              <a class="sign-in" href="#" data-widget="dialog" data-dialog-type="identification" data-dialog-tab="d00">Sign In</a>
            </li>
            <li class="dropdown" style="display: none;">
              <div class="pull-left fill-left hide"></div>
              <div class="pull-right dropdown-wrapper">
                <a class="dropdown-toggle user-name" data-togge="dropdown" href="/s/profile" id="user-name"><span></span></a>
              </div>
            </li>
          </ul>
        </div>

      </div>
    </div>
    <!-- /navbar-inner -->

  </div>
  <!-- /NavBar -->

  <!-- JavaScript at the bottom for fast page loading -->
  <script src="/static/1b/js/common/0.0.1/common.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script><?php include 'ftconfig.php'; ?></script>

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
  <script src="/static/1b/js/jquery/1.7.2/jquery.js"></script>
  <script src="/static/1b/js/jqfocusend/0.0.1/jqfocusend.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

  <!--
  <script src="/static/1b/js/jqdndsortable/0.0.1/jqdndsortable.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  -->

  <script src="/static/1b/js/api/0.0.1/api.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/dialog/0.0.1/dialog.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/typeahead/0.0.1/typeahead.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

  <script src="/static/1b/js/xidentity/0.0.1/xidentity.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
  <script src="/static/1b/js/xdialog/0.0.1/xdialog.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

  <!--
  <script src="/static/1b/js/editable/0.0.1/editable.js"></script>
  <script src="/static/1b/js/xeditable/0.0.1/xeditable.js"></script>
  <script src="/static/1b/js/profile/0.0.1/profile.js"></script>
  -->

  <!-- cross -->
  <script>
    define(function (require) {
      var Bus = require('bus');
      Bus.on('app:crossdata', function (token, status) {
        if (/![a-zA-Z0-9]+\?token=/.test(window.location.href)) {
          odof.x.edit.setreadonly = function () {
            $('.user-panel .xbtn-signin').trigger('click');
          };
        }
      });
    });
  </script>

  <script src="/static/1b/js/userpanel/0.0.1/userpanel.js?t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
