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
    ns.mailReg = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;

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

    ns.showdialog = function(type) {
        var title="", desc="", form="";
        if(type=="reg_login") {
            title="Identification";

            desc = "<div class='account'>Welcome to <span style='color:#0591AC;'>EXFE</span></div>"
                 + "<div id='identification_title_msg'>Enter identity information:</div>";
            /*
            desc="<div class='account'><p>Authorize with your <br/> existing accounts </p>"
                +"<span><img src='/static/images/facebook.png' alt='' width='32' height='32' />"
                +"<img src='/static/images/twitter.png' alt='' width='32' height='32' />"
                +"<img src='/static/images/google.png' alt='' width='32' height='32' /></span>"
                +"<h4>Enter identity information</h4>"
                +"</div>";
            */
            form = "<form id='identificationform' accept-charset='UTF-8' action='' method='post'>"
                 + "<ul>"
                 + "<li><label class='title'>Identity:</label>"
                 + "<div class='identity_box'>"
                 + "<input id='identity' name='identity' type='text' class='inputText' style='margin-left:0px;' onkeyup='javascript:odof.user.identification.showIdentityInfo();' onchange='javascript:odof.user.identification.showIdentityInfo();' />"
                 + "</div>"
                 + "<div id='identity_dbox'>Your email here</div>"
                 + "<em class='loading' id='identity_verify_loading' style='display:none;'></em>"
                 + "<em class='delete' id='delete_identity' style='display:none;'></em>"
                 + "</li>"
                 + "<li id='displayname' style='display:none'>"
                 + "<label class='title' style='color:#CC3333'>Display name:</label>"
                 + "<input type='text' name='displayname' class='inputText'/>"
                 + "</li>"
                 + "<li><label class='title'>Password:</label><input type='password' id='identification_pwd' name='password' class='inputText' />"
                 + "<input type='text' id='identification_pwd_a' class='inputText' style='display:none;' />"
                 + "<em class='ic3' id='identification_pwd_ic'></em>"
                 + "</li>"
                 + "<li id='identification_rpwd_li' style='display:none'>"
                 + "<label class='title'>Re-type:</label>"
                 + "<input type='password' id='identification_rpwd' name='retypepassword' class='inputText' />"
                 + "<em id='pwd_match_error' class='warning' style='display:none;'></em>"
                 + "</li>"
                 + "<li id='pwd_hint' style='display:none' class='notice'><span>check password</span></li>"
                 + "<li class='logincheck'>"
                 + "<div id='logincheck' style='display:none;'>"
                 + "<input type='checkbox' value='1' name='auto_signin' id='auto_signin' checked />"
                 + "<label for='auto_signin' style='cursor:pointer;'><span>Sign in automatically</span></label>"
                 + "</div></li>"
                 + "<li id='login_hint' style='display:none' class='notice'><span>Incorrect identity or password</span></li>"
                 + "</ul>"
                 + "<div class='identification_bottom_btn'>"
                 + "<a id='resetpwd' class='forgotpassword' style='display:none;'>Forgot Password...</a>"
                 + "<a href='#' id='sign_up_btn' class='sign_up_btn'>Sign Up?</a>"
                 + "<a id='startover' class='startover' style='display:none;'>Start Over</a>"
                 + "<input type='submit' value='Sign in' id='sign_in_btn' class='sign_in_btn_disabled' disabled='disabled' />"
                 //+ "<input type='submit' value='Sign in' id='sign_in_btn' class='sign_in_btn' /></li>"
                 + "</div>"
                 + "</form>";
        } else if(type=="change_pwd"){
            title = "Change Password";
            desc = "<div class='account' style='text-align:center; height:40px; font-size:18px;'>Change Password</div>"
                 + "<div style='font-size:14px; text-align:center;width:240px; margin:auto;'>Please enter current password and set new password.</div>";

            form = "<form id='identificationform' accept-charset='UTF-8' action='' method='post'>"
                 + "<ul>"
                 + "<li><label class='title'>Identity:</label>"
                 + "<div class='identity_box'>handaoliang@gmail.com</div>"
                 + "</li>"
                 + "<li><label class='title'>Password:</label>"
                 + "<input type='password' id='identification_pwd' name='password' class='inputText' />"
                 + "<input type='text' id='identification_pwd_a' class='inputText' style='display:none;' />"
                 + "<em class='ic3' id='identification_pwd_ic'></em>"
                 + "</li>"
                 + "<li>"
                 + "<label class='title'>New password:</label>"
                 + "<input type='password' id='identification_newpwd' name='newpassword' class='inputText' />"
                 + "<input type='text' id='identification_newpwd_a' class='inputText' style='display:none;' />"
                 + "<em class='ic3' id='identification_newpwd_ic'></em>"
                 + "</li>"
                 + "<li id='identification_renewpwd_li'>"
                 + "<label class='title'>Re-type new:</label>"
                 + "<input type='password' id='identification_renewpwd' name='renewpassword' class='inputText' />"
                 + "</li>"
                 + "<li id='pwd_hint' style='display:none' class='notice'><span>check password</span></li>"
                 + "<li id='displayname' style='display:none'><label class='title'>Display name:</label>"
                 + "<input  type='text'  name='displayname'class='inputText'/>"
                 + "<em id='displayname_error' class='warning' style='display:none;'></em></li>"
                 + "<li class='logincheck' id='logincheck' style='display:none;'>"
                 + "<input type='checkbox' value='1' name='auto_signin' id='auto_signin' checked />"
                 + "<span>Sign in automatically</span>"
                 + "</li>"
                 + "<li style='width:148px; padding:15px 0 0 190px;'>"
                 + "<a href='javascript:void(0);'>Discard</a>"
                 + "<input type='submit' value='Done' class='sub' />"
                 + "</li>"
                 + "</ul>"
                 + "</form>";
        } else if(type=="reset_pwd"){
            title = "Set Password";
            desc = "<div class='account' style='text-align:center; height:40px; font-size:18px;'>Set Password</div>"
                 + "<div style='font-size:14px; text-align:center;width:240px; margin:auto;'>Please set password to keep track of RSVP status and engage in.</div>";
            form = "<ul>"
                 + "<li><label class='title'>Identity:</label>"
                 + "<div class='identity_box' id='show_identity_box' style='font-size:14px;'></div>"
                 + "</li>"
                 + "<li><label class='title'>Password:</label>"
                 + "<input type='password' id='identification_pwd' name='password' class='inputText' style='display:none;' />"
                 + "<input type='text' id='identification_pwd_a' class='inputText' />"
                 + "<input type='hidden' id='identification_user_token' value='' />"
                 + "<em class='ic2' id='identification_pwd_ic'></em>"
                 + "</li>"
                 + "<li id='identification_repwd_li' style='display:none;'>"
                 + "<label class='title'>Re-type:</label>"
                 + "<input type='password' id='identification_repwd' name='repassword' class='inputText' />"
                 + "<em id='pwd_match_error' class='warning' style='display:none;'></em>"
                 + "</li>"
                 + "<li id='pwd_hint' style='display:none' class='notice'><span>check password</span></li>"
                 + "<li id='displayname'><label class='title'>Display name:</label>"
                 + "<input  type='text'  name='displayname' class='inputText' id='user_display_name' />"
                 + "<em id='displayname_error' class='warning' style='display:none;'></em></li>"
                 + "<li id='reset_pwd_error_msg' style='padding-left:118px; color:#FD6311; display:none;'></li>"
                 + "<li style='width:148px; padding:15px 0 0 190px; text-align:right;'>"
                 //+ "<a href='javascript:void(0);'>Discard</a>&nbsp;&nbsp;"
                 + "<input type='submit' value='Done' class='sub' id='submit_reset_password' style='cursor:pointer;' />"
                 + "</li>"
                 + "</ul>";
        }

        var forgot_verification = "<div id='forgot_verification_dialog' style='display:none;'>"
               + "<div style='text-align:center; height:45px; font-size:18px;'>"
               + "Forgot password"
               + "</div>"
               + "<div style='height:25px; text-align:left;'>"
               + "A verification will be sent to your identity:"
               + "</div>"
               + "<div style='height:40px;'>"
               + "<input type='text' id='forgot_identity_input' disabled='disabled' />"
               + "</div>"
               + "<div style='height:40px; text-align:left;'>"
               + "Confirm sending verification to your mailbox? It should arrive in minutes."
               + "</div>"
               + "<div class='float_panel_bottom_btn' style='text-align:right;'>"
               + "<a href='javascript:void(0);' id='cancel_forgot_verify_btn'>Cancel</a>&nbsp;&nbsp;"
               + "<input type='button' id='fogot_verify_btn' value='Verify' />"
               + "</div>"
               + "</div>";

        //需要验证的identity，从identity输入框跳过来。1AM71 D5
        var manual_verification = "<div id='manual_verification_dialog' style='display:none;'>"
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
               + "<p'>Confirm sending verification to your mailbox?</p>"
               + "</div>"
               + "<div class='float_panel_bottom_btn' style='text-align:right;'>"
               + "<a id='manual_startover' class='startover'>Start Over</a>"
               + "<a href='javascript:void(0);' id='cancel_manual_verification_btn' style='line-height:20pt;'>I See</a>&nbsp;&nbsp;"
               + "<input type='button' id='manual_verification_btn' value='Done' />"
               + "</div>"
               + "</div>";

        var forgot_pwd = "<div id='identity_forgot_pwd_dialog' class='identity_forgot_pwd_dialog' style='display:none;'>"
                       + "<div class='account' style='text-align:center; height:40px; font-size:18px;'>"
                       + "<p>Welcome to <span style='color:#0591AC;'>EXFE</span></p>"
                       + "</div>"
                       + "<div style='float:left; font-size:14px; height:30px; padding-left:36px; text-align:left; display:none;'>"
                       + "Enter identity information:"
                       + "</div>"
                       + "<div>"
                       + "<label class='title'>Identity:</label><span id='f_identity_box' style='float:left;font-size:18px; font-style:italic;'>"
                       + "<input type='text' id='f_identity' class='inputText' />"
                       + "</span>"
                       + "</div>"
                       + "<div id='identity_forgot_pwd_info' style='margin-left:80px; padding:10px; text-align:left; width:290px; font-size:14px;'>Verification will be sent in minutes, please check your inbox.</div>"
                       + "<div style='text-align:right; width:300px;'>"
                       + "<span id='submit_loading_btn' style='display:none;'></span>"
                       + "<a href='javascript:void(0);' id='cancel_verification_btn'>Cancel</a>&nbsp;&nbsp;"
                       + "<input type='button' id='send_verification_btn' style='cursor:pointer;' value='Send Verification' />"
                       + "<input type='hidden' id='f_identity_hidden' value='' />"
                       + "</div>"
                       + "</div>";

        //var html="<div id='fBox' class='loginMask' style='display:none'>"
        var reg_success = "<div id='identity_reg_success' style='display:none;'>"
                       + "<div style='height:35px; font-size:18px;'>Hi. <span id='identity_display_box'></span></div>"
                       + "<div class='account' style='height:23px; font-size:18px;'>"
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

        var sign_up_msg = "<div id='identity_register_msg' class='identity_register_msg' style='display:none;'>"
                       + "<div class='account' style='text-align:center; height:40px; font-size:18px;'>"
                       + "<p>Sign-Up-Free</p>"
                       + "</div>"
                       + "<div style='height:60px;'>"
                       + "<p>We know you’re tired of</p>"
                       + "<p>signing up all around.</p>"
                       + "</div>"
                       + "<div style='height:50px; text-align:left;'>"
                       + "So, just authorize with your existing accounts on other websites."
                       + "</div>"
                       + "<div style='height:90px; text-align:left;'>"
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
                   + "<div id='overFramel' class='overFramel'>"
                   + forgot_verification
                   + manual_verification
                   + forgot_pwd
                   + sign_up_msg
                   + reg_success
                   + "<div class='overFramelogin'>"
                   + "<div class='login'>"
                   + desc
                   + form 
                   + "</div>"
                   + "</div>"
                   + "</div>"
                   + "<div class='bottom'></div>";
                   //+ "<b class='rbottom'><b class='r3'></b><b class='r2'></b><b class='r1'></b></b>";
                   //+ "</div>";
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
        odof.comm.func.removeRePassword("identification_pwd", "identification_rpwd");
        ns.actions = "sign_in";
        if(type == 'init'){
            jQuery('#identity').val('');
            jQuery('#sign_up_btn').show();
            jQuery('#resetpwd').hide();
            jQuery('#sign_in_btn').attr('disabled', false);
            jQuery('#sign_in_btn').removeClass("sign_in_btn_disabled");
            odof.user.status.showLastIdentity()
        }else{
            jQuery('#resetpwd').show();
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


    ns.showManualVerificationDialog = function(){
        jQuery("#manual_verification_dialog").show();

        var userIdentity = jQuery("#identity").val();
        ns.userManualVerifyIdentityCache = userIdentity;

        jQuery("#manual_verify_identity").val(userIdentity);

        jQuery("#manual_verification_btn").unbind("click");
        jQuery("#manual_verification_btn").bind("click",function(){
            odof.user.status.doSendEmail(userIdentity,"verification");
            var msg = "Verification sent, it should arrive in minutes. Please check your mailbox and follow the link.";
            jQuery("#manual_verification_hint_box").html(msg);
        });

        jQuery("#cancel_manual_verification_btn").unbind("click");
        jQuery("#cancel_manual_verification_btn").bind("click", function(){
            clearManualVerifyDialog();
            jQuery("#manual_verification_dialog").hide();
        });

        jQuery('#manual_startover').unbind('click');
        jQuery('#manual_startover').bind('click', function(){
            clearManualVerifyDialog();
            ns.showLoginDialog('init');
        });

        //监听输入框。
        var manualVerifyTimer = window.setInterval(function(){
            var curVerifyIdentityVal = jQuery("#manual_verify_identity").val()
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
                jQuery("#cancel_manual_verification_btn").unbind("click");
        };

    };

    ns.identityInputBoxActions = function(userIdentity){

        if(typeof userIdentity == "undefined"){
            var userIdentity = jQuery('#identity').val();
        }else{//如果传入了，则需要重新设置一下identity输入框的值。
            jQuery('#identity').val(userIdentity);
        }
        //只有当不等时，才执行轮循
        //console.log("aaaaa");
        if(userIdentity == ns.userIdentityCache){
            return false;
        }else{
            ns.userIdentityCache = jQuery('#identity').val();
        }
        //console.log("bbbbb");
        //added by handaoliang, check email address
        if(userIdentity != "" && userIdentity.match(ns.mailReg)){
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
                            jQuery('#resetpwd').hide();
                            jQuery('#logincheck').hide();
                            jQuery('#login_hint').hide();
                            //注册对话框中的start over button
                            jQuery('#startover').show();
                            jQuery('#startover').bind('click', function(){
                                ns.showLoginDialog('init');
                            });
                            jQuery('#sign_in_btn').val("Sign Up");
                            jQuery('input[name=displayname]').val('');
                            jQuery('#identification_pwd').val('');
                            jQuery('#identification_rpwd').val('');
                            jQuery('#identification_pwd_a').val('');

                            jQuery('input[name=displayname]').keyup(function(){
                                var displayName = this.value;
                                ns.showDisplayNameError(displayName);
                            });
                            odof.comm.func.initRePassword("identification_pwd", "identification_rpwd");
                            jQuery('#identification_pwd').unbind("focus");
                            ns.actions = "sign_up";
                        } else if(data.response.identity_exist=="true") {
                            if(data.response.status == "verifying"){
                                ns.showManualVerificationDialog();
                            }else{
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
            jQuery('#resetpwd').hide();
            jQuery('#logincheck').hide();
            jQuery('#sign_up_btn').show();
            jQuery('#sign_in_btn').addClass("sign_in_btn_disabled");
            jQuery('#sign_in_btn').removeClass("sign_in_btn");
        }
        */
    };

    ns.bindDialogEvent = function(type) {
        if(type=="reg") {
            //在KeyUP事件之上加一层TimeOut设定，延迟响应以修复.co到.com的问题。
            /*
            jQuery('#identity').keyup(function() {
                jQuery(this).doTimeout('typing', 250, function(){
                    ns.identityInputBoxActions();
                });
            });
            */
            window.setInterval(function(){
                ns.identityInputBoxActions();
            },250);

            //绑定当焦点到密码框时，检测一下当前用户是否存在。
            jQuery('#identification_pwd').focus(function() {
                ns.identityInputBoxActions();
            });
            jQuery('input[name=identity]').blur(function(){
                var userIdentity = jQuery('input[name=identity]').val();
                if(userIdentity == "" || !userIdentity.match(ns.mailReg)){
                    jQuery("#identity_error_msg").show();
                    setTimeout(function(){
                        jQuery("#identity_error_msg").hide();
                    }, 3000);
                }
            });


            jQuery('#identificationform').submit(function() {
                    var params=ns.getUrlVars();
                    //ajax set password
                    //var token=params["token"];
                    var identity=jQuery('input[name=identity]').val();
                    var password=jQuery('input[name=password]').val();
                    var retypepassword=jQuery('input[name=retypepassword]').val();
                    var displayname=jQuery('input[name=displayname]').val();
                    var auto_signin=jQuery('input[name=auto_signin]').val();

                    var hideErrorMsg = function(){
                        jQuery("#identity_error_msg").hide();
                        jQuery('#displayname_error_msg').hide();
                        jQuery("#reset_pwd_error_msg").hide();
                        jQuery("#displayname_error").hide();
                        jQuery("#pwd_hint").hide();
                    };


                    if(identity == "" || !identity.match(ns.mailReg)){
                        jQuery("#identity_error_msg").show();
                        setTimeout(hideErrorMsg, 3000);
                        return false;
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

                        if(retypepassword != password){
                            jQuery('#pwd_hint').html("<span style='color:#CC3333'>Passwords don't match.</span>");
                            jQuery('#pwd_match_error').show();
                            jQuery('#pwd_hint').show();
                            setTimeout(hideErrorMsg, 3000);
                            return false;
                        }
                    }
                    if(ns.actions == "sign_in"){
                        if(password == "" || identity == ""){
                            jQuery('#login_hint').html("<span>Identity or password empty</span>");
                            jQuery('#login_hint').show();
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
                                        odof.exlibs.ExDialog.hideDialog();
                                        odof.exlibs.ExDialog.destroyCover();
                                        //jQuery.modal.close();
                                    }
                                    //added by handaoliang
                                    //callback check UserLogin
                                    odof.user.status.checkUserLogin();
                                }
                            }
                        });
                    } else if(password!=""&& identity!="" && retypepassword==password &&  displayname!="") {
                        var poststr="identity="+identity+"&password="+encodeURIComponent(password)
                                    +"&repassword="+encodeURIComponent(retypepassword)
                                    +"&displayname="+encodeURIComponent(displayname);
                        jQuery.ajax({
                            type: "POST",
                            data: poststr,
                            url: site_url+"/s/dialogaddidentity",
                            dataType:"json",
                            success: function(data){
                                if(data!=null)
                                {
                                    if(data.response.success=="false")
                                    {
                                        jQuery('#login_hint').show();
                                    }
                                    else if(data.response.success=="true")
                                    {
                                        jQuery("#hostby").val(identity);
                                        jQuery("#hostby").attr("enter","true");

                                        //显示注册成功窗口
                                        jQuery("#identity_reg_success").show();
                                        jQuery("#identity_display_box").html(identity);
                                        jQuery("#identification_handler").html("Welcome");
                                        jQuery("#close_reg_success_dialog_btn").bind("click",function(){
                                            window.location.href="/s/profile";
                                            return; 
                                            //odof.exlibs.ExDialog.hideDialog();
                                            //odof.exlibs.ExDialog.destroyCover();
                                        });
                                        odof.user.status.checkUserLogin();
                                    }
                                }
                            }
                        });
                    } else { //reg
                        return false;
                    }

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
                                exfee_pv = exfee_pv+'<li id="exfee_'+id+'" class="addjn" onmousemove="javascript:hide_exfeedel(jQuery(this))" onmouseout="javascript:show_exfeedel(jQuery(this))"> <p class="pic20"><img src="'+odof.comm.func.getHashFilePath(img_url,avatar_file_name)+'/80_80_'+avatar_file_name+'" alt="" /></p> <p class="smcomment"><span class="exfee_exist" id="exfee_'+id+'" identityid="'+id+'"value="'+identity+'">'+name+'</span><input id="confirmed_exfee_'+ id +'" checked=true type="checkbox" /> <span class="lb">host</span></p> <button class="exfee_del" onclick="javascript:exfee_del(jQuery(\'#exfee_'+id+'\'))" type="button"></button> </li>';
                            }
                        }

                        jQuery("ul.samlcommentlist").append(exfee_pv);
                        }
                });
                return false;
            });
        }
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
