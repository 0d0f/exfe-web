/**
 * @Description:    Cross edit module
 * @Author:         HanDaoliang <handaoliang@gmail.com>
 * @createDate:     Sup 15,2011
 * @CopyRights:		http://www.exfe.com
**/

var moduleNameSpace = "odof.cross.edit";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    /**
     * display edit bar
     *
     * */
    ns.showEditBar = function(){
        jQuery("#edit_cross_bar").slideDown(300);
        jQuery("#submit_data").click(function(){ odof.cross.edit.submitData(); });
        jQuery("#cross_titles").addClass("enable_click");
        jQuery("#cross_titles").click(function(){ odof.cross.edit.bindEditTitlesEvent(); });

        jQuery("#cross_times_area").addClass("enable_click");
        jQuery("#cross_times_area").click(function(){ odof.cross.edit.bindEditTimessEvent(); });
    };
    /**
     * while user submit data
     *
     * */
    ns.submitData = function(){
        jQuery("#edit_cross_bar").slideUp(300);
    };
    /**
     * while user click titles, show edit textarea.
     *
     * */
    ns.bindEditTitlesEvent = function(){
        jQuery("#cross_titles").css({"display":"none"});
        jQuery("#cross_titles_textarea").css({"display":"block"});
    };
    /**
     * user edit time, show edit time area.
     *
     * */
    ns.bindEditTimessEvent = function(){
        jQuery("#cross_time_bubble").css({"display":"block"});
        var timeDisplayContainer = [document.getElementById("cross_datetime_original"),document.getElementById("cross_times")];
        exCal.initCalendar(timeDisplayContainer, 'cross_time_container');
    };
})(ns);

jQuery(document).ready(function(){
    jQuery("#edit_icon").click(function(){
        odof.cross.edit.showEditBar();
    });
});

