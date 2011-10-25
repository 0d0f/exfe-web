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
    };
    ns.doShowLoginDialog = function(identityDialogBoxID, callBackFunc){
        var html = odof.user.identification.showdialog("reg");
        if(typeof callBackFunc != "undefined"){
            ns.callBackFunc = callBackFunc;
        }
        if(typeof identityDialogBoxID != "undefined" && typeof identityDialogBoxID == "string"){
            document.getElementById(identityDialogBoxID).innerHTML = html;
        }else{
            odof.exlibs.ExDialog.initialize("identification", html);
        }

        odof.user.identification.bindDialogEvent("reg");
        jQuery("#identification_pwd_ic").bind("click",function(){
            odof.comm.func.displayPassword('identification_pwd');
        });

        var lastIdentity = odof.util.getCookie('last_identity');
        if(lastIdentity){
            jQuery("#identity").val(odof.util.getCookie('last_identity'))
            jQuery("#identity_dbox").html("");
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
                                    + '<em class="light"></em>'
                                    + '<div class="name" >'
                                    + '<div id="goldLink"><a href="#" >'+userData.user_name+'</a></div>';
                userPanelHTML += '<div class="myexfe" id="myexfe" ><div class="message"><div class="na">';
                userPanelHTML += '<p class="h">';
                userPanelHTML += '<span>' + userData.cross_num + '</span>';
                userPanelHTML += 'exfes attended';
                userPanelHTML += '</p>';
                userPanelHTML += '<a href="/s/profile" class="l"><img src="/eimgs/80_80_'+ userData.user_avatar +'"></a>';
                userPanelHTML += '</div>';
                if(userData.crosses != ""){
                    userPanelHTML += '<p class="info">';
                    userPanelHTML += '<span>Upcoming:</span><br />';
                    jQuery.each(userData.crosses, function(k,v){
                        if(v.sort == "upcoming"){
                            userPanelHTML += '<em>Now</em>  <a href="/!'+v.id+'" style="color:#0591AF">'+ v.title +'</a><br/>';
                        }else{
                            userPanelHTML += '<em>24hr</em>  <a href="/!'+v.id+'" style="color:#0591AF">'+ v.title +'</a><br/>';
                        }
                    });
                    userPanelHTML += '</p>';
                }
                /*
                userPanelHTML += '<p class="info">';
                userPanelHTML += '<span>Upcoming:</span><br />';
                userPanelHTML += '<em>Now</em>  Dinner in SF<br/>';
                userPanelHTML += '<em>24hr</em>  Bay Area VC TALK<br/>';
                userPanelHTML += 'Mary and Virushuo’s Birthday Party';
                userPanelHTML += '</p>';
                */
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
