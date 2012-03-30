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

    ns.getHashFilePath = function(fileName, rootPath){
        if(fileName == "default.png"){ return rootPath+"/web"; }
        if(typeof rootPath != "undefined"){
            return rootPath+"/"+fileName.substring(0,1)+"/"+fileName.substring(1,3);
        }
        return fileName.substring(0,1)+"/"+fileName.substring(1,3);
    };

    ns.getUserAvatar = function(fileName, fileSize, rootPath){
        pattern = /^(http[s]?:\/\/)/;
        if(fileName.match(pattern)){ return fileName; }
        if(typeof fileSize == "undefined"){ fileSize = 80; }
        var avatarURL = ns.getHashFilePath(fileName,rootPath) + "/" + fileSize + "_" + fileSize + "_" + fileName;
        return avatarURL;
    };

    ns.convertTimezoneToSecond = function(tz){
        tz = tz ? tz : '';
        var offsetSign = tz.substr(0,1);
        offsetSign = offsetSign==0 ? "+" : offsetSign;
        var offsetDetail = tz.substr(1).split(":");
        var offsetSecond = (parseInt(offsetDetail[0]*60)+parseInt(offsetDetail[1]))*60;
        offsetSecond = parseInt(offsetSign + offsetSecond);

        return offsetSecond;
    };

    ns.getTimezone = function() {
        var rawTimezone = Date().toString().replace(/^.+([a-z]{3}[+-]\d{4}).+$/i, '$1'),
            tagTimezone = rawTimezone.replace(/^([a-z]{3}).+$/i, '$1'),
            numTimezone = rawTimezone.replace(/^[a-z]{3}([+-])(\d{2})(\d{2})$/i, '$1$2:$3');
        return numTimezone + (tagTimezone === 'UTC' ? '' : (' ' + tagTimezone));
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

    /**
     * parse exfee id
     * by Leask
     */
    ns.parseId = function(strId) {
        strId = odof.util.trim(strId);
        if (/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/.test(strId)) {
            var iLt = strId.indexOf('<'),
                iGt = strId.indexOf('>');
            return {name              : odof.util.trim(odof.util.cutLongName(odof.util.trim(strId.substring(0, iLt)).replace(/^"|^'|"$|'$/g, ''))),
                    external_identity : odof.util.trim(strId.substring(++iLt, iGt)),
                    provider          : 'email'};
        } else if (/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/.test(strId)) {
            return {name              : odof.util.trim(odof.util.cutLongName(strId.split('@')[0])),
                    external_identity : strId,
                    provider          : 'email'};
        } else if (/^@[a-z0-9_]{1,15}$|^@[a-z0-9_]{1,15}@twitter$|^[a-z0-9_]{1,15}@twitter$/i.test(strId)) {
            strId = strId.replace(/^@|@twitter$/ig, '');
            return {name              : strId,
                    external_identity : '@' + strId + '@twitter',
                    external_username : strId,
                    provider          : 'twitter'};
        } else if (/^[a-z0-9_]{1,15}@facebook$/i.test(strId)) {
            strId = strId.replace(/@facebook$/ig, '');
            return {name              : strId,
                    external_identity : strId + '@facebook',
                    external_username : strId,
                    provider          : 'facebook'};
        } else {
            return {external_identity : strId,
                    provider          : null};
        }
    };

})(ns);
