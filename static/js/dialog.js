function showdialog(type)
{
    var title="";
    var desc="";
    var fields="";
    if(type=="setpassword")
    {
	title="Set Password";
	desc="<div class='setpassword'>Please set password to keep track of <br/> RSVP status and engage in.</div>";

	fields="<li><label>Identity:</label><input id='identity' name='identity' type='text' class='inputText' disabled='disabled' value='"+external_identity+"'><em class='ic1'></em></li>"
    +"<li id='hint' style='display:none' class='notice'><span>You're creating a new identity!</span></li>"
    +"<li><label>Password:</label><input type='password'  name='password' class='inputText'/><em class='ic2'></em></li>"
    +"<li id='retype' ><label>Re-type:</label><input type='text'  name='retypepassword' class='inputText'/><em class='ic3'></em></li>"
    +"<li id='displayname'><label>Names:</label><input type='text'  name='displayname' class='inputText'/><em class='warning'></em></li>"
    +"<li><a href='#'>Cancel</a><input type='submit' name='setpwddone' value='Done' class='sub'/></li>"
    +"<li id='pwd_hint' style='display:none' class='notice'><span>check password</span></li>"
    }
    else if(type=="login")
    {
	title="Sign In";
    	desc="<div class='account'><p>Authorize with your <br/> existing accounts </p><span><img src='/static/images/facebook.png' alt='' width='32' height='32' />"
	    +"<img src='/static/images/twitter.png' alt='' width='32' height='32' /> "
	    +"<img src='/static/images/google.png' alt='' width='32' height='32' /> "
	    +"</span> <h4>Enter your identity information</h4></div>";
	fields="<li><label>Identity:</label><input id='identity' name='identity' type='text' class='inputText' ><em class='ic1'></em></li>"
    +"<li id='hint' style='display:none' class='notice'><span>You're creating a new identity!</span></li>"
    +"<li><label>Password:</label><input type='password'  name='password' class='inputText'/><em class='ic2'></em></li>"
    +"<li id='retype' style='display:none'><label>Re-type:</label><input type='text'  name='retypepassword'class='inputText'/><em class='ic3'></em></li>"
    +"<li id='displayname' style='display:none'><label>Names:</label><input type='text'  name='displayname'class='inputText'/><em class='warning'></em></li>"
    +"<li class='logincheck'><input type='hidden' value='0' name='auto_signin'><input type='checkbox' value='1' name='auto_signin' id='auto_signin'><span>Sign in automatically</span></li>"
    +"<li><input id='resetpwd' type='submit' value='Reset Password...' class='changepassword'/><input type='submit' value='Sign In' class='sub'/></li>"

    }
    var html="<div id='fBox' class='loginMask' style='display:none'>"
    +"<h5><a href='/' onclick='cancel()'>关闭</a><em class='tl'>"+title+"</em></h5>"
    +"<div class='overFramel'>"
    +"<div class='overFramelogin'>"
    +"<div class='login'>"
    + desc
    +"<form id='identityform' accept-charset='UTF-8' action='' method='post'>"
    +"<ul>"
    + fields
    +"</ul>"
    +"</form>"
    +"</div>"
    +"</div>"
    +"</div>"
    +"<b class='rbottom'><b class='r3'></b><b class='r2'></b><b class='r1'></b></b>"
    +"</div>";
    return html;
}



$(document).ready(function(){

    //$('input[name=setpwddone]').click(function(e){
    $('#identityform').submit(function() {
	var params=getUrlVars();
	//ajax set password
	var token=params["token"];
	var password=$('input[name=password]').val();
	var retypepassword=$('input[name=retypepassword]').val();
	var displayname=$('input[name=displayname]').val();

	if(password!=retypepassword)
	{
	    $('#pwd_hint').html("<span>Check Password</span>");
	    $('#pwd_hint').show();
	    return false;
	}
	if(token!=""&& cross_id>0)
	{
	    var poststr="cross_id="+cross_id+"&password="+encodeURIComponent(password)+"&displayname="+displayname+"&token="+token;
	    $.ajax({
	      type: "POST",
	      data: poststr,
	      url: site_url+"/s/setpwd",
	      dataType:"json",
	      success: function(data){
	    	if(data!=null)
	    	{
	    	    if(data.response.success=="false")
	    	    {
	    	    ////identity
	    	    //    $('#hint').show();
	    	    //    $('#retype').show();
	    	    //    $('#displayname').show();
	    	    //    $('#resetpwd').hide();
	    	    }
	    	    else if(data.response.success=="true")
	    	    {
			window.location=window.location.href;
	    	    }
	    	}
	    }
	    });
	}
        return false;
	//e.preventDefault();
	//return false;
	
    });

});
