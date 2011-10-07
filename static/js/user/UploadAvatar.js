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

        if(!ns.cropper.isAvaiable()){
            alert("Sorry, your browser doesn't support FileReader, please use Firefox3.6+ or Chrome10+ to run it.");
        }
    };

    ns.selectImage = function(fileList){
        ns.cropper.loadImage(fileList[0]);
        document.getElementById("selectBtn").style.display = "none";
    };

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
        var imgData = ns.cropper.getCroppedImageData(240, 240);
        
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function(e){
            if(xhr.readyState == 4){
                if(xhr.status == 200){
                    jQuery("#upload_status").html("Upload Picture successful");
                    jQuery("#upload_status").show();
                    setTimeout(function(){
                        jQuery("#upload_status").hide();
                    },3000);
                }
            }
        };
        
        xhr.open("post", site_url+"/s/uploadAvatarNew", true);
        var data = new FormData();
        //data.append("username", "");
        data.append("size", 240);
        data.append("file", imgData);
        xhr.send(data);
    };

    ns.trace = function(){
        if(typeof(console) != "undefined"){
            console.log(Array.prototype.slice.apply(arguments).join(" "));
        }
    };
})(ns);

jQuery(document).ready(function(){
    jQuery('#changeavatar').click(function(e) {
        odof.user.uploadAvatar.init();
    });
});
