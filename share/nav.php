<div id="headerBao">
<div id="header">
<div class="hl"></div>
<div class="hc"><a href="/"  class="logo" >LOGO</a></div>
<?php
    if(intval($_SESSION["userid"])>0)
    {
    $userData = $this->getModelByName("user");
    $user=$userData->getUser($_SESSION["userid"]);
    $name=$user["name"];
    $avatar_file_name=$user["avatar_file_name"];
?>
<div class="hr"><div class="name" ><div id="goldLink"><a href="#" ><?php echo $name?></a></div>
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
