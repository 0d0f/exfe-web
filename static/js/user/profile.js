/**
 * @Description:    user profile module
 * @createDate:     Sup 23,2011
 * @LastModified:   handaoliang
 * @CopyRights:		http://www.exfe.com
 **/

var moduleNameSpace = "odof.user.profile";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    /**
     * save user name
     *
     * */
    ns.saveUsername = function(name) {
        var poststr="name="+name;
        jQuery.ajax({
        type: "POST",
        data: poststr,
        url: site_url+"/s/SaveUserIdentity", 
        dataType:"json",
        success: function(data){
            if(data.response.user!=null)
            {
                var name=data.response.identity.name;
                jQuery('#profile_name').html(name);
            }
        }
        });
    };

    ns.updateAvatar = function(name) {
        jQuery.ajax({
            type: "GET",
            url: site_url+"/s/GetUserProfile", 
            dataType:"json",
            success: function(data){
                if(data.response.user!=null)
                {
                var name=data.response.user.avatar_file_name;
                var Timer=new Date();
                jQuery('#profile_avatar').html("<img class=big_header src='/eimgs/80_80_"+name+"?"+Timer.getTime()+"'/>");
                //<div id="profile_avatar"><img class="big_header" src=
                }
            }
        });
    };
})(ns);

jQuery(document).ready(function(){
    jQuery('#editprofile').click(function(e){
        if(jQuery('#profile_name').attr("status")=='view')
        {
            jQuery('#profile_name').html("<input id='edit_profile_name' value='"+jQuery('#profile_name').html()+"'>");
            jQuery('#profile_name').attr("status","edit");
            jQuery('#changeavatar').show();
        } else {
            var name_val=jQuery("#edit_profile_name").val();
            jQuery('#profile_name').html(name_val);
            odof.user.profile.saveUsername(name_val);
            jQuery('#profile_name').attr("status","view");
            jQuery('#changeavatar').hide();
        }
    });
    jQuery('#changeavatar').click(function(e){
        var AWnd=window.open('/s/uploadavatar','fwId','resizable=yes,scrollbars=yes,width=600,height=600');
        AWnd.focus();
    });
});
