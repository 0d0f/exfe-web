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
    ns.checkUserLogin = function(){
        var getURI = site_url+"/s/checkUserLogin";
        jQuery.ajax({
            type: "GET",
            url: getURI,
            dataType:"json",
            success: function(JSONData){
                odof.user.status.showStatus(JSONData);
            }
        });
    };
    ns.showStatus = function(userData){
        if(userData.user_status == 0){
            jQuery("#global_user_info").html('<div class="global_sign_in_btn"><a id="home_user_login_btn" href="javascript:void(0);">Sign In</a></div>');
            jQuery("#home_user_login_btn").click(function() {
                var html = showdialog("reg");
                jQuery(html).modal();
                bindDialogEvent("reg");
            });
        }else{
            var userPanelHTML = '<div class="uinfo"><em class="light"></em><div class="name" ><div id="goldLink"><a href="#" >'+userData.user_name+'</a></div>';

            userPanelHTML += '<div class="myexfe" id="myexfe" ><div class="message"><div class="na">';
            userPanelHTML += '<p class="h">';
            userPanelHTML += '<span>271</span>';
            userPanelHTML += 'exfes attended';
            userPanelHTML += '</p>';
            userPanelHTML += '<a href="/s/profile" class="l"><img src="/eimgs/80_80_<?php echo $global_avatar_file_name;?>"></a>';
            userPanelHTML += '</div>';
            userPanelHTML += '<p class="info">';
            userPanelHTML += '<span>Upcoming:</span><br />';
            userPanelHTML += '<em>Now</em>  Dinner in SF<br/>';
            userPanelHTML += '<em>24hr</em>  Bay Area VC TALK<br/>';
            userPanelHTML += 'Mary and Virushuo’s Birthday Party';
            userPanelHTML += '</p>';
            userPanelHTML += '<p class="creatbtn"><a href="/x/gather">Gather X</a></p>';
            userPanelHTML += '</div>';
            userPanelHTML += '<div class="myexfefoot"><a href="/s/profile" class="l">Setting</a><a href="/s/logout" class="r">Sign out</a></div>';
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

        }
    };
})(ns);

jQuery(document).ready(function(){
    odof.user.status.checkUserLogin();
});
