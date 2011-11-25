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
    ns.initialize = function(dialogID, contents, dialogClassName, dialogModal, dialogPosY){

        ns.createDialog(dialogID, dialogClassName, dialogModal);
        ns.dialogElement.innerHTML = contents;

        var dialogWidth = 400;
        var dialogHeight = 350;
        var pageSize = odof.util.getPageSize();
        var pageWidth = pageSize.PageW;
        var pageHeight = pageSize.PageH;
        
        var floatWrapX, floatWrapY, dragX, dragY, pX, pY, tX, tY;
        var pX = parseInt(pageWidth-dialogWidth)/2;
        var pY = parseInt(pageHeight-dialogHeight)/2;
        var cX = document.documentElement.clientWidth;
        var cY = document.documentElement.clientHeight;
        var floatWrapPerX = floatWrapPerY = floatshowOne = 0;
        var drag = false;
        var divscroll = true;
        var resizeswitch = true;

        //按照需求，距离上面的高度先写死。
        var pY = 150;

        var dialogJID = "#" + ns.dialogID;
        var dialogTitlesHandlerJID = "#" + ns.dialogTitleHandlerID;
        var dialogCloseBtnJID = "#" + ns.dialogCloseBtnID;

        if(typeof dialogPosY != "undefined" && dialogPosY != null){
            jQuery(dialogJID).css({ top:dialogPosY, left:pX });
        }else{
            jQuery(dialogJID).css({ top:pY, left:pX });
        }
        /*
        jQuery(window).scroll(function() {
            if (!drag && divscroll) {
                floatWrapX = jQuery(window).scrollLeft() + pX;
                floatWrapY = jQuery(window).scrollTop() + pY;
                jQuery(dialogJID).css({ top: floatWrapY, left: floatWrapX });
            }
        });
        */
        jQuery(window).resize(function() {
            if (!drag && resizeswitch) {
                cX = document.documentElement.clientWidth;
                cY = document.documentElement.clientHeight;
                var pSize = odof.util.getPageSize();
                var pX = parseInt(pSize.PageW-dialogWidth)/2;
                floatWrapX = jQuery(window).scrollLeft() + pX;
                floatWrapY = jQuery(window).scrollTop() + 150;
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
                console.log(floatWrapPerX);
                console.log(floatWrapPerY);
                jQuery(dialogJID).css({ top: floatWrapY, left: floatWrapX });
                pX = parseInt(jQuery(dialogJID).css("left")) - jQuery(window).scrollLeft();
                pY = parseInt(jQuery(dialogJID).css("top")) - jQuery(window).scrollTop();
            }
        });
        */
        jQuery(dialogTitlesHandlerJID).mousedown(function(event) {
            //jQuery('#floatWarpClone').remove();
            jQuery(dialogJID).clone(true).insertAfter(dialogJID).attr('id', 'floatWarpClone').show();
            //不允许双击
            //jQuery('body').bind("selectstart",function(){return false});
            jQuery(dialogTitlesHandlerJID).bind("selectstart",function(){ return false; });
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
        jQuery(dialogTitlesHandlerJID).mouseup(function() {
            jQuery(dialogJID).css({ left: tX, top: tY });
            jQuery('#floatWarpClone').remove();
            //jQuery('body').unbind("selectstart");
            jQuery(dialogTitlesHandlerJID).unbind("selectstart");
            //jQuery(dialogTitlesHandlerJID).css({cursor:'default'});
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
    ns.createDialog = function(dialogID, dialogClassName, dialogModal)
    {
        var defaultDialogClass = "ex_dialog";

        if (dialogID){
            ns.coverID = dialogID + "_cover";
            ns.dialogID = dialogID + "_dialog";
            ns.dialogTitleHandlerID = dialogID + "_handler";
            ns.dialogCloseBtnID = dialogID + "_close_btn";
        }else{
            var randID = odof.util.createRandElementID();
            ns.coverID = randID + "_cover";
            ns.dialogID = randID + "_dialog";
            ns.dialogTitleHandlerID = randID + "_handler";
            ns.dialogCloseBtnID = randID + "_close_btn";
        }
        var className = defaultDialogClass + " " + ns.dialogID;
        if(typeof dialogClassName != "undefined" && dialogClassName != "" && dialogClassName != null){
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
        if(dialogModal == "win"){
            /*
            jQuery("#"+ns.dialogID).bind("clickoutside",function(){
                odof.exlibs.ExDialog.hideDialog();
                odof.exlibs.ExDialog.destroyCover();
            });
            */
            jQuery("#"+ns.dialogID).addClass("ex_dialog_shadow");
            jQuery("#"+ns.coverID).addClass("cover_element_shadow");
        }
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
