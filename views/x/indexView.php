<?php include "share/header.php"; ?>
<body>
<?php include "share/nav.php"; ?>

<?php 
$cross=$this->getVar("cross");
print_r($cross);
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
?>
<div class="centerbg">
<div class="fsuo">
<p class="suobtn"></p>
<p class="xfc"><span>Private exfe,</span><br />
only attendees could see details.</p>
</div><!--锁-->
<div id="content" class="albg">
<div id="index" class="step">
<h2><?php echo $cross["title"]; ?></h2>
<div class="exfel">
<?php echo $description; ?>
<a href="">Expand</a>

<ul class="ynbtn">
<li><a href="" class="yes">Yes</a></li>
<li><a href="" class="no">No</a></li>
<li><a href="" class="maybe">Maybe</a><li>
</ul>

<div class="Conversation">
<h3>Conversation</h3>
<div class="commenttext">
<img src="images/img.jpg"><textarea tabindex="4" rows="10" class="ctext" name="comment"></textarea>
</div>

<ul class="commentlist">
<li>
<p class="pic40"><img src="images/img.jpg" alt=""></p>
<p class="comment"><span>Arthur369:</span>My only missing food in US, dudes! yummy Lorem ipsum dolorsit amet, ligula suspendisse nulla.</p>
<p class="times">1 min</p>
</li>
<li>
<p class="pic40"><img src="images/img.jpg" alt=""></p>
<p class="comment"><span>Arthur369:</span> Muhahaha,I’ll eat an big apple!</p>
<p class="times">3 hour</p>
</li>
<li>
<p class="pic40"><img src="images/img.jpg" alt=""></p>
<p class="comment"><span>Arthur369:</span>My only missing food in US, dudes! yummy Lorem ipsum dolorsit amet, ligula suspendisse nulla.</p>
<p class="times">06/03</p>
</li>
</ul>

</div>
</div><!--exfel-->


<div class="exfer">
<h3>Tomorrow</h3>
<p class="tm">
6:30pm, April 8
</p>
<h3><?php echo $place_line1; ?></h3>
<p class="tm"><?php echo $place_line2; ?></p>

<div class="exfee">
<div class="feetop"><h3>exfee</h3>  <p class="of"><em class="bignb">3</em> <em class="malnb">5 of <br />confirmed</em></p></div>
<ul class="samlcommentlist">

<?php 

foreach($host_exfee as $exfee)
{
?>
<li>
<p class="pic20"><img src="/eimgs/<?php echo $exfee["avatar_file_name"];?>" alt=""></p>
<p class="smcomment"><span><?php echo $exfee["name"];?></span> <span class="lb">host</span><?php echo $exfee["external_identity"];?></p>
<p class="cs"><em class="c1"></em></p>
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
<p class="cs"><em class="c2"></em></p>
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


