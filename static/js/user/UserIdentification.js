/**
 * @Description:    user login module
 * @createDate:     Sup 23,2011
 * @CopyRights:		http://www.exfe.com
 **/
var moduleNameSpace = "odof.user.identification";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    ns.getUrlVars = function() {
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for(var i = 0; i < hashes.length; i++)
        {
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
    };

    ns.showIdentityInfo = function(){
        if(jQuery("#identity").val()){
            jQuery("#identity_dbox").html('');
        }else{
            jQuery("#identity_dbox").html('Your email here');
        }
    };

    ns.showdialog = function(type) {
        var title="", desc="", form="";
        if(type=="setpassword") {
            title="Set Password";
            desc="<div class='setpassword'>Please set password to keep track of <br/> RSVP status and engage in.</div>";

            form="<form id='identityform' accept-charset='UTF-8' action='' method='post'>"
                +"<ul>"
                +"<li><label>Identity:</label>"
                +"<input id='identity' name='identity' type='text' class='inputText' disabled='disabled' value='"
                +external_identity
                +"'><em class='ic1'></em></li>"
                +"<li><label>Password:</label>"
                +"<input type='password'  name='password' class='inputText'/><em class='ic2'></em></li>"
                +"<li id='retype' ><label>Re-type:</label>"
                +"<input type='text'  name='retypepassword' class='inputText'/><em class='ic3'></em></li>"
                +"<li id='displayname'><label>Names:</label>"
                +"<input type='text'  name='displayname' class='inputText'/><em class='warning'></em></li>"
                +"<li><a href='#'>Cancel</a><input type='submit' name='setpwddone' value='Done' class='sub'/></li>"
                +"<li id='pwd_hint' style='display:none' class='notice'><span>check password</span></li>"
                +"</ul>"
                +"</form>"
        } else if(type=="login") {
            title="Sign In";
            /*
            desc="<div class='account'><p>Authorize with your <br/> existing accounts </p><span><img src='/static/images/facebook.png' alt='' width='32' height='32' />"
                +"<img src='/static/images/twitter.png' alt='' width='32' height='32' /> "
                +"<img src='/static/images/google.png' alt='' width='32' height='32' /> "
                +"</span> <h4>Enter your identity information</h4></div>";
            */
            form = "<form id='loginform' accept-charset='UTF-8' action='' method='post'>"
                +"<ul>"
                +"<li><label>Identity:</label><input id='loginidentity' name='loginidentity' type='text' class='inputText' value='"+external_identity+"' ><em class='ic1'></em></li>"
                +"<li><label>Password:</label><input type='password'  name='password' class='inputText'/><em class='ic2'></em></li>"
                +"<li id='login_hint' style='display:none' class='notice'><span>Incorrect identity or password</span></li>"
                //+"<li id='retype' style='display:none'><label>Re-type:</label><input type='text'  name='retypepassword'class='inputText'/><em class='ic3'></em></li>"
                //+"<li id='displayname' style='display:none'><label>Names:</label><input type='text'  name='displayname'class='inputText'/><em class='warning'></em></li>"
                +"<li class='logincheck' id='logincheck'><input type='checkbox' value='1' name='auto_signin' id='auto_signin'><span>Sign in automatically</span></li>"
                +"<li><input id='resetpwd' type='submit' value='Reset Password...' class='changepassword'/><input type='submit' value='Sign In' class='sub'/></li>"
                +"</ul>"
                +"</form>"

        } else if(type=="reg") {
            title="Identification";

            desc = "<div class='account' style='text-align:center; height:40px; font-size:18px;'>"
                 + "Welcome to <span style='color:#0591AC;'>EXFE</span></div>"
                 + "<div style='font-size:14px;'>Enter your identity information:</div>";
            /*
            desc="<div class='account'><p>Authorize with your <br/> existing accounts </p>"
                +"<span><img src='/static/images/facebook.png' alt='' width='32' height='32' />"
                +"<img src='/static/images/twitter.png' alt='' width='32' height='32' />"
                +"<img src='/static/images/google.png' alt='' width='32' height='32' /></span>"
                +"<h4>Enter your identity information</h4>"
                +"</div>";
            */
            form="<form id='identificationform' accept-charset='UTF-8' action='' method='post'>"
                +"<ul>"
                +"<li><label>Identity:</label>"
                +"<div class='identity_box'>"
                +"<input id='identity' name='identity' type='text' class='inputText' style='margin-left:0px;' onkeyup='javascript:odof.user.identification.showIdentityInfo();' onchange='javascript:odof.user.identification.showIdentityInfo();' />"
                +"</div>"
                +"<div id='identity_dbox'>Your email here</div>"
                +"<em class='loading' id='identity_verify_loading' style='display:none;'></em>"
                +"<em class='delete' id='delete_identity' style='display:none;'></em>"
                +"</li>"
                +"<li id='hint' style='display:none' class='notice'><span>You're creating a new identity!</span></li>"
                +"<li><label>Password:</label><input type='password' id='identification_pwd' name='password' class='inputText' />"
                +"<input type='text' id='identification_pwd_a' class='inputText' style='display:none;' />"
                +"<em class='ic3' id='identification_pwd_ic'></em>"
                +"</li>"
                +"<li id='login_hint' style='display:none' class='notice'><span>Incorrect identity or password</span></li>"
                +"<li id='retype' style='display:none'>"
                +"<label>Re-type:</label>"
                +"<input type='password' id='identification_rpwd' name='retypepassword' class='inputText' />"
                +"</li>"
                +"<li id='pwd_hint' style='display:none' class='notice'><span>check password</span></li>"
                +"<li id='displayname' style='display:none'><label>Display name:</label>"
                +"<input  type='text'  name='displayname'class='inputText'/>"
                +"<em id='displayname_error' class='warning' style='display:none;'></em></li>"
                +"<li class='logincheck' id='logincheck' style='display:none;'>"
                +"<input type='checkbox' value='1' name='auto_signin' id='auto_signin' checked /><span>Sign in automatically</span></li>"
                +"<li style='width:288px; padding:15px 0 0 50px;'>"
                +"<input id='resetpwd' type='submit' value='Reset Password...' class='changepassword' style='display:none;' />"
                +"<input type='submit' value='Sign In' class='sub'/></li>"
                +"</ul>"
                +"</form>";
        }
        //var html="<div id='fBox' class='loginMask' style='display:none'>"
        var html = "<div id='identification_titles' class='titles'>"
                   + "<div><a href='#' id='identification_close_btn'>关闭</a></div>"
                   + "<div id='identification_handler' class='tl'>"+title+"</div></div>"
                   + "<div id='overFramel' class='overFramel'>"
                   + "<div class='overFramelogin'>"
                   + "<div class='login'>"
                   + desc
                   + form 
                   + "</div>"
                   + "</div>"
                   + "</div>"
                   + "<b class='rbottom'><b class='r3'></b><b class='r2'></b><b class='r1'></b></b>";
                   //+ "</div>";
        return html;
    };

    ns.bindDialogEvent = function(type) {
        if(type=="reg") {
            jQuery('#identity').blur(function() {
                jQuery(".notice").hide();
                var identityVal = jQuery('#identity').val();
                //added by handaoliang, check email address
                var mailReg = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                if(identityVal != "" && identityVal.match(mailReg)){
                    jQuery("#identity_verify_loading").show();
                    jQuery.ajax({
                        type: "GET",
                        url: site_url+"/s/IfIdentityExist?identity="+identityVal,
                        dataType:"json",
                        success: function(data){
                            if(data!=null) {
                                if(data.response.identity_exist=="false"){//identity
                                    jQuery('#hint').show();
                                    //jQuery('#retype').show();
                                    jQuery('#displayname').show();
                                    jQuery('#resetpwd').hide();
                                    jQuery('#logincheck').hide();
                                    odof.comm.func.showRePassword();
                                } else if(data.response.identity_exist=="true") {
                                    jQuery('#hint').hide();
                                    jQuery('#retype').hide();
                                    jQuery('#displayname').hide();
                                    jQuery('#resetpwd').show();
                                    jQuery('#logincheck').show();
                                }
                            }
                            jQuery("#identity_verify_loading").hide();
                        },
                        complete:function(){
                            jQuery("#identity_verify_loading").hide();
                        }
                    });
                }else{
                    jQuery('#hint').hide();
                    jQuery('#retype').hide();
                    jQuery('#displayname').hide();
                    jQuery('#resetpwd').hide();
                }
            });

            jQuery('#identificationform').submit(function() {
                    var params=ns.getUrlVars();
                    //ajax set password
                    //var token=params["token"];
                    var identity=jQuery('input[name=identity]').val();
                    var password=jQuery('input[name=password]').val();
                    var retypepassword=jQuery('input[name=retypepassword]').val();
                    var displayname=jQuery('input[name=displayname]').val();
                    var auto_signin=jQuery('input[name=auto_signin]').val();
           
                    if(jQuery('#retype').is(':visible')==true &&  password!=retypepassword && password!="" ) {
                        jQuery('#pwd_hint').html("<span>Check Password</span>");
                        jQuery('#pwd_hint').show();
                        return false;
                    }
                    if(jQuery('#displayname').is(':visible')==true) {
                        if(displayname==""){
                            jQuery('#pwd_hint').html("<span style='color:#CC3333'>set your display name</span>");
                            jQuery('#displayname_error').show();
                            jQuery('#pwd_hint').show();
                            return false;
                        }else if(!odof.comm.func.verifyDisplayName(displayname)){
                            jQuery('#pwd_hint').html("<span style='color:#CC3333'>Display name Error.</span>");
                            jQuery('#displayname_error').show();
                            jQuery('#pwd_hint').show();
                            return false;
                        }
                    }
                    if(password!=""&& identity!="" && jQuery('#displayname').is(':visible')==false) {
                        var poststr = "identity="+identity+"&password="
                                      + encodeURIComponent(password)+"&auto_signin="+auto_signin;
                        jQuery.ajax({
                            type: "POST",
                            data: poststr,
                            url: site_url+"/s/dialoglogin",
                            dataType:"json",
                            success: function(data){
                                if(data!=null) {
                                    if(data.response.success=="false") {
                                        jQuery('#login_hint').show();
                                    } else if(data.response.success=="true") {
                                        jQuery("#hostby").val(identity);
                                        jQuery("#hostby").attr("enter","true");
                                        odof.exlibs.ExDialog.hideDialog();
                                        odof.exlibs.ExDialog.destroyCover();

                                        //jQuery.modal.close();
                                    }
                                    //added by handaoliang
                                    //callback check UserLogin
                                    odof.user.status.checkUserLogin();
                                }
                            }
                        });
                    } else if(password!=""&& identity!="" && retypepassword==password &&  displayname!="") {
                        var poststr="identity="+identity+"&password="+encodeURIComponent(password)
                                    +"&repassword="+encodeURIComponent(retypepassword)
                                    +"&displayname="+encodeURIComponent(displayname);
                        jQuery.ajax({
                            type: "POST",
                            data: poststr,
                            url: site_url+"/s/dialogaddidentity",
                            dataType:"json",
                            success: function(data){
                                if(data!=null)
                                {
                                    if(data.response.success=="false")
                                    {
                                        jQuery('#login_hint').show();
                                    }
                                    else if(data.response.success=="true")
                                    {
                                        jQuery("#hostby").val(identity);
                                        jQuery("#hostby").attr("enter","true");
                                        odof.exlibs.ExDialog.hideDialog();
                                        odof.exlibs.ExDialog.destroyCover();
                                        //jQuery.modal.close();
                                        odof.user.status.checkUserLogin();
                                    }
                                }
                            }
                        });
                    } else { //reg
                        return false;
                    }

                    jQuery.ajax({
                        type: "GET",
                        url: site_url+"/identity/get?identity="+identity, 
                        dataType:"json",
                        success: function(data){
                        var exfee_pv="";
                        if(data.response.identity!=null)
                        {
                            var identity=data.response.identity.external_identity;
                            var id=data.response.identity.id;
                            var name=data.response.identity.name;
                            var avatar_file_name=data.response.identity.avatar_file_name;
                            if(jQuery('#exfee_'+id).attr("id")==null)
                            {
                                if(name=="")
                                    name=identity;
                                exfee_pv = exfee_pv+'<li id="exfee_'+id+'" class="addjn" onmousemove="javascript:hide_exfeedel(jQuery(this))" onmouseout="javascript:show_exfeedel(jQuery(this))"> <p class="pic20"><img src="/'+odof.comm.func.getHashFilePath("eimgs",avatar_file_name)+'/80_80_'+avatar_file_name+'" alt="" /></p> <p class="smcomment"><span class="exfee_exist" id="exfee_'+id+'" identityid="'+id+'"value="'+identity+'">'+name+'</span><input id="confirmed_exfee_'+ id +'" checked=true type="checkbox" /> <span class="lb">host</span></p> <button class="exfee_del" onclick="javascript:exfee_del(jQuery(\'#exfee_'+id+'\'))" type="button"></button> </li>';
                            }
                        }

                        jQuery("ul.samlcommentlist").append(exfee_pv);
                        }
                });
                return false;
            });
        }
    };
})(ns);

