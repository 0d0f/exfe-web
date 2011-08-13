<?php include "share/header.php"; ?>
<body>
<?php include "share/nav.php"; ?>
<?php 
$cross=$this->getVar("cross");
$user=$this->getVar("user");
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

<ul class="ynbtn">
<li><a href="/<?php echo $cross["id"];?>/rsvp/yes<?php if($token!="") echo "?token=".$token;?>" class="yes">Yes</a></li>
<li><a href="/<?php echo $cross["id"];?>/rsvp/no<?php if($token!="") echo "?token=".$token;?>" class="no">No</a></li>
<li><a href="/<?php echo $cross["id"];?>/rsvp/maybe<?php if($token!="") echo "?token=".$token;?>" class="maybe">Maybe</a><li>
</ul>

<div class="Conversation">
<h3>Conversation</h3>
<div class="commenttext">
<form action="/<?php echo $cross["id"];?>/conversation/add" method="post">
<img style="width:40px;height:40px" src="/eimgs/<?php echo $_SESSION["identity"]["avatar_file_name"];?>"><input type="submit" value="" title="Say!" name="commit" id="post_submit"><textarea tabindex="4" rows="10" class="ctext" name="comment"></textarea>
</form>
</div>

<ul class="commentlist">
<?php 
if($cross["conversation"])
{
    foreach($cross["conversation"] as $conversation)
    {
	$posttime=RelativeTime(strtotime($conversation["updated_at"]));
	$identity=$conversation["identity"];
	if($identity["name"]=="")
	    $identity["name"]=$user["name"];
	if($identity["avatar_file_name"]=="")
	    $identity["avatar_file_name"]=$user["avatar_file_name"];

	if($identity["name"]=="")
	    $identity["name"]=$identity["external_identity"];
?>
<li>
<p class="pic40"><img src="/eimgs/<?php echo $identity["avatar_file_name"];?>" alt=""></p> <p class="comment"><span><?php echo $identity["name"]; ?>:</span><?php echo $conversation["content"];?></p> <p class="times"><?php echo $posttime?></p>
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
<div class="feetop"><h3>exfee</h3>  <p class="of"><em class="bignb"><?php echo $confirmed; ?></em> <em class="malnb"><?php echo $allinvitation; ?> of <br />confirmed</em></p></div>
<ul class="samlcommentlist">

<?php 

foreach($host_exfee as $exfee)
{
?>
<li>
<p class="pic20"><img src="/eimgs/<?php echo $exfee["avatar_file_name"];?>" alt=""></p>
<p class="smcomment"><span><?php echo $exfee["name"];?></span> <span class="lb">host</span><?php echo $exfee["external_identity"];?></p>
<p class="cs"><em class="<?php if($exfee["state"]==INVITATION_YES) echo "c1"; else echo "c2";?>"></em></p>
</li>
<?php
}
?>
<?php
foreach($normal_exfee as $exfee)
{
?>
<li>
<p class="pic20"><img src="/eimgs/<?php echo $exfee["avatar_file_name"];?>" alt=""></p>
<p class="smcomment"><span><?php echo $exfee["name"];?></span> <?php echo $exfee["external_identity"];?> </p>
<p class="cs"><em class="<?php if($exfee["state"]==INVITATION_YES) echo "c1"; else echo "c2";?>"></em></p>
</li>
<?php
}
?>

</ul>
</div><!--exfee-->
</div><!--exfer-->


</div><!--/#index-->
</div><!--/#content-->
</div>

<div id="footerBao">

</div><!--/#footerBao-->
</body>
</html>


