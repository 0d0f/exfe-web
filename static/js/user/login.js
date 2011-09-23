/**
 * @Description:    user login module
 * @createDate:     Sup 23,2011
 * @LastModified:   handaoliang
 * @CopyRights:		http://www.exfe.com
 **/
var moduleNameSpace = "odof.user.login";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    ns.checkIdentity = function(){
        var getURI = site_url+"/s/IfIdentityExist?identity="+jQuery('#identity').val();
        jQuery.ajax({
            type: "GET",
            url: getURI,
            dataType:"json",
            success: function(data){
                if(data!=null) {
                    if(data.response.identity_exist=="false") {
                        //identity
                        jQuery('#hint').show();
                        jQuery('#retype').show();
                        jQuery('#displayname').show();
                        jQuery('#resetpwd').hide();
                    }
                    else if(data.response.identity_exist=="true") {
                        jQuery('#hint').hide();
                        jQuery('#retype').hide();
                        jQuery('#displayname').hide();
                        jQuery('#resetpwd').show();
                    }
                }
            }
        });
    };
})(ns);

jQuery(document).ready(function(){
    jQuery('#identity').blur(function() {
        odof.user.login.checkIdentity();
    });
});