jQuery(document).ready(function(){

        jQuery('#loginform').submit(function() {
            var identity=jQuery('input[name=loginidentity]').val();
            var password=jQuery('input[name=password]').val();
            var auto_signin=jQuery('input[name=auto_signin]').val();
            var poststr="identity="+identity+"&password="+encodeURIComponent(password)+"&auto_signin="+auto_signin;
            jQuery('#login_hint').hide();
            jQuery.ajax({
                type: "POST",
                data: poststr,
                url: site_url+"/s/dialoglogin",
                dataType:"json",
                success: function(data){
                    if(data!=null)
                    {
                        if(data.response.success=="false")
                        {

                            //jQuery('#pwd_hint').html("<span>Error identity </span>");
                            jQuery('#login_hint').show();
                        }
                        else if(data.response.success=="true")
                        {
                        location.reload();
                        }
                    }
                }
            });
        return false;
        });

        jQuery('#identityform').submit(function() {
            var params=ns.getUrlVars();
            //ajax set password
            var token=params["token"];
            var password=jQuery('input[name=password]').val();
            var retypepassword=jQuery('input[name=retypepassword]').val();
            var displayname=jQuery('input[name=displayname]').val();


            if(password!=retypepassword && password!="" )
            {
                jQuery('#pwd_hint').html("<span>Check Password</span>");
                jQuery('#pwd_hint').show();
                return false;
            }
            if(displayname=="")
            {
                jQuery('#pwd_hint').html("<span>set your display name</span>");
                jQuery('#pwd_hint').show();
                return false;
            }
            if(token!=""&& cross_id>0)
            {
            var poststr="cross_id="+cross_id+"&password="+encodeURIComponent(password)+"&displayname="+displayname+"&token="+token;
            jQuery.ajax({
                type: "POST",
                data: poststr,
                url: site_url+"/s/setpwd",
                dataType:"json",
                success: function(data){
                    if(data!=null)
                    {
                        if(data.response.success=="false")
                        {
                        }
                        else if(data.response.success=="true")
                        {
                            location.reload();
                        }
                    }
                }
            });
            }
        return false;
    });
});
