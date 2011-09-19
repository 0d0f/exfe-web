/**
 * @Description:    gather edit module
 * @Author:         HanDaoliang <handaoliang@gmail.com>
 * @createDate:     Sup 15,2011
 * @CopyRights:		http://www.exfe.com
 **/

var moduleNameSpace = "odof.gather.edit";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    ns.showEditBar = function(){
        jQuery("#edit_gather_bar").css({"display":"block"});
        jQuery("#submit_data").click(function(){ odof.gather.edit.submitData(); });
    };
    ns.submitData = function(){
        jQuery("#edit_gather_bar").css({"display":"none"});
    };

})(ns);

jQuery(document).ready(function(){
    jQuery("#edit_icon").click(function(){
        odof.gather.edit.showEditBar();
    });
});

