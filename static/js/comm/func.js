var moduleNameSpace = "odof.comm.func";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    ns.getBak = function(){
        var oall = document.getElementById("oall");
        var lightBox = document.getElementById("fBox");
        if(oall && lightBox){
            oall.style.display = "block";
            oall.style.height = document.body.scrollHeight + "px";
            lightBox.style.display = "block";
            function reset()
            {
                var d = document.documentElement;
                var x1 = d.scrollLeft;
                var sUserAgent = navigator.userAgent;
                var isChrome = sUserAgent.indexOf("Chrome") > -1 ;
                if(isChrome){
                    var y1 = document.body.scrollTop;
                }
                else{
                    var y1 = d.scrollTop;
                }
                var w1 = d.clientWidth;
                var h1 = d.clientHeight;


                var w = parseInt(lightBox.offsetWidth);
                var h = parseInt(lightBox.offsetHeight);
                var x = Math.ceil((w1 - w)/2) + x1;
                var y = Math.ceil((h1 - h)/2) + y1;


                lightBox.style.left = x + "px";
                lightBox.style.top = y + "px";
            }
            window.onresize = reset;
            window.onscroll = reset;
            reset();

        }
    };
    ns.verifyDisplayName = function(dname){
        if(typeof dname == "undefined" || dname == ""){
            return false;
        }
        var nameREG = "^[0-9a-zA-Z_\ \'\.]+$"; 
        var re = new RegExp(nameREG);
        if(!re.test(dname)){
            return false;
        }
        return true;

    };
    ns.showRePassword = function(pwdBoxID, rePwdBoxID, type){
        if(typeof type == "undefined"){ type = "visible"; }

        var pwdBoxJID = "#"+pwdBoxID;
        var displayPwdBoxJID = "#"+pwdBoxID+"_a";
        var btnJID = "#"+pwdBoxID+"_ic";

        var rePwdBoxJID = "#"+rePwdBoxID;
        var rePwdBoxLiJID = "#"+rePwdBoxID+"_li";

        //initialize
        if(type == "visible"){
            jQuery(btnJID).unbind("click");
            if(jQuery(btnJID).hasClass("ic3")){
                jQuery(btnJID).removeClass("ic3");
            }
            jQuery(btnJID).addClass("ic2");
            jQuery(pwdBoxJID).hide();
            jQuery(displayPwdBoxJID).show();

            jQuery(displayPwdBoxJID).unbind("keyup");
            jQuery(displayPwdBoxJID).bind("keyup", function(){
                var curPwd = jQuery(displayPwdBoxJID).val();
                jQuery(pwdBoxJID).val(curPwd);
                jQuery(rePwdBoxJID).val(curPwd);
            });

            jQuery(rePwdBoxLiJID).hide();
        }

        //绑定事件。
        jQuery(btnJID).bind("click",function(){
            ns.displayPassword(pwdBoxID);

            if(jQuery(btnJID).hasClass("ic2")){
                jQuery(rePwdBoxLiJID).hide();
                jQuery(displayPwdBoxJID).bind("keyup", function(){
                    jQuery(rePwdBoxJID).val(jQuery(displayPwdBoxJID).val());
                });
            }else{
                jQuery(rePwdBoxLiJID).show();
                jQuery(displayPwdBoxJID).unbind("keyup");
                jQuery(rePwdBoxJID).val('');
            }

        });
    };
    ns.removeRePassword = function(pwdBoxID, rePwdBoxID){

        var pwdBoxJID = "#"+pwdBoxID;
        var displayPwdBoxJID = "#"+pwdBoxID+"_a";
        var btnJID = "#"+pwdBoxID+"_ic";

        var rePwdBoxJID = "#"+rePwdBoxID;
        var rePwdBoxLiJID = "#"+rePwdBoxID+"_li";

        jQuery(btnJID).unbind("click");
        if(jQuery(btnJID).hasClass("ic2")){
            jQuery(btnJID).removeClass("ic2");
        }
        jQuery(btnJID).addClass("ic3");
        jQuery(pwdBoxJID).show();
        jQuery(displayPwdBoxJID).hide();
        jQuery(displayPwdBoxJID).unbind("keyup");

        jQuery(rePwdBoxLiJID).hide();

        jQuery(btnJID).bind("click",function(){
            ns.displayPassword(pwdBoxID);
        });
    };
    ns.displayPassword = function(pwdBoxID){
        var originalBoxJID = "#"+pwdBoxID;
        var displayPWDBoxID = "#"+pwdBoxID+"_a";
        var curBtnID = "#"+pwdBoxID+"_ic";

        if(jQuery(curBtnID).hasClass("ic3")){
            jQuery(curBtnID).removeClass("ic3");
            jQuery(curBtnID).addClass("ic2");
            var originalPWDVal = jQuery(originalBoxJID).val();
            jQuery(displayPWDBoxID).val(originalPWDVal);

            jQuery(displayPWDBoxID).show();
            jQuery(originalBoxJID).hide();

            jQuery(displayPWDBoxID).bind("keyup", function(){
                jQuery(originalBoxJID).val(jQuery(displayPWDBoxID).val());
            });
        }else{
            jQuery(curBtnID).removeClass("ic2");
            jQuery(curBtnID).addClass("ic3");

            var displayPWDVal = jQuery(displayPWDBoxID).val();
            jQuery(originalBoxJID).val(displayPWDVal);

            jQuery(displayPWDBoxID).hide();
            jQuery(originalBoxJID).show();

            jQuery(originalBoxJID).bind("keyup", function(){
                jQuery(displayPWDBoxID).val(jQuery(originalBoxJID).val());
            });
        }
    };
    ns.getHashFilePath = function(rootPath, fileName){
        if(fileName == "default.png"){ return rootPath; }
        return rootPath+"/"+fileName.substring(0,1)+"/"+fileName.substring(1,3);
    };
    /*
    ns.cancel = function(){
        var oall = document.getElementById("oall");
        var lightBox = document.getElementById("fBox");
        if(oall && lightBox){
            oall.style.display = "none";
            lightBox.style.display = "none";
        }
    };
    */
})(ns);

