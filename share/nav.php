    <div id="global_header">
        <div class="nav-bkg"></div>
        <div class="site-infos">
        <div class="logo">
<?php
if (intval($_SESSION["userid"]) > 0) {
?>
<a href="/s/profile">
<?php
} else {
?>
<a href='/'>
<?php } ?>
<img src="/static/images/exfe_logo.png" alt="EXFE" title="EXFE.COM" /></a>
    <div id="exfe_version" class="version"><a href="javascript:;">SANDBOX</a></div>
        </div>
        <div class="user_info" id="global_user_info">
            <div class="global_sign_in_btn">
                <a id="global_user_login_btn" href="javascript:void(0);">Sign in</a>
            </div>
        </div>
    </div>
    </div>
    <img src="/static/images/user.png" style="display:none;" width="0" height="0" />
    <script>
        if (typeof showSpecialIdentityDialog == "undefined") {
            jQuery("#global_user_login_btn").unbind("click");
            jQuery("#global_user_login_btn").bind("click",function() {
                odof.user.status.doShowLoginDialog();
            });
        } else {
            jQuery("#global_user_login_btn").unbind("click");
            jQuery("#global_user_login_btn").click(function() {
                jQuery("#identity").focus();
            });
        }
        $(document).delegate('div#exfe_version', 'click', function (e) {
            odof.user.status.showExfeVersion();
        });
    </script>
