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

    ns.getUTF8Length = function(str){
        if(typeof str == "undefined" || str == ""){ return 0; }
        var len = 0;
        for (var i = 0; i < str.length; i++){
            charCode = str.charCodeAt(i);
            if (charCode < 0x007f){
                len += 1;
            } else if ((0x0080 <= charCode) && (charCode <= 0x07ff)){
                len += 2;
            } else if ((0x0800 <= charCode) && (charCode <= 0xffff)){
                len += 3;
            }
        }
        return len;
    };

    ns.verifyDisplayName = function(dname){
        if(typeof dname == "undefined" || dname == ""){
            return false;
        }
        var nameLength = ns.getUTF8Length(dname);
        //var nameREG = "^[0-9a-zA-Z_\ \'\.]+$";
        //var nameREG = "^(?!_)(?!.*?_$)[a-zA-Z0-9_\u4e00-\u9fa5]+$";
        var nameREG = "^[0-9a-zA-Z_\u4e00-\u9fa5\ \'\.]+$";
        var re = new RegExp(nameREG);
        if(!re.test(dname) || nameLength > 30){
            return false;
        }
        return true;

    };
    ns.initRePassword = function(pwdBoxID, rePwdBoxID, type){
        var displayType = "visible";
        if(typeof type != "undefined"){
            displayType = type;
        }

        var pwdBoxJID = "#"+pwdBoxID;
        var displayPwdBoxJID = "#"+pwdBoxID+"_a";
        var btnJID = "#"+pwdBoxID+"_ic";

        var rePwdBoxJID = "#"+rePwdBoxID;
        var rePwdBoxLiJID = "#"+rePwdBoxID+"_li";

        //initialize
        if(displayType == "visible"){
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
        }else{
            if(jQuery(btnJID).hasClass("ic2")){
                jQuery(btnJID).removeClass("ic2");
            }
            jQuery(btnJID).addClass("ic3");
            jQuery(pwdBoxJID).show();
            jQuery(displayPwdBoxJID).hide();

            jQuery(pwdBoxJID).unbind("keyup");
            jQuery(pwdBoxJID).bind("keyup", function(){
                var curPwd = jQuery(pwdBoxJID).val();
                jQuery(displayPwdBoxJID).val(curPwd);
            });

            jQuery(rePwdBoxLiJID).show();
        }

        //绑定事件。
        jQuery(btnJID).unbind("click");
        jQuery(btnJID).bind("click",function(){
            ns.showRePassword(pwdBoxID, rePwdBoxID);
        });
    };
    ns.showRePassword = function(pwdBoxID, rePwdBoxID){//class 'ic3' repwd可见
        var pwdBoxJID = "#"+pwdBoxID;
        var displayPwdBoxJID = "#"+pwdBoxID+"_a";
        var btnJID = "#"+pwdBoxID+"_ic";

        var rePwdBoxJID = "#"+rePwdBoxID;
        var rePwdBoxLiJID = "#"+rePwdBoxID+"_li";

        //do effect.*****************
        if(jQuery(btnJID).hasClass("ic2")){//rePwd可见。
            jQuery(btnJID).removeClass("ic2");
            jQuery(btnJID).addClass("ic3");
            jQuery(pwdBoxJID).show();
            jQuery(displayPwdBoxJID).hide();

            jQuery(rePwdBoxLiJID).show();
            jQuery(displayPwdBoxJID).unbind("keyup");
            jQuery(rePwdBoxJID).val('');

            jQuery(rePwdBoxJID).bind("blur",function(){
                if(jQuery(pwdBoxJID).val() != jQuery(rePwdBoxJID).val()){
                    jQuery('#pwd_hint').html("<span style='color:#CC3333'>Passwords don't match.</span>");
                    jQuery('#pwd_match_error').show();
                    jQuery('#pwd_hint').show();
                    setTimeout(function(){
                        jQuery('#pwd_match_error').hide();
                        jQuery('#pwd_hint').hide();
                    }, 3000);
                }
            });
        }else{
            jQuery(btnJID).removeClass("ic3");
            jQuery(btnJID).addClass("ic2");
            jQuery(pwdBoxJID).hide();
            jQuery(displayPwdBoxJID).show();

            //初始化一下。
            var curPwd = jQuery(pwdBoxJID).val();
            jQuery(displayPwdBoxJID).val(curPwd);
            jQuery(rePwdBoxJID).val(curPwd);

            //取消重复输入框的事件绑定。
            jQuery(rePwdBoxJID).unbind("blur");

            //绑定事件到可见的框。
            jQuery(displayPwdBoxJID).unbind("keyup");
            jQuery(displayPwdBoxJID).bind("keyup", function(){
                var curPwd = jQuery(displayPwdBoxJID).val();
                jQuery(pwdBoxJID).val(curPwd);
                jQuery(rePwdBoxJID).val(curPwd);
            });

            jQuery(rePwdBoxLiJID).hide();
        }

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
        if(fileName == "default.png"){ return rootPath+"/web"; }
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
