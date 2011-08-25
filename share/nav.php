<div id="headerBao">
<div id="header">
<div class="hl"></div>
<div class="hc"><a href="/"  class="logo" >LOGO</a></div>
<?php

    if($_SESSION["tokenIdentity"]!="")
    {
	 $global_name=$_SESSION["tokenIdentity"]["identity"]["name"];	
	 $global_avatar_file_name=$_SESSION["tokenIdentity"]["identity"]["avatar_file_name"];	
	 $global_external_identity=$_SESSION["tokenIdentity"]["identity"]["external_identity"];	
	 $global_identity_id=$_SESSION["tokenIdentity"]["identity_id"];	

    }
    else if($_SESSION["identity"]!="")
    {
	 $global_name=$_SESSION["identity"]["name"];	
	 $global_avatar_file_name=$_SESSION["identity"]["avatar_file_name"];	
	 $global_external_identity=$_SESSION["identity"]["external_identity"];	
	 $global_identity_id=$_SESSION["identity_id"];	
    }

    
    if(intval($_SESSION["userid"])>0)
    {
	$userData = $this->getModelByName("user");
    	$user=$userData->getUser($_SESSION["userid"]);
	if($global_name=="")
	   $global_name=$user["name"];
	if($global_avatar_file_name=="")
	   $global_avatar_file_name=$user["avatar_file_name"];
?>
<div class="hr"><div class="name" ><div id="goldLink"><a href="#" ><?php echo $global_name; ?></a></div>
<div class="myexfe" id="myexfe" >
<div class="message">
<div class="na">
<p class="h">
<span>271</span>
exfes attended
</p>
<img src="/eimgs/64_64_<?php echo $avatar_file_name;?>">
</div>
<p class="info">
<span>Upcoming:</span><br />
 <em>Now</em>  Dinner in SF<br/>
 <em>24hr</em>  Bay Area VC TALK<br/>
Mary and Virushuo’s Birthday Party
</p>
<p class="creatbtn"><a href="/x/gather">Gather X</a></p>
</div>
<div class="myexfefoot"><a href="/s/profile" class="l">Setting</a><a href="" class="r">Sign out</a></div>
<p class="fjiao"></p>
</div><!--#myexfe-->
<iframe class="menu_iframe"></iframe>
</div>
<em class="light"></em>
</div><!--.hr-->
<?php
    }
?>

</div><!--/#header-->
</div><!--/#headerBao-->
