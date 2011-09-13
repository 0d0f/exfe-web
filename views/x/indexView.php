<?php $page="cross";?>
<?php include "share/header.php"; ?>
<link type="text/css" href="/static/css/simplemodal.css" rel="stylesheet" />
<script type="text/javascript" src="/static/js/jquery.simplemodal.1.4.1.min.js"></script>
<script type="text/javascript" src="/static/js/activity-indicator.js"></script>
<body>
<?php include "share/nav.php"; ?>
<?php
?>
<?php
$cross=$this->getVar("cross");
$user=$this->getVar("user");
$myidentity=$this->getVar("myidentity");
$myrsvp=$this->getVar("myrsvp");
$interested=$this->getVar("interested");

$token_expired=$this->getVar("token_expired");

echo "<script type='text/javascript'>\r\n";
echo "var external_identity='".$myidentity["external_identity"]."';\r\n";
echo "var cross_id=".$cross["id"].";\r\n";
echo "var show_idbox='".$this->getVar("showlogin")."'; \r\n";
echo "var token_expired='".$token_expired."'; \r\n";
echo "var myrsvp=".intval($myrsvp)."; \r\n";
echo "</script>\r\n";
?>
<script type='text/javascript' src='/static/js/login.js'></script>
<script type="text/javascript" src="/static/js/dialog.js"></script>
<script type="text/javascript" src="/static/js/crossdialog.js"></script>
<?php
$description_lines=preg_split ("/\r\n|\r|\n/", $cross["description"]);
$description="";
foreach($description_lines as $line)
{
    $description.='<p class="text">'.$line.'</p>';
}
$place_line1=$cross["place"]["line1"];
$place_line2= str_replace('\r',"<br/>",$cross["place"]["line2"]);
$host_exfee=$cross["host_exfee"];
$normal_exfee=$cross["normal_exfee"];
$confirmed=0;
$allinvitation=count($host_exfee)+count($normal_exfee);
foreach($host_exfee as $exfee)
{
    if($exfee["state"]==INVITATION_YES)
	$confirmed=$confirmed+1;
}
foreach($normal_exfee as $exfee)
{
    if($exfee["state"]==INVITATION_YES)
	$confirmed=$confirmed+1;
}

$begin_at_relativetime=RelativeTime(strtotime($cross["begin_at"]));
$begin_at_humandatetime=humanDateTime(strtotime($cross["begin_at"]));
$token=$_GET["token"];
?>
<div class="centerbg">
<div class="fsuo">
<p class="suobtn" id="private_icon"></p>
<p class="xfc" id="private_hint" style="display:none" ><span>Private exfe,</span><br />
only attendees could see details.</p>
</div><!--锁-->
<div id="content" class="albg">
<div id="index" class="step">
<h2><?php echo $cross["title"]; ?></h2>
<div class="exfel">
<?php echo $description; ?>
<a href="">Expand</a>

<ul class="ynbtn" id="rsvp_options" <?php if($interested=="yes") { echo 'style="display:none"'; } ?> >
<li><a id='rsvp_yes' value="yes" href="#" "/<?php echo $cross["id"];?>/rsvp/yes<?php if($token!="") echo "?token=".$token;?>" class="yes">Yes</a></li>
<li><a id='rsvp_no' value="no" href="#" "/<?php echo $cross["id"];?>/rsvp/no<?php if($token!="") echo "?token=".$token;?>" class="no">No</a></li>
<li><a id='rsvp_maybe' value="maybe" href="#" "/<?php echo $cross["id"];?>/rsvp/maybe<?php if($token!="") echo "?token=".$token;?>" class="maybe">interested</a> <div style="display:none" id="rsvp_loading" ></div> <li>
</ul>
<div id="rsvp_submitted" <?php if($interested!="yes") { echo 'style="display:none"'; } ?>><span>Your RSVP is "<span id="rsvp_status"></span>". </span> <a href="#" id="changersvp">Change?</a></div>

<div class="Conversation">
<h3>Conversation</h3>
<div class="commenttext">
<form id="formconversation" action="/<?php echo $cross["id"];?>/conversation/add" method="post">
<img style="width:40px;height:40px" src="/eimgs/80_80_<?php echo $global_avatar_file_name;?>"><input type="submit" value="" title="Say!" name="post_commit" id="post_submit"><textarea tabindex="4" rows="10" class="ctext" name="comment"></textarea>
</form>
</div>

<ul id="commentlist" class="commentlist">
<?php
if($cross["conversation"])
{
    foreach($cross["conversation"] as $conversation)
    {
	$posttime=RelativeTime(strtotime($conversation["updated_at"]));
	$identity=$conversation["identity"];
	//if($identity["name"]=="")
	//    $identity["name"]=$user["name"];
	//if($identity["avatar_file_name"]=="")
	//    $identity["avatar_file_name"]=$user["avatar_file_name"];

	if($identity["name"]=="")
	    $identity["name"]=$identity["external_identity"];
?>
<li>
<p class="pic40"><img src="/eimgs/80_80_<?php if($identity["avatar_file_name"]=="") echo "default.png"; else echo $identity["avatar_file_name"];?>" alt=""></p> <p class="comment"><span><?php echo $identity["name"]; ?>:</span><?php echo $conversation["content"];?></p> <p class="times"><?php echo $posttime?></p>
</li>
<?php
    }
}
?>
</ul>

</div>
</div><!--exfel-->


<div class="exfer">
<h3><?php echo $begin_at_relativetime;?></h3>
<p class="tm">
<?php echo $begin_at_humandatetime;?>
</p>
<h3><?php echo $place_line1; ?></h3>
<p class="tm"><?php echo $place_line2; ?></p>

<div class="exfee">
<div class="feetop"><h3>exfee</h3>  <p class="of"><em class="bignb"><?php echo $confirmed; ?></em> <em class="malnb"> of <?php echo $allinvitation; ?><br />confirmed</em></p></div>
<ul class="samlcommentlist">

<?php

foreach($host_exfee as $exfee)
{
    if($exfee["avatar_file_name"]=="")
        $exfee["avatar_file_name"]="default.png"
?>
<li id="exfee_<?php echo $exfee["identity_id"];?>">
<p class="pic20"><img src="/eimgs/80_80_<?php echo $exfee["avatar_file_name"];?>" alt=""></p>
<p class="smcomment"><span><?php echo $exfee["name"];?></span> <span class="lb">host</span><?php echo $exfee["external_identity"];?></p>
<p class="cs"><em class="<?php if($exfee["state"]==INVITATION_YES) echo "c1"; else echo "c2";?>"></em></p>
</li>
<?php
}
?>
<?php
foreach($normal_exfee as $exfee)
{
    if($exfee["avatar_file_name"]=="")
        $exfee["avatar_file_name"]="default.png"
?>
<li id="exfee_<?php echo $exfee["identity_id"];?>">
<p class="pic20"><img src="/eimgs/80_80_<?php echo $exfee["avatar_file_name"];?>" alt=""></p>
<p class="smcomment"><span><?php echo $exfee["name"];?></span> <?php echo $exfee["external_identity"];?> </p>
<p class="cs"><em class="<?php if($exfee["state"]==INVITATION_YES) echo "c1"; else echo "c2";?>"></em></p>
</li>
<?php
}
?>

</ul>
</div><!--exfee-->
</div><!--exfer-->
<script type="text/javascript" src="/static/js/cross.js"></script>


</div><!--/#index-->
</div><!--/#content-->
</div>

<div id="footerBao">
</div><!--/#footerBao-->
</body>
</html>
