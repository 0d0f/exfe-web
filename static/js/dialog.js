function showdialog(type)
{
    var title="";
    var desc="";
    if(type=="setpassword")
    {
	title="Set Password";
	desc="<div class='setpassword'>Please set password to keep track of <br/> RSVP status and engage in.</div>";
    }
    else if(type=="login")
    {
	title="Sign In";
    	desc="<div class='account'><p>Authorize with your <br/> existing accounts </p><span><img src='/static/images/facebook.png' alt='' width='32' height='32' />"
	    +"<img src='/static/images/twitter.png' alt='' width='32' height='32' /> "
	    +"<img src='/static/images/google.png' alt='' width='32' height='32' /> "
	    +"</span> <h4>Enter your identity information</h4></div>";
    }
    var html="<div id='fBox' class='loginMask' style='display:none'>"
    +"<h5><a href='/' onclick='cancel()'>关闭</a><em class='tl'>"+title+"</em></h5>"
    +"<div class='overFramel'>"
    +"<div class='overFramelogin'>"
    +"<div class='login'>"
    + desc
    +"<form accept-charset='UTF-8' action='' method='post'>"
    +"<ul>"
    +"<li><label>Identity:</label><input id='identity' name='identity' type='text' class='inputText' ><em class='ic1'></em></li>"
    +"<li id='hint' style='display:none' class='notice'><span>You're creating a new identity!</span></li>"
    +"<li><label>Password:</label><input type='password'  name='password' class='inputText'/><em class='ic2'></em></li>"
    +"<li id='retype' style='display:none'><label>Re-type:</label><input type='text'  name='retypepassword'class='inputText'/><em class='ic3'></em></li>"
    +"<li id='displayname' style='display:none'><label>Names:</label><input type='text'  name='displayname'class='inputText'/><em class='warning'></em></li>"
    +"<li class='logincheck'><input type='hidden' value='0' name='auto_signin'><input type='checkbox' value='1' name='auto_signin' id='auto_signin'><span>Sign in automatically</span></li>"
    +"<li><input id='resetpwd' type='submit' value='Reset Password...' class='changepassword'/><input type='submit' value='Sign In' class='sub'/></li>"
    +"</ul>"
    +"</form>"
    +"</div>"
    +"</div>"
    +"</div>"
    +"<b class='rbottom'><b class='r3'></b><b class='r2'></b><b class='r1'></b></b>"
    +"</div>";
    return html;
}