jQuery(document).ready(function() {
    jQuery('#private_icon').mousemove(function() {
        jQuery('#private_hint').show();
    });
    jQuery('#private_icon').mouseout(function() {
        jQuery('#private_hint').hide();
    });
    jQuery('#edit_icon').mousemove(function() {
        jQuery('#edit_icon_desc').show();
    });
    jQuery('#edit_icon').mouseout(function() {
        jQuery('#edit_icon_desc').hide();
    });

    //  jQuery('.newbg').mousemove(function(){
    //	jQuery(this).addClass('fbg');
    //	jQuery('.fbg button').show();
    //});
    //
    //  jQuery('.newbg').mouseout(function(){
    //	jQuery(this).removeClass('fbg');
    //	jQuery('button').hide();
    //});
    //
    //  jQuery('.bnone').mousemove(function(){
    //	jQuery(this).addClass('bdown');
    //	jQuery('.bdown button').show();
    //});
    //
    //  jQuery('.bnone').mouseout(function(){
    //	jQuery(this).removeClass('bdown');
    //	jQuery('dd button').hide();
    //});
    //
    //
    //  jQuery('.lb').mousemove(function(){
    //	  jQuery(this).addClass('labtn');
    //	  jQuery('.labtn button').show();
    //	  jQuery('.lb span').hide()
    //});
    //
    //  jQuery('.lb').mouseout(function(){
    //	  jQuery(this).removeClass('labtn');
    //	  jQuery('button').hide();
    //	  jQuery('.lb span').show()
    //
    //});
    //
    //  jQuery('.uplb').mousemove(function(){
    //	jQuery(this).addClass('uabtn');
    //	  jQuery('.uabtn button').show();
    //	  jQuery('.uplb span').hide()
    //});
    //
    //  jQuery('.uplb').mouseout(function(){
    //	  jQuery(this).removeClass('uabtn');
    //	  jQuery('button').hide();
    //	  jQuery('.uplb span').show()
    //});

    jQuery('.lbl').mousemove(function(){
        jQuery('.lt').addClass('lton');
    });
    jQuery('.lbl').mouseout(function(){
        jQuery('.lt').removeClass('lton');
    });
    jQuery('.lbr').mousemove(function(){
        jQuery('.rt').addClass('rton');
    });
    jQuery('.lbr').mouseout(function(){
        jQuery('.rt').removeClass('rton');
    });

    // jQuery('.addjn').mousemove(function(){
    //	jQuery(this).addClass('bgrond');
    //	jQuery('.bgrond .exfee_del').show();
    //});
    //
    //  jQuery('.addjn').mouseout(function(){
    //	jQuery(this).removeClass('bgrond');
    //	jQuery('.exfee_del').hide();
    //});

    jQuery('.redate').mousemove(function(){
        jQuery(this).addClass('bgdq');
    });

    jQuery('.redate').mouseout(function(){
        jQuery(this).removeClass('bgdq');
    });

    jQuery('.coming').mousemove(function(){
        jQuery(this).addClass('bgcom');
    });

    jQuery('.coming').mouseout(function(){
        jQuery(this).removeClass('bgcom');
    });

});
