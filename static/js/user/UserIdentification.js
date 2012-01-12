/**
 * @Description:    user login module
 * @createDate:     Sup 23,2011
 * @CopyRights:		http://www.exfe.com
 **/
var moduleNameSpace = "odof.user.identification";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){

    ns.actions = "sign_in";
    ns.userIdentityCache = "";
    ns.userManualVerifyIdentityCache = "";
    ns.specialDomain = ["facebook", "twitter", "google"];
    ns.inentityInputIntervalHandler = null;

    ns.getUrlVars = function() {
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for(var i = 0; i < hashes.length; i++)
        {
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
    };

    ns.showIdentityInfo = function(){
        if(jQuery("#identity").val()){
            jQuery("#identity_dbox").html('');
        }else{
            jQuery("#identity_dbox").html('Your email here');
        }
    };

    ns.showRegisteMsg = function(){
        jQuery("#identity_register_msg").show();
        jQuery("#close_reg_msg_btn").bind("click",function(){
            ns.hideRegisteMsg();
        });
    };

    ns.hideRegisteMsg = function(){
        jQuery("#identity_register_msg").hide();
        jQuery("#close_reg_msg_btn").unbind("click");
    }

    ns.createDialogDomCode = function(type) {
        var title="", form="";
        if(type == "reg_login") {
            title="Identification";
            desc = "<div class='dialog_titles' style='height:110px;'>"
                + "<p style='height:35px;line-height:18px'>Welcome to <span style='color:#0591AC;'>EXFE</p>" 
                + "<p class='oauth_title'>Authorize with your <br/> existing identity:</p>"
                + "<p class='oauth_icon'>"
                + "<a href='/oAuth/loginWithFacebook' class='facebook_oauth' alt='FaceBookOAuth'></a>"
                + "<a href='/oAuth/twitterRedirect' class='twitter_oauth' alt='TwitterOAuth'></a>"
                + "<a href='/oAuth/loginWithGoogle' class='google_oauth' alt='GoogleOAuth'></a>"
                + "</p>"
                + "</div>";
            form = "<div id='identity_reg_login_dialog' class='identity_dialog_main'>"
                 + desc
                 + "<div id='identification_title_msg'>Enter identity information:</div>"
                 + "<form id='identificationform' accept-charset='UTF-8' action='' method='post'>"
                 + "<ul>"
                 + "<li><label class='title'>Identity:</label>"
                 + "<div class='identity_box'>"
                 + "<input id='identity' name='identity' type='text' class='inputText' autocomplete='off' disableautocomplete='' />"
                 + "</div>"
                 + "<div class='account_hint_list' id='account_hint_list' style='display:none;'>"
                 + "<ul>"
                 + "<li class='facebook' id='facebook'><span id='facebook_name'></span>@Facebook</li>"
                 + "<li class='twitter' id='twitter'><span id='twitter_name'></span>@Twitter</li>"
                 + "<li class='google' id='google'><span id='google_name'></span>@Google</li>"
                 + "</ul>"
                 + "</div>"
                 + "<div id='identity_dbox'>Your email here</div>"
                 + "<em class='loading' id='identity_verify_loading' style='display:none;'></em>"
                 + "<em class='delete' id='delete_identity' style='display:none;'></em>"
                 + "<img class='avatar' id='user_avatar' style='display:none;' src='' />"
                 + "</li>"
                 + "<li id='displayname' style='display:none'>"
                 + "<label class='title' style='color:#CC3333'>Display name:</label>"
                 + "<input type='text' name='displayname' class='inputText'/>"
                 + "</li>"
                 + "<li><label class='title'>Password:</label>"
                 + "<input type='password' id='identification_pwd' name='password' class='inputText' />"
                 + "<input type='text' id='identification_pwd_a' class='inputText' style='display:none;' />"
                 + "<em class='ic3' id='identification_pwd_ic'></em>"
                 + "</li>"
                 + "<li id='identification_rpwd_li' style='display:none'>"
                 + "<label class='title'>Re-type:</label>"
                 + "<input type='password' id='identification_rpwd' name='retypepassword' class='inputText' />"
                 + "<em id='pwd_match_error' class='warning' style='display:none;'></em>"
                 + "</li>"
                 + "<li id='pwd_hint' style='display:none' class='notice'><span>check password</span></li>"
                 + "<li id='login_hint' style='display:none' class='notice'><span>Incorrect identity or password</span></li>"
                 + "<li class='logincheck' id='logincheck' style='display:none;'>"
                 + "<input type='checkbox' value='1' name='auto_signin' id='auto_signin' checked />"
                 + "<label for='auto_signin' style='cursor:pointer;'><span>Sign in automatically</span></label>"
                 + "</li>"
                 + "</ul>"
                 + "<div class='identification_bottom_btn'>"
                 + "<a id='forgot_password' class='forgot_password' style='display:none;'>Forgot Password...</a>"
                 + "<a href='#' id='sign_up_btn' class='sign_up_btn'>Sign Up?</a>&nbsp;&nbsp;"
                 + "<a id='startover' class='startover' style='display:none;'>Start Over</a>"
                 + "<input type='submit' value='Sign in' id='sign_in_btn' class='sign_in_btn_disabled' disabled='disabled' />"
                 //+ "<input type='submit' value='Sign in' id='sign_in_btn' class='sign_in_btn' /></li>"
                 + "</div>"
                 + "</form>"
                 + "</div>";
        } else if( type=="reset_pwd" ){ //重置密码。
            title = "Set Password";
            form = "<div id='identity_set_pwd_dialog' class='identity_dialog_main'>"
                 + "<div id='set_password_titles' class='dialog_titles'>Set Password</div>"
                 + "<div id='set_password_desc'>"
                 + "Please set password for your identity."
                 + "</div>"
                 + "<form id='reset_pwd_form' accept-charset='UTF-8' action='' method='post'>"
                 + "<ul>"
                 + "<li><label class='title'>Identity:</label>"
                 + "<input type='text' id='show_identity_box' class='inputText' disabled='disabled' />"
                 + "</li>"
                 + "<li>"
                 + "<label class='title'>Display name:</label>"
                 + "<input  type='text'  name='displayname' class='inputText' id='user_display_name' />"
                 + "<em id='displayname_error' class='warning' style='display:none;'></em>"
                 + "</li>"
                 + "<li><label class='title'>Password:</label>"
                 + "<input type='password' id='identification_pwd' name='password' class='inputText' />"
                 + "<input type='text' id='identification_pwd_a' class='inputText' style='display:none;' />"
                 + "<input type='hidden' id='identification_user_token' value='' />"
                 + "<em class='ic3' id='identification_pwd_ic'></em>"
                 + "</li>"
                 /*
                 + "<li id='identification_repwd_li' style='display:none;'>"
                 + "<label class='title'>Re-type:</label>"
                 + "<input type='password' id='identification_repwd' name='repassword' class='inputText' />"
                 + "<em id='pwd_match_error' class='warning' style='display:none;'></em>"
                 + "</li>"
                 */
                 + "<li id='pwd_hint' style='display:none' class='notice'><span>check password</span></li>"
                 + "<li id='reset_pwd_error_msg' style='padding-left:118px; color:#FD6311; display:none;'></li>"
                 + "</ul>"
                 + "<div class='identification_bottom_btn'>"
                 + "<a id='set_passwprd_discard' style='display:none;' href='javascript:void(0);'>Discard</a>&nbsp;&nbsp;"
                 + "<input type='submit' value='Done' class='btn_85' id='submit_reset_password' style='cursor:pointer;' />"
                 + "</div>"
                 + "</form>"
                 + "</div>";
        } else if(type == "change_pwd"){ //用户修改密码，从Profile页面触发。
            title = "Change Password";
            form = "<div id='identity_change_pwd_dialog' class='identity_dialog_main'>"
                 + "<div id='set_password_titles' class='dialog_titles'>Change Password</div>"
                 + "<div id='set_password_desc' style='height:45px;line-height:18px;'>"
                 + "Please enter current password and set new password."
                 + "</div>"
                 + "<form id='change_pwd_form' accept-charset='UTF-8' action='' method='post'>"
                 + "<ul>"
                 + "<li><label class='title'>Name:</label>"
                 + "<input type='text' id='show_username_box' class='inputText' disabled='disabled' />"
                 + "</li>"
                 + "<li>"
                 + "<label class='title'>Password:</label>"
                 + "<input type='password' name='o_pwd' id='o_pwd' class='inputText' />"
                 + "<input  type='text'  name='o_pwd_a' id='o_pwd_a' class='inputText' style='display:none;' />"
                 + "<em class='ic3' id='o_pwd_ic'></em>"
                 + "</li>"
                 + "<li><label class='title'>New password:</label>"
                 + "<input type='password' name='new_pwd' id='new_pwd' class='inputText' />"
                 + "<input type='text' name='new_pwd_a' id='new_pwd_a' class='inputText' style='display:none;' />"
                 + "<em class='ic3' id='new_pwd_ic'></em>"
                 + "</li>"
                 /*
                 + "<li id='re_new_pwd_li' style='display:block;'>"
                 + "<label class='title'>Re-type new:</label>"
                 + "<input type='password' name='re_new_pwd' id='re_new_pwd' class='inputText' />"
                 + "</li>"
                 */
                 + "<li id='change_pwd_error_msg' style='padding-left:118px; color:#FD6311; display:none;'></li>"
                 + "</ul>"
                 + "<div class='identification_bottom_btn' style='text-align:right;'>"
                 //+ "<a id='forgot_password' class='forgot_password'>Forgot Password...</a>"
                 + "<a id='change_pwd_discard' href='javascript:void(0);'>Discard</a>&nbsp;&nbsp;"
                 + "<input type='submit' value='Done' class='btn_85' id='submit_change_password' style='cursor:pointer;' />"
                 + "</div>"
                 + "</form>"
                 + "</div>";
        } else if(type == "add_identity") {
            title="Add Identity";
            desc = "<div class='dialog_titles' style='height:110px;'>"
                 + "<p style='height:35px;line-height:18px'>Welcome to <span style='color:#0591AC;'>EXFE</p>" 
                 + "<p class='oauth_title'>Authorize with your <br/> existing identity:</p>"
                 + "<p class='oauth_icon'>"
                 + "<a href='/oAuth/loginWithFacebook' class='facebook_oauth' alt='FaceBookOAuth'></a>"
                 + "<a href='/oAuth/twitterRedirect' class='twitter_oauth' alt='TwitterOAuth'></a>"
                 + "<a href='/oAuth/loginWithGoogle' class='google_oauth' alt='GoogleOAuth'></a>"
                 + "</p>"
                 + "</div>";
            form = "<div id='add_identity_dialog' class='identity_dialog_main'>"
                 + desc
                 + "<div id='identification_title_msg' style='display:none;'>Enter identity information:</div>"
                 + "<form id='addIdentityForm' accept-charset='UTF-8' action='' method='post'>"
                 + "<ul>"
                 + "<li style='padding-top:20px;'><label class='title'>Identity:</label>"
                 + "<div class='identity_box'>"
                 + "<input id='identity' name='identity' type='text' class='inputText' />"
                 + "</div>"
                 + "<div id='identity_dbox'>Your email here</div>"
                 + "<em class='loading' id='identity_verify_loading' style='display:none;'></em>"
                 + "<img class='avatar' id='user_avatar' style='display:none;' src='' />"
                 + "</li>"
                 + "</ul>"
                 + "<div class='identification_bottom_btn'>"
                 + "<input type='submit' value='Add' id='add_identity_btn' class='sign_in_btn' />"
                 + "</div>"
                 + "</form>"
                 + "</div>";
        }


        //新的找回密码对话框。用户点击Forgot Password进去。
        var forgot_verification = "<div id='forgot_verification_dialog' class='identity_visual_dialog' style='display:none;'>"
               + "<div style='text-align:center; height:45px; font-size:18px;'>Forgot password</div>"
               + "<div style='height:25px; text-align:left;'>"
               + "A verification will be sent to your identity:"
               + "</div>"
               + "<div style='height:40px;'>"
               + "<input type='text' id='forgot_identity_input' disabled='disabled' />"
               + "</div>"
               + "<div id='forgot_verification_msg' style='height:40px; text-align:left;'>"
               + "Confirm sending verification to your mailbox?"
               + "</div>"
               + "<div class='float_panel_bottom_btn' style='text-align:right;'>"
               + "<a href='javascript:void(0);' id='cancel_forgot_verify_btn'>Cancel</a>&nbsp;&nbsp;"
               + "<input type='button' id='fogot_verify_btn' value='Verify' />"
               + "</div>"
               + "</div>";

        //需要验证的identity，从identity输入框跳过来。1AM71 D5
        var manual_verification = "<div id='manual_verification_dialog' class='identity_visual_dialog' style='display:none;'>"
               + "<div style='text-align:center; height:45px; font-size:18px;'>"
               + "Welcome to <span style='color:#0591AC;'>EXFE</span>"
               + "</div>"
               + "<div style='height:30px; text-align:left;'>"
               + "Enter identity information:"
               + "</div>"
               + "<div style='height:40px;'>"
               + "<label class='title'>Identity:</label>"
               + "<input type='text' id='manual_verify_identity' />"
               + "</div>"
               + "<div id='manual_verification_hint_box' style='height:40px; text-align:left;'>"
               + "<p style='color:#CC3333;'>This identity needs to be verified before using.</p>"
               + "<p>Confirm sending verification to your mailbox?</p>"
               + "</div>"
               + "<div class='float_panel_bottom_btn' style='text-align:right;'>"
               + "<a id='manual_startover' class='startover'>Start Over</a>"
               + "<a href='javascript:void(0);' id='cancel_manual_verification_btn' style='line-height:20pt;display:none;'>I See</a>&nbsp;&nbsp;"
               + "<input type='button' id='manual_verification_btn' value='Verify' />"
               + "</div>"
               + "</div>";

        //var html="<div id='fBox' class='loginMask' style='display:none'>"
        var reg_success = "<div id='identity_reg_success' class='identity_visual_dialog' style='display:none;'>"
               + "<div style='height:35px; font-size:18px;'>Hi. <span id='identity_display_box'></span></div>"
               + "<div style='height:23px; font-size:18px;'>"
               + "Thanks for using <span style='color:#0591AC;'>EXFE</span>"
               + "</div>"
               + "<div style='height:30px;'>"
               + "An utility for hanging out with friends."
               + "</div>"
               + "<div style='height:30px; text-align:left;'>"
               + "Please check your mailbox for verification."
               + "</div>"
               + "<div style='height:90px; text-align:left;'>"
               + "<span style='color:#0591AC;'>X</span> (cross) is a gathering of people, for anything to do with them. We save you from calling up every one RSVP, losing in endless emails messages off the point."
               + "</div>"
               + "<div style='height:23px; text-align:left;'>"
               + "<span style='color:#0591AC;'>EXFE</span> your friends, gather a <span style='color:#0591AC;'>X</span>"
               + "</div>"
               + "<div style='text-align:right;'>"
               + "<input type='button' id='close_reg_success_dialog_btn' style='cursor:pointer;' value='Go' />"
               + "</div>"
               + "</div>";

        var sign_up_msg = "<div id='identity_register_msg' class='identity_visual_dialog reg_hint' style='display:none;'>"
               + "<div style='text-align:center; height:40px; font-size:18px;'>"
               + "<p>Sign-Up-Free</p>"
               + "</div>"
               + "<div style='height:60px;'>"
               + "<p>We know you’re tired of</p>"
               + "<p>signing up all around.</p>"
               + "</div>"
               + "<div style='height:50px; text-align:left;'>"
               + "So, just authorize with your existing accounts on other websites."
               + "</div>"
               + "<div style='height:120px; text-align:left;'>"
               + "Or, tell us your desired identity and display name that your friends know who you are, along with a password for sign-in. Identity could be your email address or phone number."
               + "</div>"
               + "<div style='text-align:right;'>"
               + "<input type='button' id='close_reg_msg_btn' style='cursor:pointer;' value='I See' />"
               + "</div>"
               + "</div>";

        var html = "<div id='identification_titles' class='titles'>"
               + "<div><a href='#' id='identification_close_btn'>Close</a></div>"
               + "<div id='identification_handler' class='tl'>"+title+"</div>"
               + "</div>"
               + "<div id='identity_error_msg' style='display:none;'>Invalid identity</div>"
               + "<div id='displayname_error_msg' style='display:none;'>Invalid identity</div>"
               + "<div id='need_verify_msg' style='display:none;'>You couldn’t sign in or edit if you left now</div>"
               + "<div id='identification_dialog_con' class='identification_dialog_con'>"
               + forgot_verification
               + manual_verification
               + sign_up_msg
               + reg_success
               + form
               + "</div>"
               + "<div class='identification_dialog_bottom'></div>";

        return html;
    };

    ns.showLoginDialog = function(type){
        //改变titles
        jQuery('#identification_title_msg').html('Enter identity information:');
        jQuery('#identification_title_msg').css({color:'#333333'});
        jQuery('#retype').hide();
        jQuery('#displayname').hide();
        jQuery('#sign_in_btn').val("Sign In");
        jQuery('#startover').hide();
        jQuery('#startover').unbind("click");
        jQuery('#logincheck').show();
        //因为取消显示Re-type，所以这个也不需要了。
        //odof.comm.func.removeRePassword("identification_pwd", "identification_rpwd");
        ns.actions = "sign_in";
        if(type == 'init'){
            jQuery('#identity').val('');
            jQuery('#sign_up_btn').show();
            jQuery('#forgot_password').hide();
            jQuery('#sign_in_btn').attr('disabled', false);
            jQuery('#sign_in_btn').removeClass("sign_in_btn_disabled");
            odof.user.status.showLastIdentity()
        }else{
            jQuery('#forgot_password').show();
        }

        //还是得绑定Password框的Onfocus事件。
        jQuery('#identification_pwd').focus(function() {
            odof.user.identification.identityInputBoxActions();
        });
    };

    ns.showDisplayNameError = function(displayName){
        if(displayName ==""){
            jQuery('#displayname_error_msg').html("Set your display name");
            jQuery('#displayname_error_msg').show();
            setTimeout(function(){
                jQuery('#displayname_error_msg').hide();
            }, 3000);
            return false;
        }else if(!odof.comm.func.verifyDisplayName(displayName)){
            jQuery('#displayname_error_msg').html("Invalid character in name");
            jQuery('#displayname_error_msg').show();
            setTimeout(function(){
                jQuery('#displayname_error_msg').hide();
            }, 3000);
            return false;
        }else{
            jQuery('#displayname_error_msg').hide();
            return true;
        }
    };


    ns.showManualVerificationDialog = function(curUserIdentity){
        jQuery("#manual_verification_dialog").show();

        var userIdentity = curUserIdentity;
        if(typeof curUserIdentity == "undefined" || curUserIdentity == "" || curUserIdentity == null){
            userIdentity = jQuery("#identity").val();
        }
        ns.userManualVerifyIdentityCache = userIdentity;

        jQuery("#manual_verify_identity").val(userIdentity);

        jQuery("#manual_verification_hint_box").html("<p style='color:#CC3333;'>This identity needs to be verified before using.</p><p>Confirm sending verification to your mailbox?</p>");
        jQuery("#manual_verification_btn").unbind("click");
        jQuery("#manual_verification_btn").val("Verify");
        jQuery("#manual_verification_btn").bind("click",function(){
            var callBackFunc = function(){
                var msg = "Verification sent, it should arrive in minutes. Please check your mailbox and follow the link.";
                jQuery("#manual_verification_hint_box").html(msg);
                jQuery("#manual_verification_btn").val("Done");
                jQuery("#manual_verification_btn").unbind("click");
                jQuery("#manual_verification_btn").bind("click",function(){
                    clearManualVerifyDialog();
                    ns.showLoginDialog('init');
                });
            };
            odof.user.status.doSendEmail(userIdentity, null, callBackFunc);
        });

        /*
        jQuery("#cancel_manual_verification_btn").unbind("click");
        jQuery("#cancel_manual_verification_btn").bind("click", function(){
            clearManualVerifyDialog();
            //jQuery("#manual_verification_dialog").hide();
            ns.showLoginDialog('init');
            jQuery("#identity").val(jQuery("#manual_verify_identity").val());
        });
        */

        jQuery('#manual_startover').unbind('click');
        jQuery('#manual_startover').bind('click', function(){
            clearManualVerifyDialog();
            ns.showLoginDialog('init');
        });

        //监听输入框。
        var manualVerifyTimer = window.setInterval(function(){
            var curVerifyIdentityVal = jQuery("#manual_verify_identity").val();
            if(curVerifyIdentityVal != ns.userManualVerifyIdentityCache){
                ns.identityInputBoxActions(curVerifyIdentityVal);
                clearManualVerifyDialog();
            }
        },250);

        //清除验证对话框。
        var clearManualVerifyDialog = function(){
                clearInterval(manualVerifyTimer);
                jQuery("#manual_verification_dialog").hide();
                jQuery('#manual_startover').unbind('click');
                jQuery("#manual_verification_btn").unbind("click");
                //jQuery("#cancel_manual_verification_btn").unbind("click");
        };
    };

    ns.identityInputBoxActions = function(myIdentity){
        var userIdentity = ""
        if(typeof myIdentity == "undefined"){
            userIdentity = jQuery('#identity').val();
        }else{//如果传入了，则需要重新设置一下identity输入框的值。
            jQuery('#identity').val(myIdentity);
            userIdentity = myIdentity;
        }
        //只有当不等时，才执行轮循
        if(userIdentity == ns.userIdentityCache){
            return false;
        }else{
            ns.userIdentityCache = jQuery('#identity').val();
        }

        var curDomain = userIdentity.split("@")[1];
        if(ns.inentityInputIntervalHandler != null){
            clearInterval(ns.inentityInputIntervalHandler);
        }

        //check email address
        if(userIdentity != "" && (userIdentity.match(odof.mailReg) || odof.util.inArray(ns.specialDomain,curDomain))) {
            jQuery("#identity_verify_loading").show();
            jQuery.ajax({
                type: "GET",
                url: site_url+"/s/IfIdentityExist?identity="+userIdentity,
                dataType:"json",
                success: function(data){
                    if(data!=null) {
                        //如果当前identity不存在。
                        if(data.response.identity_exist=="false"){
                            jQuery('#identification_title_msg').html('Signing up new identity:');
                            jQuery('#identification_title_msg').css({color:'#0591AC'});
                            //jQuery('#retype').show();
                            jQuery('#displayname').show();
                            jQuery('#forgot_password').hide();
                            jQuery('#logincheck').hide();
                            jQuery('#login_hint').hide();
                            jQuery("#user_avatar").hide();
                            //注册对话框中的start over button
                            jQuery('#startover').show();
                            jQuery('#startover').bind('click', function(){
                                ns.showLoginDialog('init');
                            });
                            jQuery('#sign_in_btn').val("Sign Up");
                            jQuery('input[name=displayname]').val('');
                            jQuery('#identification_pwd').val('');
                            /*
                            //取消显示Re-type。
                            jQuery('#identification_rpwd').val('');
                            jQuery('#identification_pwd_a').val('');
                            */

                            jQuery('input[name=displayname]').keyup(function(){
                                var displayName = this.value;
                                ns.showDisplayNameError(displayName);
                            });
                            //取消显示Re-type。
                            //odof.comm.func.initRePassword("identification_pwd", "identification_rpwd");
                            //换成单Password输入框。并且隐藏。
                            //odof.comm.func.displayPassword("identification_pwd");
                            jQuery('#identification_pwd').unbind("focus");
                            ns.actions = "sign_up";
                        } else if(data.response.identity_exist=="true") {
                            if(data.response.status == "verifying"){
                                ns.showManualVerificationDialog();
                            }else{
                                if(data.response.status == "empty_pwd"){
                                    jQuery("#login_hint").show();
                                    jQuery("#login_hint").html("<span>OAuth User,Password empty!</span>");
                                }

                                if(typeof data.response.avatar != "undefined" && data.response.avatar != ""){
                                    jQuery("#user_avatar").show();
                                    jQuery("#user_avatar")[0].src=odof.comm.func.getUserAvatar(data.response.avatar,80,img_url);
                                }else{
                                    jQuery("#user_avatar").hide();
                                }
                                ns.showLoginDialog();
                            }
                        }
                        jQuery('#sign_up_btn').hide();
                        jQuery('#sign_in_btn').attr('disabled', false);
                        jQuery('#sign_in_btn').removeClass("sign_in_btn_disabled");
                        jQuery('#sign_in_btn').addClass("sign_in_btn");

                    }
                    jQuery("#identity_verify_loading").hide();
                },
                complete:function(){
                    jQuery("#identity_verify_loading").hide();
                }
            });
        }
        /*
        }else{
            jQuery('#hint').hide();
            jQuery('#retype').hide();
            jQuery('#displayname').hide();
            jQuery('#forgot_password').hide();
            jQuery('#logincheck').hide();
            jQuery('#sign_up_btn').show();
            jQuery('#sign_in_btn').addClass("sign_in_btn_disabled");
            jQuery('#sign_in_btn').removeClass("sign_in_btn");
        }
        */
    };

    ns.bindLoginDialogEvent = function(){
        //在KeyUP事件之上加一层TimeOut设定，延迟响应以修复.co到.com的问题。
        /*
        jQuery('#identity').keyup(function() {
            jQuery(this).doTimeout('typing', 250, function(){
                ns.identityInputBoxActions();
            });
        });
        */
        jQuery('#identity').keyup(function(event) {
            var identity = jQuery('#identity').val();
            var currentIdentity = identity.split("@")[0];
            var curDomain = identity.split("@")[1];

            if(currentIdentity == ""){
                jQuery("#account_hint_list").hide();
            }else{
                if(identity.indexOf("@") < 0 || odof.util.inArray(ns.specialDomain,curDomain)){
                    jQuery("#account_hint_list").show();
                    jQuery("#account_hint_list").unbind("clickoutside");
                    jQuery("#account_hint_list").bind("clickoutside",function(event){
                        jQuery("#account_hint_list").hide();
                        jQuery("#account_hint_list").unbind("clickoutside");
                    });
                    jQuery("#facebook_name, #twitter_name, #google_name").html(currentIdentity);
                    jQuery("#facebook, #twitter, #google").unbind("click");
                    jQuery("#facebook, #twitter, #google").unbind("mouseover");
                    jQuery("#facebook, #twitter, #google").bind("mouseover",function(){
                        jQuery(this).addClass("active");
                    });
                    jQuery("#facebook, #twitter, #google").unbind("mouseleave");
                    jQuery("#facebook, #twitter, #google").bind("mouseleave",function(){
                        jQuery(this).removeClass("active");
                    });
                    jQuery("#facebook, #twitter, #google").bind("click", function(data){
                        var objID = data.currentTarget.id;
                        jQuery("#identity").val(jQuery("#"+objID+"_name").html()+"@"+objID);
                        jQuery("#account_hint_list").hide();
                        jQuery("#facebook, #twitter, #google").unbind("click");
                    });
                    if(event.keyCode == 38 || event.keyCode == 40){
                        //键盘的上下方向键操作。预留接口。
                    }
                }else{
                    jQuery("#account_hint_list").unbind("clickoutside");
                    jQuery("#account_hint_list").hide();
                }
            }
        });

        ns.inentityInputIntervalHandler = window.setInterval(function(){
            ns.identityInputBoxActions();
        },1000);

        //绑定当焦点到密码框时，检测一下当前用户是否存在。
        jQuery('#identification_pwd').focus(function() {
            ns.identityInputBoxActions();
        });

        //绑定两个显示的事件。
        jQuery('#identity').keyup(function(){ ns.showIdentityInfo(); });
        /*
        jQuery('#identity').change(function(){
            ns.showIdentityInfo();
            var userIdentity = jQuery('#identity').val();
            if(userIdentity == "" || !userIdentity.match(odof.mailReg)){
                jQuery("#identity_error_msg").show();
                setTimeout(function(){
                    jQuery("#identity_error_msg").hide();
                }, 3000);
            }
        });
        */

        jQuery('#identificationform').submit(function() {
            var params=ns.getUrlVars();
            //ajax set password
            //var token=params["token"];
            var identity=jQuery('input[name=identity]').val();
            var password=jQuery('input[name=password]').val();
            //var retypepassword=jQuery('input[name=retypepassword]').val();
            var displayname=jQuery('input[name=displayname]').val();
            var auto_signin=jQuery('input[name=auto_signin]').val();

            var hideErrorMsg = function(){
                jQuery("#identity_error_msg").hide();
                jQuery('#displayname_error_msg').hide();
                jQuery("#reset_pwd_error_msg").hide();
                jQuery("#displayname_error").hide();
                jQuery("#pwd_hint").hide();
                jQuery("#login_hint").hide();
            };


            if(identity == "" || !identity.match(odof.mailReg)){
                var curDomain = identity.split("@")[1];
                if(!odof.util.inArray(ns.specialDomain,curDomain)){
                    jQuery("#identity_error_msg").show();
                    setTimeout(hideErrorMsg, 3000);
                    return false;
                }
            }
            if(jQuery('#displayname').is(':visible')==true) {
                if(!ns.showDisplayNameError(displayname)){
                    return false;
                }
            }

            /*
            if(jQuery('#retype').is(':visible') == true && password != retypepassword && password!="" ) {
                jQuery('#pwd_hint').html("<span>Check Password</span>");
                jQuery('#pwd_hint').show();
                setTimeout(hideErrorMsg, 3000);
                return false;
            }
            */
            if(ns.actions == "sign_up"){
                if(password == ""){
                    jQuery('#pwd_hint').html("<span style='color:#CC3333'>Passwords empty.</span>");
                    jQuery('#pwd_hint').show();
                    setTimeout(hideErrorMsg, 3000);
                    return false;
                }
                /*
                if(retypepassword != password){
                    jQuery('#pwd_hint').html("<span style='color:#CC3333'>Passwords don't match.</span>");
                    jQuery('#pwd_match_error').show();
                    jQuery('#pwd_hint').show();
                    setTimeout(hideErrorMsg, 3000);
                    return false;
                }
                */
            }
            if(ns.actions == "sign_in"){
                if(password == "" || identity == ""){
                    jQuery('#login_hint').html("<span>Identity or password empty</span>");
                    jQuery('#login_hint').show();
                    setTimeout(hideErrorMsg, 3000);
                    return false;
                }
            }
            if(password != "" && identity != "" && jQuery('#displayname').is(':visible')==false) {
                var poststr = "identity="+identity+"&password="
                              + encodeURIComponent(password)+"&auto_signin="+auto_signin;
                jQuery.ajax({
                    type: "POST",
                    data: poststr,
                    url: site_url+"/s/dialoglogin",
                    dataType:"json",
                    success: function(data){
                        if(data!=null) {
                            if(data.response.success=="false") {
                                jQuery('#login_hint').show();
                                setTimeout(hideErrorMsg, 3000);
                            } else if(data.response.success=="true") {
                                jQuery("#hostby").val(identity);
                                jQuery("#hostby").attr("enter","true");
                                //如果是首页，并且是已经登录，则跳转到Profile页面。
                                if(typeof pageFlag != "undefined" && pageFlag == "home_page"){
                                    window.location.href="/s/profile";
                                    return;
                                }
                                //如果是从/s/login页面登录。
                                if(typeof showSpecialIdentityDialog != "undefined" && pageFlag == "login"){
                                    window.location.href="/s/profile";
                                    return;
                                }
                                //如果是从/x/forbidden页面登录
                                if(typeof showSpecialIdentityDialog != "undefined" && pageFlag == "forbidden"){
                                    //检查当前用户是否有权限访问这个Cross。
                                    jQuery.ajax({
                                        type:"POST",
                                        data:"cid="+cross_id,
                                        url:site_url+"/x/checkforbidden",
                                        dataType:"json",
                                        success:function(JSONData){
                                            if(JSONData.success){
                                                window.location.href=referer;
                                            }else{
                                                window.location.href="/s/profile";
                                            }
                                        }
                                    });

                                    return;
                                }
                                odof.exlibs.ExDialog.removeDialog();
                                odof.exlibs.ExDialog.removeCover();
                            }
                            //added by handaoliang
                            //callback check UserLogin
                            odof.user.status.checkUserLogin();
                        }
                    }
                });
            //} else if(password!=""&& identity!="" && retypepassword==password && displayname!="") {
            } else if(password!=""&& identity!="" && displayname!="") {
                /*
                var poststr="identity="+identity+"&password="+encodeURIComponent(password)
                            +"&repassword="+encodeURIComponent(retypepassword)
                            +"&displayname="+encodeURIComponent(displayname);
                */
                var poststr = "identity="+identity
                            + "&password="+encodeURIComponent(password)
                            + "&displayname="+encodeURIComponent(displayname);

                jQuery.ajax({
                    type: "POST",
                    data: poststr,
                    url: site_url+"/s/dialogaddidentity",
                    dataType:"json",
                    success: function(data){
                        if(data!=null)
                        {
                            if(data.response.success=="false") {
                                jQuery('#login_hint').show();
                                setTimeout(function(){
                                    jQuery('#login_hint').hide();
                                }, 3000);
                            } else if(data.response.success=="true") {
                                jQuery("#hostby").val(identity);
                                jQuery("#hostby").attr("enter","true");

                                //显示注册成功窗口
                                jQuery("#identity_reg_success").show();
                                jQuery("#identity_display_box").html(identity);
                                jQuery("#identification_handler").html("Welcome");
                                jQuery("#close_reg_success_dialog_btn").bind("click",function(){
                                    window.location.href="/s/profile";
                                    return;
                                    //odof.exlibs.ExDialog.removeDialog();
                                    //odof.exlibs.ExDialog.removeCover();
                                });
                                odof.user.status.checkUserLogin();
                            }
                        }
                    }
                });
            } else { //reg
                return false;
            }

            /*
            jQuery.ajax({
                type: "GET",
                url: site_url+"/identity/get?identity="+identity,
                dataType:"json",
                success: function(data){
                var exfee_pv="";
                if(data.response.identity!=null)
                {
                    var identity=data.response.identity.external_identity;
                    var id=data.response.identity.id;
                    var name=data.response.identity.name;
                    var avatar_file_name=data.response.identity.avatar_file_name;
                    if(jQuery('#exfee_'+id).attr("id")==null)
                    {
                        if(name=="")
                            name=identity;
                        exfee_pv = exfee_pv+'<li id="exfee_'+id+'" class="addjn" onmousemove="javascript:hide_exfeedel(jQuery(this))" onmouseout="javascript:show_exfeedel(jQuery(this))"> <p class="pic20"><img src="'+odof.comm.func.getUserAvatar(avatar_file_name, 80, img_url)+'" alt="" /></p> <p class="smcomment"><span class="exfee_exist" id="exfee_'+id+'" identityid="'+id+'"value="'+identity+'">'+name+'</span><input id="confirmed_exfee_'+ id +'" checked=true type="checkbox" /> <span class="lb">host</span></p> <button class="exfee_del" onclick="javascript:exfee_del(jQuery(\'#exfee_'+id+'\'))" type="button"></button> </li>';
                    }
                }

                jQuery("ul.samlcommentlist").append(exfee_pv);
                }
            });
            */
            return false;
        });
    };
})(ns);

