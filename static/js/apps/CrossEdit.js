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
    ns.cross_time_bubble_status = 0;
    /**
     * display edit bar
     *
     * */
    ns.showEditBar = function(){
        jQuery("#edit_cross_bar").slideDown(300);
        jQuery("#submit_data").bind("click",function(){
            odof.cross.edit.submitData();
        });
        jQuery("#cross_titles").addClass("enable_click");
        jQuery("#cross_titles").bind("click",function(){
            odof.cross.edit.bindEditTitlesEvent();
        });

        //bind event for cross time container
        jQuery("#cross_times_area").addClass("enable_click");
        jQuery("#cross_times_area").bind("click",function(){
             odof.cross.edit.bindEditTimesEvent();
         });

        jQuery("#cross_desc").show();
        jQuery("#cross_desc_short").hide();

        jQuery("#cross_desc").addClass("enable_click");
        jQuery("#cross_desc").bind("click",function(){
            odof.cross.edit.bindEditDescEvent();
        });
    };

    /**
     * while user click titles, show edit textarea.
     *
     * */
    ns.bindEditTitlesEvent = function(){
        jQuery("#cross_titles").hide();
        jQuery("#cross_titles_textarea").show();
        jQuery('#cross_titles_textarea').bind("clickoutside",function(event) {
            if(event.target != jQuery("#cross_titles")[0]){
                jQuery("#cross_titles").html(jQuery("#cross_titles_textarea").val());
                jQuery("#cross_titles_textarea").hide();
                jQuery("#cross_titles_textarea").unbind("clickoutside");
                jQuery("#cross_titles").show();
            }
        });
    };

    /**
     * user edit time, show edit time area.
     *
     * */
    ns.bindEditTimesEvent = function(){
        //check if had bind a event for #cross_time_bubble
        var eventTemp = jQuery("#cross_time_bubble").data("events");
        //console.log(eventTemp);
        if(!eventTemp){
            jQuery('#cross_time_bubble').bind("clickoutside",function(event) {
                //console.log(event.target.parentNode);
                if(event.target.parentNode != jQuery("#cross_times_area")[0]){
                    //console.log("aaaa");
                    jQuery("#cross_time_bubble").hide();
                    jQuery("#cross_time_bubble").unbind("clickoutside");
                }else{
                    //console.log("bbbb");
                    jQuery("#cross_time_bubble").show();
                }
            });
        }
        /*
        jQuery(document).bind('click',function(e){
            console.log(e.target.parentNode);
        });
        */


        var timeDisplayContainer = [
            document.getElementById("cross_datetime_original"),
            document.getElementById("cross_times")
        ];
        exCal.initCalendar(timeDisplayContainer, 'cross_time_container',"datetime");

    };

    /**
     * User Edit cross description
     *
     * */
    ns.bindEditDescEvent = function(){
        jQuery("#cross_desc").hide();
        jQuery("#cross_desc_textarea").slideDown(400);
        jQuery('#cross_desc_textarea').bind("clickoutside",function(event) {
            if(event.target.parentNode != jQuery("#cross_desc")[0]){
                var str = odof.cross.edit.formateString(jQuery("#cross_desc_textarea").val());
                jQuery("#cross_desc").html(str);
                jQuery("#cross_desc_textarea").slideUp(400);
                jQuery("#cross_desc_textarea").unbind("clickoutside");
                jQuery("#cross_desc").show();
            }
        });
    };

    ns.formateString = function(str){
        var strstr = "0107a88030bfca5e5f72346966901d6a";
        var str = str.replace(/(\r\n|\n|\r)/gm,strstr);
        var strArr = str.split(strstr);
        var reString = "";
        for(i=0; i<strArr.length; i++){
            reString += '<p class="text">'+ strArr[i] +'</p>';
        }
        return reString;
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

    /**
     * submit call back actions
     *
     * */
    ns.callbackActions = function(JSONData){
        if(!JSONData.error){
            window.location.href = ns.editURI;
        }else{
            jQuery("#error_msg").html(JSONData.msg);
        }

    };
    /**
     * revert cross page
     *
     * */
    ns.revertCross = function(){
        window.location.href=ns.editURI;
    };

    /**
     * expand cross description
     *
     * */
    ns.expandDesc = function(){
        jQuery("#cross_desc").show();
        jQuery("#cross_desc_short").hide();
    };

})(ns);

jQuery(document).ready(function(){
    jQuery("#edit_icon").bind("click",function(){
        odof.cross.edit.showEditBar();
    });
    jQuery("#revert_cross_btn").bind("click",function(){
        odof.cross.edit.revertCross();
    });
    jQuery("#desc_expand_btn").bind("click",function(){
        odof.cross.edit.expandDesc();
    });
});

