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
    ns.checkUserLogin = function(){
        //专门针对Cross页面，不需要去检查
        if(typeof login_type != "undefined" && login_type == "token"){
            var navMenu = '<div class="global_sign_in_btn">'
                            + '<a id="cross_identity_btn" href="javascript:void(0);">'
                            + external_identity
                            + '</a>'
                            + '</div>';
            jQuery("#global_user_info").html(navMenu);

            //If not login page
            if(typeof show_idbox != "undefined" && show_idbox == "login"){
                jQuery("#cross_identity_btn").bind("click",function(){
                    ns.doShowLoginDialog();
                });
            }
            if(typeof show_idbox != "undefined" && show_idbox == "setpassword"){
                if(token_expired == 'false'){
                    jQuery("#cross_identity_btn").bind("click",function(){
                        odof.user.status.doShowResetPwdDialog(null, 'setpwd');
                        jQuery("#show_identity_box").html(external_identity);
                    });
                }else{
                    jQuery("#cross_identity_btn").bind("click",function(){
                        var args = {"identity":external_identity};
                        odof.user.status.doShowVerificationDialog(null, args);
                    });
                }
            }

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
    ns.doShowVerificationDialog = function(dialogBoxID, args){
        var html = odof.user.identification.showdialog("reg");

        if(typeof dialogBoxID != "undefined" && typeof dialogBoxID == "string"){
            document.getElementById(dialogBoxID).innerHTML = html;
        }else{
            odof.exlibs.ExDialog.initialize("identification", html);
            var dialogBoxID = "identification_dialog";
        }

        jQuery("#identity_forgot_pwd_dialog").show();
        jQuery("#f_identity_box").html(args.identity);
        jQuery("#f_identity_box").css({"padding-top":"5px"});
        jQuery("#f_identity_hidden").val(args.identity);

        jQuery("#send_verification_btn").bind("click",function(){
            var userIdentity = jQuery("#f_identity_hidden").val();
            var mailReg = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            if(userIdentity != "" && userIdentity.match(mailReg)){
                jQuery("#submit_loading_btn").show();
                jQuery.ajax({
                    type: "GET",
                    url: site_url+"/s/SendVerification?identity="+userIdentity,
                    dataType:"json",
                    success: function(JSONData){
                        //jQuery("#identity_forgot_pwd_info").css({"color":"#CC3333"});
                        //jQuery("#identity_forgot_pwd_info").html("You’re requesting verification too frequently, please wait for several hours.");
                        jQuery("#identity_forgot_pwd_info").html("Verification sent.");
                        setTimeout(function(){
                            jQuery("#identity_forgot_pwd_dialog").hide();
                        }, 3000);
                    },
                    complete: function(){
                        jQuery("#submit_loading_btn").hide();
                    }
                });
            }
        });

    };
    ns.doShowResetPwdDialog =function(resetPwdCID, actions){
        var html = odof.user.identification.showdialog("reset_pwd");
        if(typeof resetPwdCID != "undefined" && typeof resetPwdCID == "string") {
            jQuery("#"+resetPwdCID).html(html);
        }else{
            odof.exlibs.ExDialog.initialize("identification", html);
            var dialogBoxID = "identification_dialog";
        }

        jQuery("#identification_pwd_ic").bind("click", function(){
            odof.comm.func.displayPassword('identification_pwd');
        });

        if(typeof actions == "undefined"){
            actions = "resetpwd";
        }

        jQuery("#submit_reset_password").bind("click", function(){
            var userPassword = jQuery("#identification_pwd").val();
            var userDisplayName = jQuery("#user_display_name").val();
            var userToken = jQuery("#identification_user_token").val();
            var hideErrorMsg = function(){
                jQuery("#reset_pwd_error_msg").hide();
                jQuery("#displayname_error").hide();
            }
            if(userPassword == ""){
                jQuery("#reset_pwd_error_msg").show();
                setTimeout(hideErrorMsg, 3000);
                jQuery("#reset_pwd_error_msg").html("Please input a password");
            }else if(userDisplayName == ""){
                jQuery("#reset_pwd_error_msg").show();
                jQuery("#displayname_error").show();
                setTimeout(hideErrorMsg, 3000);
                jQuery("#reset_pwd_error_msg").html("Please input a display name");
            }else if(!odof.comm.func.verifyDisplayName(userDisplayName)){
                jQuery("#reset_pwd_error_msg").show();
                jQuery("#displayname_error").show();
                setTimeout(hideErrorMsg, 3000);
                jQuery("#reset_pwd_error_msg").html("Display name Error.");
            }else{
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
                            if(actions == "setpwd")
                            {
                                window.location.href="/!"+JSONData.cross_id;
                            }
                            else
                                window.location.href="/s/profile";
                        }else{
                            jQuery("#reset_pwd_error_msg").show();
                            setTimeout(hideErrorMsg, 3000);
                        }
                    }
                });
            }
        });
        
    };
    ns.doShowChangePwdDialog =function(changePwdCID){
        var html = odof.user.identification.showdialog("change_pwd");
        if(typeof changePwdCID != "undefined" && typeof changePwdCID == "string") {
            jQuery("#"+changePwdCID).html(html);
        }
        jQuery("#identification_pwd_ic").bind("click", function(){
            odof.comm.func.displayPassword('identification_pwd');
        });
        jQuery("#identification_newpwd_ic").bind("click", function(){
            odof.comm.func.showRePassword('identification_newpwd', 'identification_renewpwd');
        });
    };
    ns.doShowLoginDialog = function(dialogBoxID, callBackFunc, userIdentity){
        var html = odof.user.identification.showdialog("reg");
        if(typeof callBackFunc != "undefined"){
            ns.callBackFunc = callBackFunc;
        }

        if(typeof dialogBoxID != "undefined" && typeof dialogBoxID == "string"){
            document.getElementById(dialogBoxID).innerHTML = html;
        }else{
            odof.exlibs.ExDialog.initialize("identification", html);
            var dialogBoxID = "identification_dialog";
        }

        //如果传入了identity，那么要检测是注册还是登录。
        if(typeof userIdentity != "undefined" && userIdentity != ""){
            odof.user.identification.identityInputBoxActions(userIdentity);
        }

        jQuery("#resetpwd").bind("click", function(){
            jQuery("#identity_forgot_pwd_dialog").show();
            jQuery("#f_identity").val(jQuery("#identity").val());
            jQuery("#cancel_verification_btn").bind("click",function(){
                jQuery("#identity_forgot_pwd_dialog").hide();
                jQuery("#send_verification_btn").unbind("click");
            });
            jQuery('#f_identity').keyup(function() {
                jQuery("#identity").val(jQuery("#f_identity").val());
                jQuery("#identity_forgot_pwd_dialog").hide();
                odof.user.identification.identityInputBoxActions();
            });
            jQuery("#send_verification_btn").bind("click",function(){
                var userIdentity = jQuery("#f_identity").val();
                var mailReg = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                if(userIdentity != "" && userIdentity.match(mailReg)){
                    jQuery("#submit_loading_btn").show();
                    jQuery.ajax({
                        type: "GET",
                        url: site_url+"/s/SendVerification?identity="+userIdentity,
                        dataType:"json",
                        success: function(JSONData){
                            //jQuery("#identity_forgot_pwd_info").css({"color":"#CC3333"});
                            //jQuery("#identity_forgot_pwd_info").html("You’re requesting verification too frequently, please wait for several hours.");
                            jQuery("#identity_forgot_pwd_info").html("Verification sent.");
                            setTimeout(function(){
                                jQuery("#identity_forgot_pwd_dialog").hide();
                            }, 3000);
                        },
                        complete: function(){
                            jQuery("#submit_loading_btn").hide();
                        }
                    });
                }
            });
        });
        jQuery("#sign_up_btn").bind("click", function(){
            odof.user.identification.showRegisteMsg();
        });

        odof.user.identification.bindDialogEvent("reg");
        jQuery("#identification_pwd_ic").bind("click",function(){
            odof.comm.func.displayPassword('identification_pwd');
        });

        var lastIdentity = odof.util.getCookie('last_identity');
        if(lastIdentity){
            jQuery("#identity").val(lastIdentity)
            jQuery("#identity_dbox").html("");
            jQuery("#identity").bind("mouseover",function(){
                jQuery("#delete_identity").show();
                setTimeout(function(){
                    jQuery("#delete_identity").hide();
                }, 3000);
            });

            //enable sign in btn
            jQuery('#sign_in_btn').attr('disabled', false);
            jQuery('#sign_in_btn').removeClass("sign_in_btn_disabled");
            jQuery('#sign_in_btn').addClass("sign_in_btn");
            jQuery('#resetpwd').show();
            jQuery('#logincheck').show();
            jQuery('#sign_up_btn').hide();

            /*
            jQuery("#identity").bind("mouseout",function(){
                jQuery("#delete_identity").hide();
            });
            */

            jQuery("#delete_identity").click(function(){
                odof.util.delCookie('last_identity', "/", ".exfe.com");
                jQuery("#identity").val("");
                jQuery("#identity_dbox").html("Your email here");
                jQuery("#identity").unbind("mouseover");
                //jQuery("#identity").unbind("mouseout");
                jQuery("#delete_identity").unbind("click");
                jQuery("#delete_identity").hide();
            });
        }
        jQuery("#identity").focus();
    };
    ns.showLoginStatus = function(userData){
        if(userData.user_status == 0){
            var loginMenu = '<div class="global_sign_in_btn">'
                            + '<a id="home_user_login_btn" href="javascript:void(0);">Sign In</a>'
                            + '</div>';
            jQuery("#global_user_info").html(loginMenu);

            //If not login page
            if(typeof showIdentificationDialog == "undefined"){
                jQuery("#home_user_login_btn").bind("click",function(){
                    ns.doShowLoginDialog();
                });
            }else{
                jQuery("#home_user_login_btn").click(function() {
                    jQuery("#identity").focus();
                });
            }
        }else{
            //如果不是从/s/login页面登录。
            if(typeof showIdentificationDialog == "undefined"){
                var userPanelHTML = '<div class="uinfo">'
                                    + '<em class="light" style="background:none;"></em>'
                                    + '<div class="name" >'
                                    + '<div id="goldLink"><a href="#" >'+userData.user_name+'</a></div>';
                userPanelHTML += '<div class="myexfe" id="myexfe"><div class="message"><div class="na">';
                userPanelHTML += '<p class="h">';
                userPanelHTML += '<span class="num_of_x">' + userData.cross_num + '</span>';
                userPanelHTML += '<span class="x_attended">X</span> attended';
                userPanelHTML += '</p>';
                userPanelHTML += '<a href="/s/profile" class="l"><img src="'+odof.comm.func.getHashFilePath(img_url,userData.user_avatar)+'/80_80_'+ userData.user_avatar +'"></a>';
                userPanelHTML += '</div>';
                if(userData.crosses != ""){
                    userPanelHTML += '<p class="info">';
                    userPanelHTML += '<span>Upcoming:</span><br />';
                    jQuery.each(userData.crosses, function(k,v){
                        if(v.sort == "upcoming"){
                            userPanelHTML += '<em>Now</em> <a href="/!'+v.id+'">'+ v.title +'</a>';
                        }else{
                            userPanelHTML += '<em>24hr</em> <a href="/!'+v.id+'">'+ v.title +'</a>';
                        }
                    });
                    userPanelHTML += '</p>';
                }
                userPanelHTML += '<p class="creatbtn"><a href="/x/gather">Gather X</a></p>';
                userPanelHTML += '</div>';
                userPanelHTML += '<div class="myexfefoot">';
                userPanelHTML += '<a href="/s/profile" class="l">Setting</a>';
                userPanelHTML += '<a href="/s/logout" class="r">Sign out</a></div>';
                userPanelHTML += '</div></div></div>';

                jQuery("#global_user_info").html(userPanelHTML);

                jQuery('.name').mousemove(function() {
                    jQuery('#goldLink a').addClass('nameh');
                    jQuery('#myexfe').show();
                });
                jQuery('.name').mouseout(function() {
                    jQuery('#goldLink a').removeClass('nameh');
                    jQuery('#myexfe').hide();
                });
            }else{
                //console.log(pageReferrerURI);
                //window.history.back(-1);
                window.location.href="/s/profile";
            }

        }
    };

})(ns);

jQuery(document).ready(function(){
    odof.user.status.checkUserLogin();
});
