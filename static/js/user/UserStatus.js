/**
 * @Description:    user login module
 * @createDate:     Sup 23,2011
 * @LastModified:   handaoliang
 * @CopyRights:		http://www.exfe.com
 **/
var moduleNameSpace = "odof.user.status";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    /**
     * Check if user login
     *
     * */
    ns.callBackFunc = null;
    ns.showResetPasswordStatus = 0;
    ns.checkUserLogin = function(){
        //专门针对Cross页面，如果以Token进入，则预先设置一个ID为：cross_identity_btn的元素节点。
        //具体的事件绑定在odof.cross.index里面实现。
        //页面上写有external_identity

        if(typeof login_type != "undefined" && login_type == "token"){
            var display_name = external_identity;
            if(typeof token_expired != "undefined" && token_expired == "false"){
                display_name = id_name;
            }
            /*
            var navMenu = '<div class="global_sign_in_btn">'
                            + '<a id="cross_identity_btn" href="javascript:void(0);">'
                            + display_name
                            + '</a>'
                            + '</div>';
            jQuery("#global_user_info").html(navMenu);
            */
            odof.user.status.showTokenIdentityStatus(myIdentity);
            try{
                if(odof.user.status.callBackFunc){
                    odof.user.status.callBackFunc();
                }
            }catch(e){ /*console.log(e);*/ }

        }else{//正常检查是否登录。
            var getURI = site_url+"/s/checkUserLogin";
            jQuery.ajax({
                type: "GET",
                url: getURI,
                dataType:"json",
                success: function(JSONData){
                    odof.user.status.showLoginStatus(JSONData);
                    try{
                        if(odof.user.status.callBackFunc){
                            odof.user.status.callBackFunc(JSONData);
                        }
                    }catch(e){ /*pass*/ }
                }
            });
        }
    };

    ns.doShowCrossPageVerifyDialog = function(dialogContainerID, args){
        ns.doShowLoginDialog(dialogContainerID);
        odof.util.delCookie('last_identity', "/", cookies_domain);
        odof.util.setCookie("last_identity", args.identity, 365, cookies_domain);
        odof.user.identification.userIdentityCache = args.identity;
        odof.user.identification.showManualVerificationDialog(args.identity);
    };

    ns.doSendEmail = function(userIdentity, doActions, callBackFunc){
        var actionURI = "";
        if(typeof doActions != "undefined" && doActions != null && doActions == 'resetPassword')
        {
            actionURI = site_url+"/s/SendResetPasswordMail";
        }else{
            actionURI = site_url+"/s/SendVerifyingMail";
        }

        userIdentity = odof.util.trim(userIdentity);
        if(userIdentity != "" && userIdentity.match(odof.mailReg)){
            var postData = {identity:userIdentity};
            jQuery("#submit_loading_btn").show();
            jQuery.ajax({
                type: "POST",
                url: actionURI,
                dataType: "json",
                data: postData,
                success: function(JSONData){
                    if(typeof callBackFunc != "undefined"){
                        callBackFunc();
                    }
                },
                complete: function(){
                    jQuery("#submit_loading_btn").hide();
                }
            });
        }
    };
    //重置密码。
    ns.doShowResetPwdDialog =function(resetPwdCID, actions){
        var html = odof.user.identification.createDialogDomCode("reset_pwd");
        if(typeof resetPwdCID != "undefined" && typeof resetPwdCID == "string") {
            jQuery("#"+resetPwdCID).html(html);
        }else{
            odof.exlibs.ExDialog.initialize("identification", html);
            var dialogBoxID = "identification_dialog";
        }

        //去掉Re-type
        //odof.comm.func.initRePassword("identification_pwd", "identification_repwd");

        jQuery("#identification_pwd_ic").click(function(){
            odof.comm.func.displayPassword("identification_pwd");
        });

        //默认为重置密码。
        if(typeof actions == "undefined"){
            actions = "resetpwd";
        }

        //如果为Cross页面的设置密码。
        if(actions == 'setpwd'){
            jQuery("#set_password_titles").html("Welcome to <span style='color:#0591AC;'>EXFE</span>");
            jQuery("#set_password_desc").html("Please set password to keep track of attendees update, and engage in.");
            var showWarning = function(){
                jQuery("#need_verify_msg").show();
                //显示Discard按钮，并且绑定关闭事件。
                jQuery("#set_passwprd_discard").show();
                jQuery("#set_passwprd_discard").unbind("click");
                jQuery("#identification_close_btn").unbind("click");
                jQuery("#set_passwprd_discard, #identification_close_btn").bind("click", function(){
                    odof.exlibs.ExDialog.removeDialog();
                    odof.exlibs.ExDialog.removeCover();
                });
                ns.showResetPasswordStatus = 1;
            };

            //使用ns.showResetPasswordStatus来记录状态，如果已经点击过了。则需要一直显示状态。
            if(ns.showResetPasswordStatus == 0){
                jQuery("#identification_close_btn").unbind("click");
                jQuery("#identification_close_btn").bind("click", function(){
                    showWarning();
                });
                jQuery("#identification_dialog").bind("clickoutside",function(event){
                    if(event.target == jQuery("#identification_cover")[0]){
                        showWarning();
                    }
                });
            }else{
                showWarning();
            }
        }

        jQuery("#reset_pwd_form").submit(function(){
            var userPassword = jQuery("#identification_pwd").val();
            //var userRePassword = jQuery("#identification_repwd").val();
            var userDisplayName = jQuery("#user_display_name").val();
            var userToken = jQuery("#identification_user_token").val();
            var hideErrorMsg = function(){
                jQuery("#reset_pwd_error_msg").hide();
                jQuery("#displayname_error").hide();
                jQuery("#pwd_match_error").hide();
            }

            /*else if(userPassword != userRePassword){
                jQuery("#reset_pwd_error_msg").show();
                jQuery("#pwd_match_error").show();
                setTimeout(hideErrorMsg, 3000);
                jQuery("#reset_pwd_error_msg").html("Passwords don't match.");
            } */

            if(userDisplayName == ""){
                jQuery("#reset_pwd_error_msg").show();
                jQuery("#displayname_error").show();
                setTimeout(hideErrorMsg, 3000);
                jQuery("#reset_pwd_error_msg").html("Please input a display name.");
                return false;
            }
            if(!odof.comm.func.verifyDisplayName(userDisplayName)){
                jQuery("#reset_pwd_error_msg").show();
                jQuery("#displayname_error").show();
                setTimeout(hideErrorMsg, 3000);
                jQuery("#reset_pwd_error_msg").html("Display name Error.");
                return false;
            }
            if(userPassword == ""){
                jQuery("#reset_pwd_error_msg").show();
                setTimeout(hideErrorMsg, 3000);
                jQuery("#reset_pwd_error_msg").html("Please input a password.");
                return false;
            }
            // post data
            var postData = {
                jrand:Math.round(Math.random()*10000000000),
                u_pwd:userPassword,
                u_dname:userDisplayName,
                u_token:userToken
            };
            if(actions == "setpwd"){
                postData = {
                    jrand:Math.round(Math.random()*10000000000),
                    u_pwd:userPassword,
                    u_dname:userDisplayName,
                    c_id:cross_id,
                    c_token:token
                };
            }
            jQuery.ajax({
                type: "POST",
                url: site_url+"/s/resetPassword?act="+actions,
                dataType:"json",
                data:postData,
                success: function(JSONData){
                    if(!JSONData.error){
                        if(actions == "setpwd") {
                            window.location.href="/!"+JSONData.cross_id;
                        } else{
                            jQuery("#userResetPwdBox").hide();
                            jQuery("#resetPwdSuccess").show();
                            jQuery("#resetPwdSuccess").fadeTo("slow", 0.3, function(){
                                window.location.href="/s/profile";
                            });
                        }
                    }else{
                        jQuery("#reset_pwd_error_msg").show();
                        setTimeout(hideErrorMsg, 3000);
                    }
                }
            });
            return false;
        });

    };

    ns.changeOAuthAccountPWD = function(userIdentity, externalUserName, callBackFunc){
        var html = odof.user.identification.createDialogDomCode("reset_pwd");
        odof.exlibs.ExDialog.initialize("identification", html);

        jQuery("#show_identity_box").val(externalUserName);
        jQuery("#user_display_name").val(jQuery("#profile_name").html());

        jQuery("#identification_pwd_ic").click(function(){
            odof.comm.func.displayPassword("identification_pwd");
        });

        //显示Discard按钮，并且绑定关闭事件。
        jQuery("#set_passwprd_discard").show();
        jQuery("#set_passwprd_discard").unbind("click");
        jQuery("#identification_close_btn").unbind("click");
        jQuery("#set_passwprd_discard, #identification_close_btn").bind("click", function(){
            odof.exlibs.ExDialog.removeDialog();
            odof.exlibs.ExDialog.removeCover();
        });

        jQuery("#reset_pwd_form").submit(function(){
            var userPassword = jQuery("#identification_pwd").val();
            var userDisplayName = jQuery("#user_display_name").val();
            var hideErrorMsg = function(){
                jQuery("#reset_pwd_error_msg").hide();
                jQuery("#displayname_error").hide();
                jQuery("#pwd_match_error").hide();
            }

            if(userDisplayName == ""){
                jQuery("#reset_pwd_error_msg").show();
                jQuery("#displayname_error").show();
                setTimeout(hideErrorMsg, 3000);
                jQuery("#reset_pwd_error_msg").html("Please input a display name.");
                return false;
            }
            if(!odof.comm.func.verifyDisplayName(userDisplayName)){
                jQuery("#reset_pwd_error_msg").show();
                jQuery("#displayname_error").show();
                setTimeout(hideErrorMsg, 3000);
                jQuery("#reset_pwd_error_msg").html("Display name Error.");
                return false;
            }
            if(userPassword == ""){
                jQuery("#reset_pwd_error_msg").show();
                setTimeout(hideErrorMsg, 3000);
                jQuery("#reset_pwd_error_msg").html("Please input a password.");
                return false;
            }
            // post data
            var postData = {
                jrand:Math.round(Math.random()*10000000000),
                u_identity:userIdentity,
                u_passwd:userPassword,
                u_dname:userDisplayName
            };

            jQuery.ajax({
                type: "POST",
                url: site_url+"/s/setOAuthAccountPassword",
                dataType:"json",
                data:postData,
                success: function(JSONData){
                    if(JSONData.error){
                        jQuery("#reset_pwd_error_msg").html(JSONData.msg);
                        jQuery("#reset_pwd_error_msg").show();
                        setTimeout(function(){
                            jQuery("#reset_pwd_error_msg").hide();
                        }, 3000);
                    }else{
                        odof.exlibs.ExDialog.removeDialog();
                        odof.exlibs.ExDialog.removeCover();
                    }
                }
            });
            return false;
        });
    };

    ns.doShowChangePwdDialog = function(userIdentity, callBackFunc){
        var html = odof.user.identification.createDialogDomCode("change_pwd");
        odof.exlibs.ExDialog.initialize("identification", html);
        //绑定事件。
        jQuery("#show_identity_box").val(userIdentity);
        jQuery("#o_pwd_ic").bind("click",function(){
            odof.comm.func.displayPassword("o_pwd");
        });
        //去掉Re-type
        //odof.comm.func.initRePassword("new_pwd", "re_new_pwd", "invisible");
        //换成替换成单密码输入框。
        jQuery("#new_pwd_ic").bind("click",function(){
            odof.comm.func.displayPassword("new_pwd");
        });

        jQuery("#change_pwd_discard").unbind("click");
        jQuery("#change_pwd_discard").bind("click", function(){
            odof.exlibs.ExDialog.removeDialog();
            odof.exlibs.ExDialog.removeCover();
        });

        jQuery("#forgot_password").bind("click", function(){
            ns.showForgotPwdDialog(userIdentity);
        });
        jQuery("#change_pwd_form").submit(function(){
            var userIdentity = jQuery("#show_identity_box").val();
            var userPassword = jQuery("#o_pwd").val();
            var userNewPassword = jQuery("#new_pwd").val();
            //去掉Re-type
            //var userReNewPassword = jQuery("#re_new_pwd").val();
            if(userPassword == ""){
                jQuery("#change_pwd_error_msg").html("Password cannot be empty.");
                jQuery("#change_pwd_error_msg").show();
                return false;
            }
            if(userNewPassword == ""){
                jQuery("#change_pwd_error_msg").html("New password cannot be empty.");
                jQuery("#change_pwd_error_msg").show();
                return false;
            }
            //去掉Re-type
            /*
            if(userNewPassword != userReNewPassword){
                jQuery("#change_pwd_error_msg").html("Passwords don’t match.");
                jQuery("#change_pwd_error_msg").show();
                return false;
            }
            var postData = {
                jrand:Math.round(Math.random()*10000000000),
                u_pwd:userPassword,
                u_new_pwd:userNewPassword,
                u_re_new_pwd:userReNewPassword
            };
            */
            var postData = {
                jrand:Math.round(Math.random()*10000000000),
                u_identity:userIdentity,
                u_pwd:userPassword,
                u_new_pwd:userNewPassword
            };

            jQuery.ajax({
                type: "POST",
                data: postData,
                url: site_url+"/s/changePassword",
                dataType:"json",
                success: function(JSONData){
                    if(JSONData.error){
                        jQuery("#change_pwd_error_msg").html(JSONData.msg);
                        jQuery("#change_pwd_error_msg").show();
                    }else{
                        odof.exlibs.ExDialog.removeDialog();
                        odof.exlibs.ExDialog.removeCover();
                    }
                }
            });
            return false;

        });
    };

    ns.showLastIdentity = function(){
        var lastIdentity = odof.util.getCookie('last_identity');
        try{
            var lastIdentityObj = jQuery.parseJSON(lastIdentity);
        }catch(e){
            var lastIdentityObj = lastIdentity;
            //pass
        }

        if(lastIdentity){
            if(typeof lastIdentityObj == "string"){
                jQuery("#identity").val(lastIdentity);
            }else{
                jQuery("#identity").val(lastIdentityObj.identity);
                jQuery("#user_avatar").show();
                jQuery("#user_avatar")[0].src = odof.comm.func.getUserAvatar(lastIdentityObj.identity_avatar, 80, img_url);
            }
            jQuery("#identity_dbox").hide();
            jQuery("#identity").bind("mouseover",function(){
                jQuery("#delete_identity").show();
                setTimeout(function(){
                    jQuery("#delete_identity").hide();
                }, 3000);
            });

            //enable sign in btn
            jQuery('#sign_in_btn').attr("disabled", false);
            jQuery('#sign_in_btn').removeClass("sign_in_btn_disabled");
            jQuery('#sign_in_btn').addClass("sign_in_btn");
            jQuery('#forgot_password').show();
            jQuery('#logincheck').show();
            jQuery('#sign_up_btn').hide();

            /*
            jQuery("#identity").bind("mouseout",function(){
                jQuery("#delete_identity").hide();
            });
            */

            jQuery("#delete_identity").click(function(){
                odof.util.delCookie('last_identity', "/", cookies_domain);
                jQuery("#user_avatar").hide();
                jQuery("#user_avatar")[0].src = "";

                jQuery("#identity").val("");
                jQuery("#identity_dbox").show();
                jQuery("#identity").unbind("mouseover");
                //jQuery("#identity").unbind("mouseout");
                jQuery("#delete_identity").unbind("click");
                jQuery("#delete_identity").hide();
            });
        }
    };

    ns.doShowAddIdentityDialog = function(){
        var html = odof.user.identification.createDialogDomCode("add_identity");
        odof.exlibs.ExDialog.initialize("identification", html);
    };

    ns.doShowLoginDialog = function(dialogBoxID, callBackFunc, userIdentity, winModal, dialogPosY){
        var html = odof.user.identification.createDialogDomCode("reg_login");
        if(typeof callBackFunc != "undefined" && callBackFunc != null){
            ns.callBackFunc = callBackFunc;
        }

        if(typeof dialogBoxID != "undefined" && typeof dialogBoxID == "string" && dialogBoxID != null){
            document.getElementById(dialogBoxID).innerHTML = html;
        }else{
            odof.exlibs.ExDialog.initialize("identification", html, null, winModal, dialogPosY);
            var dialogBoxID = "identification_dialog";
        }

        //show last login identity
        ns.showLastIdentity();

        //如果传入了identity，那么要检测是注册还是登录。
        if(typeof userIdentity != "undefined" && userIdentity != null){
            odof.user.identification.identityInputBoxActions(userIdentity);
        }

        //用户点击忘记密码按钮。
        jQuery("#forgot_password").bind("click", function(){
            var userIdentityVal = jQuery("#identity").val();
            ns.showForgotPwdDialog(userIdentityVal);
        });

        jQuery("#sign_up_btn").bind("click", function(){
            odof.user.identification.showRegisteMsg();
        });

        odof.user.identification.bindLoginDialogEvent();
        jQuery("#identification_pwd_ic").bind("click",function(){
            odof.comm.func.displayPassword('identification_pwd');
        });

        jQuery("#identity").focus();
    };

    ns.showForgotPwdDialog = function(userIdentity){
        //隐藏掉头像的显示。
        jQuery("#user_avatar").hide();

        jQuery("#forgot_verification_dialog").show();
        jQuery("#forgot_identity_input").val(userIdentity);
        jQuery("#cancel_forgot_verify_btn").bind("click",function(){
            jQuery("#forgot_verification_dialog").hide();
            jQuery("#fogot_verify_btn").unbind("click");
        });
        jQuery("#forgot_verification_msg").html("Confirm sending reset token to your mailbox?");
        jQuery("#fogot_verify_btn").unbind("click");
        jQuery("#fogot_verify_btn").val("Verify");
        jQuery("#fogot_verify_btn").bind("click",function(){
            var callBackFunc = function(){
                jQuery("#forgot_verification_msg").html("Verification sent, it should arrive in minutes. Please check your mailbox and follow the link.");
                jQuery("#fogot_verify_btn").val("Done");
                jQuery("#fogot_verify_btn").unbind("click");
                jQuery("#fogot_verify_btn").bind("click",function(){
                    jQuery("#forgot_verification_dialog").hide();
                    jQuery("#fogot_verify_btn").unbind("click");
                });
            };
            ns.doSendEmail(userIdentity,"resetPassword", callBackFunc);
        });

    };

    ns.showLoginStatus = function(userData){
        if(typeof userData == "undefined"
                || userData == null
                || userData == ""
                || userData.user_status == 0
        ){
            var loginMenu = '<div class="global_sign_in_btn">'
                            + '<a id="home_user_login_btn" href="javascript:void(0);">Sign in</a>'
                            + '</div>';
            jQuery("#global_user_info").html(loginMenu);

            //是否为定制的登录框。
            if(typeof showSpecialIdentityDialog == "undefined"){
                jQuery("#home_user_login_btn").unbind("click");
                jQuery("#home_user_login_btn").bind("click",function(){
                    ns.doShowLoginDialog();
                });
            }else{
                //专门为forbidden页面定制。/x/forbidden
                if(typeof pageFlag != "undefined" && pageFlag == "forbidden"){
                    jQuery("#home_user_login_btn").unbind("click");
                    jQuery("#home_user_login_btn").bind("click",function(){
                        ns.doShowLoginDialog(null, null, null, "win", 200);
                    });
                }else{
                    //专门为登录页面定制。。/s/login
                    jQuery("#home_user_login_btn").unbind("click");
                    jQuery("#home_user_login_btn").click(function() {
                        jQuery("#identity").focus();
                    });
                }
            }
        }else{
            var userPanelHTML = '<div class="uinfo">'
                                + '<em class="light" style="background:none;"></em>'
                                + '<div class="name" >'
                                + '<div id="goldLink"><a href="/s/profile" >'+userData.user_name+'</a></div>';
            userPanelHTML += '<div class="myexfe" id="myexfe"><div class="message"><div class="na">';
            userPanelHTML += '<a href="/s/profile" class="h">';
            userPanelHTML += '<span class="num_of_x">' + userData.cross_num + '</span>';
            userPanelHTML += '<span><em class="x_attended">X</em> attended</span>';
            userPanelHTML += '</a>';
            userPanelHTML += '<a href="/s/profile" class="l"><img src="'+odof.comm.func.getUserAvatar(userData.user_avatar, 80, img_url)+'"></a>';
            userPanelHTML += '</div>';
            userPanelHTML += '</div>';

            if (userData.crosses && userData.crosses.length) {
                userPanelHTML += '<div class="info">';
                var supcoming = '<div class="upcoming">Upcoming:</div>';
                supcoming += '<div class="crosseslist"><ol>';
                var eupcoming = '</ol></div>';
                var upcoming_status = 0;
                var snow = '<div class="crosseslist"><span class="now">NOW</span><ol>';
                var enow = '</ol></div>';
                var now_status = 0;
                var s24hr = '<div class="crosseslist"><span class="hr">24hr</span><ol>';
                var e24hr = '</ol></div>';
                var hr_status = 0;
                $.each(userData.crosses, function (k, v) {
                    var s = '<li><a href="/!' + v.id + '">' + v.title + '</a>';
                    switch (v.sort) {
                        case 'upcoming':
                            supcoming += s;
                            upcoming_status = 1;
                            break;
                        case 'now':
                            snow += s;
                            now_status = 1;
                            break;
                        case '24hr':
                            s24hr += s;
                            hr_status = 1;
                            break;
                    }
                });
                userPanelHTML += (upcoming_status ? supcoming + eupcoming : '')
                    + (now_status ? snow + enow : '')
                    + (hr_status ? s24hr + e24hr : '')
                    + '</div>';
            }

            userPanelHTML += '<div class="creatbtn"><a href="/x/gather">Gather</a></div>';
            userPanelHTML += '<div class="myexfefoot">';
            userPanelHTML += '<a href="/s/profile" class="l">Setting</a>';
            userPanelHTML += '<a href="/s/logout" class="r">Sign out</a></div>';
            userPanelHTML += '</div></div></div>';

            jQuery("#global_user_info").html(userPanelHTML);

            jQuery('.name').mousemove(function() {
                jQuery('#goldLink').addClass('nameh');
                jQuery('#myexfe').show();
            });
            jQuery('.name').mouseout(function() {
                jQuery('#goldLink').removeClass('nameh');
                jQuery('#myexfe').hide();
            });
        }
    };

    ns.showTokenIdentityStatus = function (identity) {
        var panel = '<div class="uinfo" data-identity-id="' + identity.id + '">'
                + '<em class="light" style="background:none;"></em>'
                + '<div class="name" >'
                    + '<div id="goldLink">'
                        + '<a href="/s/profile" >' + (identity.name || identity.external_username) + '</a>'
                    + '</div>'
                + '<div class="myexfe" id="myexfe">'
                    + '<div class="message">'
                        + '<div class="title">New Identity</div>'
                        + '<p class="detail">You are browsing this page as identity:</p>'
                        + '<div class="identity">'
                            + '<span class="email">' + (identity.external_identity || identity.external_username || identity.name) + '</span>'
                            + '<img alt="" width="20px" height="20px" src="' + identity.avatar_file_name + '" />'
                        + '</div>'
                        + '<div class="setup">'
                            + '<p>as your new <span>EXFE</span> account.</p>'
                            + '<span class="setup-btn">Set up</span>'
                        + '</div>'
                    + '</div>'
                + '</div>'
            + '</div>';

        jQuery("#global_user_info").html(panel);

        jQuery('.name').mouseenter(function() {
            jQuery('#goldLink').addClass('nameh');
            jQuery('#myexfe').show();
        });
        jQuery('.name').mouseleave(function() {
            jQuery('#goldLink').removeClass('nameh');
            jQuery('#myexfe').hide();
        });
        $('.name .setup-btn').bind('click', function (e) {
            if (login_type === 'token' && token) {
                odof.x.edit.setreadonly(clickCallBackFunc);
            }
        });
    };

    ns.showExfeVersion = function () {
        var hasDialog = $('div.ex_dialog').size();
        if (hasDialog) return;
        var buf = "<div id='identification_titles' class='titles'>"
                    + "<div><a href='#' id='identification_close_btn'>X</a></div>"
                    + "<div id='identification_handler' class='tl'>Sandbox</div>"
                + "</div>"
                + '<div class="exfe-version-info">'
                    + '<h3>“Rome wasn\'t built in a day.”</h3>'
                    + '<p><span class="blue">EXFE</span> [ˈɛksfi] is still in <span class="bold">pilot</span> stage (with <span class="oblique">SANDBOX</span> tag). We’re building up blocks of it, thus some bugs and unfinished pages. Any feedback, please email <span class="oblique">feedback@exfe.com.</span> Our apologies for any trouble you may encounter, much appreciated.</p>'
                + '</div>'
                + "<div class='identification_dialog_bottom'></div>";
        odof.exlibs.ExDialog.initialize('identification', buf, 'exfe_version identification_dialog', 'win');
        if (ns.showExfeVersion.status) {
            $('#identification_close_btn').click(function (e) {
                $(this).unbind('click');
            });
        }
    };
    ns.showExfeVersion.status = 1;

})(ns);

jQuery(document).ready(function(){
    //odof.user.status.doShowLoginDialog();
    //odof.user.status.doShowAddIdentityDialog();
    odof.user.status.checkUserLogin();

    // check accuracy of the local time
    var maxDiff   = 30 * 60,
        localUtc  = Math.round(new Date().getTime() / 1000);
    window.timeValid  = Math.abs(window.utcDiff = localUtc - utc) < 15 * 60;
    window.timeOffset = odof.comm.func.convertTimezoneToSecond(jstz.determine_timezone().offset());
});
