    <div id="global_header">
        <div class="logo">
            <a href="/"><img src="/static/images/exfe_logo.png" alt="EXFE" title="EXFE.COM" /></a>
        </div>
        <div class="user_info" id="global_user_info">
            <div class="global_sign_in_btn">
                <a id="global_user_login_btn" href="javascript:void(0);">Sign In</a>
            </div>
        </div>
    </div>
    <img src="/static/images/user.png" style="display:none;" width="0" height="0" />
    <script type="text/javascript">
        //If not login page
        if(typeof showIdentificationDialog == "undefined"){
            jQuery("#global_user_login_btn").unbind("click");
            jQuery("#global_user_login_btn").bind("click",function(){
                odof.user.status.doShowLoginDialog();
            });
        }else{
            jQuery("#global_user_login_btn").unbind("click");
            jQuery("#global_user_login_btn").click(function() {
                jQuery("#identity").focus();
            });
        }
    </script>