/*
jQuery(document).ready(function(){
        jQuery('#loginform').submit(function() {
            var identity=jQuery('input[name=loginidentity]').val();
            var password=jQuery('input[name=password]').val();
            var auto_signin=jQuery('input[name=auto_signin]').val();
            var poststr="identity="+identity+"&password="+encodeURIComponent(password)+"&auto_signin="+auto_signin;
            jQuery('#login_hint').hide();
            jQuery.ajax({
                type: "POST",
                data: poststr,
                url: site_url+"/s/dialoglogin",
                dataType:"json",
                success: function(data){
                    if(data!=null)
                    {
                        if(data.response.success=="false")
                        {

                            //jQuery('#pwd_hint').html("<span>Error identity </span>");
                            jQuery('#login_hint').show();
                        }
                        else if(data.response.success=="true")
                        {
                        location.reload();
                        }
                    }
                }
            });
        return false;
        });

        jQuery('#identityform').submit(function() {
            var params=ns.getUrlVars();
            //ajax set password
            var token=params["token"];
            var password=jQuery('input[name=password]').val();
            var retypepassword=jQuery('input[name=retypepassword]').val();
            var displayname=jQuery('input[name=displayname]').val();


            if(password!=retypepassword && password!="" )
            {
                jQuery('#pwd_hint').html("<span>Check Password</span>");
                jQuery('#pwd_hint').show();
                return false;
            }
            if(displayname=="") {
                jQuery('#pwd_hint').html("<span>set your display name</span>");
                jQuery('#pwd_hint').show();
                return false;
            }
            if(token!=""&& cross_id>0)
            {
            var poststr="cross_id="+cross_id+"&password="+encodeURIComponent(password)+"&displayname="+displayname+"&token="+token;
            jQuery.ajax({
                type: "POST",
                data: poststr,
                url: site_url+"/s/setpwd",
                dataType:"json",
                success: function(data){
                    if(data!=null)
                    {
                        if(data.response.success=="false")
                        {
                        }
                        else if(data.response.success=="true")
                        {
                            location.reload();
                        }
                    }
                }
            });
            }
        return false;
    });
});
*/
