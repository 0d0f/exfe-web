<?php include "share/header.php"; ?>
<link type="text/css" href="/static/css/ui-lightness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<link type="text/css" href="/static/css/simplemodal.css" rel="stylesheet" />
<script type="text/javascript" src="/static/js/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="/static/js/gather.js"></script>
<script type="text/javascript" src="/static/js/timepicker.js"></script>
<script type="text/javascript" src="/static/js/jquery.simplemodal.1.4.1.min.js"></script>
<script type="text/javascript" src="/static/js/activity-indicator.js"></script>
<script type="text/javascript" src="/static/js/dialog.js"></script>

<link type="text/css" href="/static/js/excal/skin/default/excal.css" rel="stylesheet" />
<script type="text/javascript" src="/static/js/excal/excal.js"></script>

<body>
<?php include "share/nav.php"; ?>
<?php
    $external_identity=$this->getVar("external_identity");
?>
<div class="centerbg">
  <div class="createset">
  <!--<h3>Gather for your <span>X</span></h3>-->

<ul>
<li id="calendar_map_container"></li>
<form action="" method="post" id="gatherxform">
<li><label class="title">Title:</label><input type="text"  name="title" id="g_title"  value="Edit title here"/></li>

<li><label class="description">Description:</label><textarea enter="0" name="description" id="g_description">Write some description for your exfe. (optional)</textarea>
</li>

<li><label class="date">Date &amp; Time</label>  <input type="text"  name="datetime" onclick="exfeCalendar(this, 'calendar_map_container');" />
<p class="redbtn">Incorrect format. e.g:6:30pm, 1/15/2011 </p>
</li>

<li><label class="location">Location:</label>  <textarea name="place" id="g_place" >Crab House
Pier 39, 203 C
San Francisco, CA
(555) 434-2722</textarea></li>
<li><label class="hostby">Host By</label>  <input type="text"  name="hostby" id="hostby" <?php if($external_identity!="") echo "enter='true' disabled='disabled' ";?> value="<?php if($external_identity!="") echo $external_identity; else echo "Enter your email";?>"/></li>

<li><label class="exfee">exfee:</label>
<p class="count"> <a id="confirmed_all" check=false href="javascript:void(1);"> Mark all as confirmed</a> count: <span id="exfee_count">1</span></p>
<span id="post_submit" title="Invite!"></span>
<textarea name="comment" id="exfee" >Enter attendees’ email or id</textarea><div id="identity_ajax"></div>
<div class="creattext">
  <div class="selecetafri">
    <div class="sover" id="exfee_pv">
      <ul class="samlcommentlist">
      <?php if($external_identity!="") { ?>
        <li class="addjn">
          <p class="pic20"><img src="/eimgs/80_80_<?php echo $global_avatar_file_name;?>" alt="" /></p>
          <p class="smcomment"><span class="exfee_exist" id="exfee_<?php echo $global_identity_id; ?>" identityid="<?php echo $global_identity_id; ?>" value="<?php echo $global_external_identity; ?>"><?php echo $global_name;?></span><input id='confirmed_exfee_<?php echo $global_identity_id;?>' checked=true type="checkbox" /> <span class="lb">host</span></p>
          <button type="button" class="exfee_del"></button>
        </li>
        <?php } ?>
      </ul>
    </div>
  </div>
</div>
</li>

 <li>
 <input type=hidden id="exfee_list" name="exfee_list"  value="" />
<label class="privacy">Privacy:</label><p class="privacy"><span>This is a private <strong>X</strong>.</span> <!--So only attendees could see details.--></p>
        <button type="button" id="gather_x" class="submit">Submit</button> <a href="/<?php echo $external_identity ? 's/profile' : ''; ?>" class="discard"> Discard </a> </li>
</form>
  </ul>


  </div>
<!--input id="user_submit" name="commit"  type="submit" value="Submit" /-->


<div class="albg" id="content_x">
<div class="step" id="index">
<h2 id="pv_title">Title</h2>
<div class="exfel">
<p class="text" id="pv_description">new cross for test</p><a href="">Expand</a>
<p class="Preview"></p>
<ul class="ynbtn">
<li><a class="yes" href="/3/rsvp/yes">Yes</a></li>
<li><a class="no" href="/3/rsvp/no">No</a></li>
<li><a class="maybe" href="/3/rsvp/maybe">Interested</a></li><li>
</li></ul>


</div><!--exfel-->


<div class="exfer">
<h3>3 months later</h3>
<p class="tm">
12:00 AM, Oct 20, 2011 </p>
<h3 id="pv_place_line1">huoju's home</h3>
<p class="tm" id="pv_place_line2">shanghai<br>pudong</p>

<div class="exfee">
<div class="feetop"><h3>exfee</h3>  <p class="of"><em class="bignb">2</em> <em class="malnb">3 of <br>confirmed</em></p></div>
<ul class="samlcommentlist">

<li>
<!-- p class="pic20"><img alt="" src="/eimgs/1.png"></p -->
<p class="smcomment"><span></span> <span class="lb">host</span>virushuo@gmail.com</p>
<p class="cs"><em class="c1"></em></p>
</li>
<li>
<p class="pic20"><img alt="" src="/eimgs/"></p>
<p class="smcomment"><span></span> gokeeper@gmail.com </p>
<p class="cs"><em class="c1"></em></p>
</li>

</ul>
</div><!--exfee-->
</div><!--exfer-->


</div><!--/#index-->
</div>


</body>
</html>
