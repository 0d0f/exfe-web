/**
 * @Description:    ExDialog Module
 * @Author:         HanDaoliang <handaoliang@gmail.com>
 * @createDate:     Oct 17,2011
 * @CopyRights:     http://www.exfe.com
**/
var moduleNameSpace = "odof.exlibs.ExDialog";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    ns.version = "1.0.0";

    /*
     * initialize dialog module
     * */
    ns.initialize = function(dialogID, contents, dialogClassName){

        ns.createDialog(dialogID, dialogClassName);
        ns.dialogElement.innerHTML = contents;

        var dialogWidth = 463;
        var pageSize = odof.util.getPageSize();
        var pageWidth = pageSize.PageW;
        var pageHeight = pageSize.PageH;
        
        var floatWrapX, floatWrapY, dragX, dragY, pX, pY, tX, tY;
        var pX = parseInt(pageWidth-dialogWidth)/2;
        var pY = 20;
        var cX = document.documentElement.clientWidth;
        var cY = document.documentElement.clientHeight;
        var floatWrapPerX = floatWrapPerY = floatshowOne = 0;
        var drag = false;
        var divscroll = true;
        var resizeswitch = true;

        var dialogJID = "#" + ns.dialogID;
        var dialogTitlesJID = "#" + ns.dialogTitleID;
        var dialogCloseBtnJID = "#" + ns.dialogCloseBtnID;

        jQuery(dialogJID).css({ top:23, left:parseInt(pageWidth-dialogWidth)/2 });
        jQuery(window).scroll(function() {
            if (!drag && divscroll) {
                floatWrapX = jQuery(window).scrollLeft() + pX;
                floatWrapY = jQuery(window).scrollTop() + pY;
                jQuery(dialogJID).css({ top: floatWrapY, left: floatWrapX });
            }
        });
        /*
        jQuery(window).resize(function() {
            if (!drag && resizeswitch) {
                cX = document.documentElement.clientWidth;
                cY = document.documentElement.clientHeight;
                floatWrapX = jQuery(window).scrollLeft() + cX * floatWrapPerX;
                floatWrapY = jQuery(window).scrollTop() + cY * floatWrapPerY;
                jQuery(dialogJID).css({ top: floatWrapY, left: floatWrapX });
                pX = parseInt(jQuery(dialogJID).css("left")) - jQuery(window).scrollLeft();
                pY = parseInt(jQuery(dialogJID).css("top")) - jQuery(window).scrollTop();
            }
        });
        */
        jQuery(dialogTitlesJID).mousedown(function(event) {
            //jQuery('#floatWarpClone').remove();
            jQuery(dialogJID).clone(true).insertAfter(dialogJID).attr('id', 'floatWarpClone').show();
            //不允许双击
            //jQuery('body').bind("selectstart",function(){return false});
            jQuery(dialogTitlesJID).bind("selectstart",function(){ return false; });
            jQuery(dialogJID).hide();
            dragX = (jQuery(window).scrollLeft() + event.clientX) - (parseInt(jQuery(dialogJID).css("left")));
            dragY = (jQuery(window).scrollTop() + event.clientY) - (parseInt(jQuery(dialogJID).css("top")));
            drag = true;
        });
        jQuery('body').mousemove(function(event) {
            if (drag) {
                tX = event.pageX - dragX;
                tY = event.pageY - dragY;
                jQuery('#floatWarpClone').css({ left: tX, top: tY });
                pX = tX - jQuery(window).scrollLeft();
                pY = tY - jQuery(window).scrollTop();
                floatWrapPerX = pX / cX;
                floatWrapPerY = pY / cY;
            }
        });
        jQuery(dialogTitlesJID).mouseup(function() {
            jQuery(dialogJID).css({ left: tX, top: tY });
            jQuery('#floatWarpClone').remove();
            //jQuery('body').unbind("selectstart");
            jQuery(dialogTitlesJID).unbind("selectstart");
            //jQuery(dialogTitlesJID).css({cursor:'default'});
            jQuery(dialogJID).show();
            drag = false;
        });
        jQuery(dialogCloseBtnJID).click(function() {
            ns.hideDialog();
            ns.destroyCover();
            floatshowOne = 0;
        });

    };

    /**
     * create dialog function
     * */
    ns.createDialog = function(dialogID, dialogClassName)
    {
        var defaultDialogClass = "ex_dialog";

        if (dialogID){
            ns.coverID = dialogID + "_cover";
            ns.dialogID = dialogID + "_dialog";
            ns.dialogTitleID = dialogID + "_handler";
            ns.dialogCloseBtnID = dialogID + "_close_btn";
        }else{
            var randID = odof.util.createRandElementID();
            ns.coverID = randID + "_cover";
            ns.dialogID = randID + "_dialog";
            ns.dialogTitleID = randID + "_handler";
            ns.dialogCloseBtnID = randID + "_close_btn";
        }
        var className = defaultDialogClass + " " + ns.dialogID;
        if(typeof dialogClassName != "undefined"){
            className = defaultDialogClass + " " +dialogClassName;
        }

        // If had create cover return NULL
        if(document.getElementById(ns.dialogID)){
            document.getElementById(ns.dialogID).style.display = "block";
        }else{
            //create dialog element
            ns.dialogElement = odof.util.createElement("div", ns.dialogID, className);
            document.body.insertBefore(ns.dialogElement,document.body.firstChild);
        }
        odof.exlibs.ExDialog.createCover();
    };

    ns.hideDialog = function(){
        jQuery("#" + ns.dialogID).hide();
    };

    /*
     * initialize cover module
     * */
    ns.createCover = function(){
        // If had create cover return NULL
        if(document.getElementById(ns.coverID)){
            document.getElementById(ns.coverID).style.display = "block";
        }else{
            // Create a element for display cover
            ns.coverElement = odof.util.createElement("div", ns.coverID, "cover_element");
            ns.resizeCover();
            document.body.appendChild(ns.coverElement);
            //jQuery("#"+ns.coverID).dblclick(function(){ return false; });
            
            jQuery(window).resize(ns.resizeCover);
            //双击不允许选择内容。
            //jQuery("body").bind("selectstart",function(){ return false; });
            jQuery("#"+ns.coverID).bind("selectstart",function(){ return false; });
        }

    };
    /*
     * resize cover element 
     * */
    ns.resizeCover = function() {
        var pageSize = odof.util.getPageSize();
        pageWidth = pageSize.PageW;
        pageHeight = pageSize.PageH;

        ns.coverElement.style.width = pageWidth + "px";
        ns.coverElement.style.height = pageHeight + "px";
    };

    /*
     * distroy cover element 
     * */
    ns.destroyCover = function() {
        //Find cover element
        var coverElementObj = document.getElementById(ns.coverID);
        // If have cover element, delete it.
        if(coverElementObj){
            document.body.removeChild(coverElementObj);
        }
        //解除绑定。
        //jQuery("body").unbind("selectstart");
        jQuery("#"+ns.coverID).unbind("selectstart");
    };


})(ns);

jQuery(document).ready(function() {



});
