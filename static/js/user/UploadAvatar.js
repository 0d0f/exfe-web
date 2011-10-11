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
        ns.cropper = new ImageCropper(300, 300, 240, 240);
        ns.cropper.setCanvas("cropper");
        ns.cropper.addPreview("preview80");

        /*
        if(!ns.cropper.isAvailable()){
            alert("Sorry, your browser doesn't support FileReader, please use Firefox3.6+ or Chrome10+ to run it.");
        }
        */
    };

    /*
    ns.imageSelect = function(evt){
        var files = evt.target.files;
        ns.cropper.loadImage(files[0]);
        document.getElementById("selectBtn").style.display = "none";
        document.getElementById("dragdrop_info").style.display = "none";
    };

    ns.dragImageSelect = function(evt){
        evt.stopPropagation();
        evt.preventDefault();

        var files = evt.dataTransfer.files;
        ns.cropper.loadImage(files[0]);
        document.getElementById("selectBtn").style.display = "none";
        document.getElementById("dragdrop_info").style.display = "none";
    };
    */

    ns.rotateImage = function(e){
        switch(e.target.id){
            case "rotateLeftBtn":
                ns.cropper.rotate(-90);
                break;
            case "rotateRightBtn":
                ns.cropper.rotate(90);
                break;
        }
    };

    ns.saveImage = function(){
        var bigImgData = ns.cropper.getCroppedImageData(240, 240);
        var smallImgData = ns.cropper.getCroppedImageData(80, 80);

        jQuery.ajax({
            url:site_url+ "/s/uploadAvatarNew",
            type:"POST",
            dataType:"json",
            data:{
                jrand: Math.round(Math.random()*10000000000),
                iName: "images",
                iSize: 240,
                iSmallFile: smallImgData,
                iBigFile: bigImgData,
            },
            success:function(JSONData){
                odof.user.uploadAvatar.callbackActions(JSONData);
            }
        });
    };

    ns.callbackActions = function(JSONData){
        jQuery("#upload_status").html("Upload Picture successful");
        jQuery("#upload_status").show();
        setTimeout(function(){
            jQuery("#upload_status").hide();
            window.location.href=site_url+"/s/profile";
        },1000);
    };

    ns.handleDragOver = function(evt){
        evt.stopPropagation();
        evt.preventDefault();
    };

    ns.trace = function(){
        if(typeof(console) != "undefined"){
            console.log(Array.prototype.slice.apply(arguments).join(" "));
        }
    };

})(ns);

jQuery(document).ready(function(){
    var getElement = function(id) { return document.getElementById(id); };
    //jQuery('#changeavatar').click(function(e) {
        odof.user.uploadAvatar.init();
    //});
    /*
    getElement('avatar_files').addEventListener('change', odof.user.uploadAvatar.imageSelect, false);
    getElement('cropper').addEventListener('dragover', odof.user.uploadAvatar.handleDragOver, false);
    getElement('cropper').addEventListener('drop', odof.user.uploadAvatar.dragImageSelect, false);
    */
});
