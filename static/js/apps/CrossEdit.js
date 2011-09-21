/**
 * @Description:    Cross edit module
 * @Author:         HanDaoliang <handaoliang@gmail.com>
 * @createDate:     Sup 15,2011
 * @CopyRights:		http://www.exfe.com
**/

var moduleNameSpace = "odof.cross.edit";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    ns.editURI = window.location.href;
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
        jQuery("#cross_times_area").click(function(){ odof.cross.edit.bindEditTimesEvent(); });

        jQuery("#cross_desc").addClass("enable_click");
        jQuery("#cross_desc").click(function(){ odof.cross.edit.bindEditDescEvent(); });
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
    ns.bindEditTimesEvent = function(){
        jQuery("#cross_time_bubble").css({"display":"block"});
        var timeDisplayContainer = [document.getElementById("cross_datetime_original"),document.getElementById("cross_times")];
        exCal.initCalendar(timeDisplayContainer, 'cross_time_container',"datetime");
    };
    /**
     * User Edit cross description
     *
     * */
    ns.bindEditDescEvent = function(){
        jQuery("#cross_desc").css({"display":"none"});
        jQuery("#cross_desc_textarea").slideDown(400);
    };
    /**
     * while user submit data
     *
     * */
    ns.submitData = function(){
        var title = jQuery("#cross_titles_textarea").val();
        var time = jQuery("#datetime").val();
        var desc = jQuery("#cross_desc_textarea").val();
        jQuery.ajax({
            url:ns.editURI + "/crossEdit",
            type:"POST",
            dataType:"json",
            data:{
                jrand: Math.round(Math.random()*10000000000),
                ctitle: title,
                ctime: time,
                cdesc: desc
            },
            //回调
            success:function(JSONData){
                ns.callbackActions(JSONData);
            }
        });

        //jQuery("#edit_cross_bar").slideUp(300);

    };
    ns.callbackActions = function(JSONData){
        if(!JSONData.error){
            window.location.href = ns.editURI;
        }else{
            jQuery("#error_msg").html(JSONData.msg);
        }

    };

})(ns);

jQuery(document).ready(function(){
    jQuery("#edit_icon").click(function(){
        odof.cross.edit.showEditBar();
    });
});

