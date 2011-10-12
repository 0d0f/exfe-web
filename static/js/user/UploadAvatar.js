/**
 * @Description:    upload user avatar
 * @createDate:     Oct 07,2011
 * @author:         handaoliang
 * @LastModified:   handaoliang
 * @CopyRights:		http://www.exfe.com
 **/
var moduleNameSpace = "odof.user.uploadAvatar";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    ns.cropper = null;
    ns.init = function(){   
        //display upload windows.
        jQuery("#close_upload_avatar_window_btn").bind("click",function(e){
            jQuery("#upload_avatar_window").hide();
            jQuery("#close_upload_avatar_window_btn").unbind("click");
        });
        jQuery("#upload_avatar_window").show();

        //create file uploader object
        var fileUploaderObj = new exFileUploader.initialize({
            element: document.getElementById('upload_btn_container'),
            action: '/s/uploadAvatarFile',
            onProgress: odof.user.uploadAvatar.fileUploadProgressCallBack,
            onComplete: odof.user.uploadAvatar.fileUploadCompleteCallBack
        });


    };
    ns.fileUploadProgressCallBack = function(){
        jQuery("#upload_files_process_status").show();
    };
    ns.fileUploadCompleteCallBack = function(id, fileName, responseJSON){
        if(responseJSON.error){
            window.location.href = site_url + "/s/profile";
        }
        
        jQuery("#upload_btn_container").hide();
        jQuery("#dragdrop_info").hide();

		var img = "/eimgs/300_300_"+responseJSON.filename;

        jQuery('#preview').html(img);
        jQuery('#img_src').val(responseJSON.filename);				
        jQuery('#preview').html('<img src="'+img+'" />');	
		jQuery('#div_upload_big').html('<img id="big" src="'+img+'" />');						

        jQuery('#upload_thumb').show();
        jQuery('#big').imgAreaSelect({
            aspectRatio: '1:1',
            handles: true,
            hide:false,
            show:true,
            fadeSpeed: 200,
            resizeable:false,
            maxHeight:300,
            maxWidth:300,
            minHeight:80,
            minWidth:80,
            x1: 0,
            y1: 0,
            x2: 80,
            y2: 80,
            onSelectChange: ns.previewImages
        });
    };

    ns.previewImages = function(img, selection) {
        if (!selection.width || !selection.height){ return; }
            
        //200 is the #preview dimension, change this to your liking
        var scaleX = 80 / selection.width; 
        var scaleY = 80 / selection.height;

        jQuery('#preview img').css({
            width: Math.round(scaleX * 300),
            height: Math.round(scaleY * 300),
            marginLeft: -Math.round(scaleX * selection.x1),
            marginTop: -Math.round(scaleY * selection.y1)
        });
        
        
        jQuery('.x1').val(selection.x1);
        jQuery('.y1').val(selection.y1);
        jQuery('.x2').val(selection.x2);
        jQuery('.y2').val(selection.y2);
        jQuery('.width').val(selection.width);
        jQuery('.height').val(selection.height);    
    };

    ns.saveImage = function(){
        var imageName = jQuery("#img_src").val();
        var imageHeight = jQuery("#height").val();
        var imageWidth = jQuery("#width").val();
        var imageX = jQuery("#x1").val();
        var imageY = jQuery("#y1").val();
        var imageX1 = jQuery("#x2").val();
        var imageY1 = jQuery("#y2").val();
        if(imageHeight == 0 || imageWidth == 0 || imageY == "" || imageX == ""){
            window.alert("Please select a area to cropper as a avatar");
        }else{
            jQuery.ajax({
                url:site_url+ "/s/uploadAvatarNew",
                type:"POST",
                dataType:"json",
                data:{
                    jrand: Math.round(Math.random()*10000000000),
                    iName: imageName,
                    iHeight: imageHeight,
                    iWidth: imageWidth,
                    iX: imageX,
                    iY: imageY
                },
                success:function(JSONData){
                    odof.user.uploadAvatar.callbackActions(JSONData);
                }
            });
        }
    };
    ns.callbackActions = function(JSONData){
        jQuery("#upload_status").html("Upload Picture successful");
        jQuery("#upload_status").show();
        setTimeout(function(){
            jQuery("#upload_status").hide();
            window.location.href=site_url+"/s/profile";
        },1000);
    };
})(ns);

jQuery(document).ready(function(){
    var getElement = function(id) { return document.getElementById(id); };
    jQuery('#changeavatar').click(function(e) {
        odof.user.uploadAvatar.init();
    });
});
