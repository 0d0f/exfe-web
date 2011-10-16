<?php include "share/header.php"; ?>
<link type="text/css" href="/static/css/ui-lightness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<link type="text/css" href="/static/css/simplemodal.css" rel="stylesheet" />
<script type="text/javascript" src="/static/js/libs/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="/static/js/libs/jquery.simplemodal.1.4.1.min.js"></script>
<script type="text/javascript" src="/static/js/libs/timepicker.js"></script>
<script type="text/javascript" src="/static/js/libs/activity-indicator.js"></script>
<script type="text/javascript" src="/static/js/apps/gather.js"></script>
<script type="text/javascript" src="/static/js/comm/dialog.js"></script>

<!-- Exfe Calendar -->
<link type="text/css" href="/static/js/excal/skin/default/excal.css" rel="stylesheet" />
<script type="text/javascript" src="/static/js/excal/excal.js"></script>

</head>
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
<li>
    <div class="gather_title_bg" id="gather_title_bg">
        <?php if($global_name != ""){ ?>Meet <?php echo $global_name; }else{ ?>Edit title here<?php } ?>
    </div>
    <label class="title">Title:</label>
    <input type="text"  name="title" id="gather_title_input" class="gather_title_input"  value="" />
    <input type="hidden"  name="title" id="g_title" value="" />
    <input type="hidden"  name="draft_id" id="draft_id" value="0" style="clear:both;" />
</li>

<li><label class="description">Description:</label><textarea enter="0" name="description" id="g_description">Write some description for your exfe. (optional)</textarea>
</li>

<input type="hidden" name="datetime" id="datetime" value="" />
<li><label class="date">Date &amp; Time</label>  <input type="text" name="datetime_original" id="datetime_original" onfocus="exCal.initCalendar(this, 'calendar_map_container', 'datetime');" />
<p class="redbtn">Incorrect format. e.g:6:30pm, 1/15/2011 </p>
</li>

<li><label class="location">Location:</label>  <textarea name="place" id="g_place" >Crab House
Pier 39, 203 C
San Francisco, CA
(555) 434-2722</textarea></li>
<li style="margin-top:15px;"><label class="hostby">Host By</label>  <input type="text"  name="hostby" id="hostby" <?php if($external_identity!="") echo "enter='true' disabled='disabled' ";?> value="<?php if($external_identity!="") echo $external_identity; else echo "Enter your email";?>"/></li>

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
          <p class="pic20">
              <img src="/eimgs/80_80_<?php echo $global_avatar_file_name;?>" alt="" />
          </p>
          <p class="smcomment">
              <span class="exfee_exist" id="exfee_<?php echo $global_identity_id; ?>" identityid="<?php echo $global_identity_id; ?>" value="<?php echo $global_external_identity; ?>"><?php echo $global_name;?></span>
              <input id="confirmed_exfee_<?php echo $global_identity_id;?>" class="confirmed_box" checked=true type="checkbox" />
              <span class="lb">host</span>
          </p>
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
<p class="Preview"></p>
<h2 id="pv_title">Title</h2>
<div class="exfel">
<p class="text" id="pv_description">new cross for test</p><a href="">Expand</a>
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
<div class="feetop"><h3>exfee</h3>  <p class="of"><em id="exfee_confirmed" class="bignb">0</em> of <span id="exfee_summary">0</span><br>confirmed</p></div>
<ul id="samlcommentlist" class="samlcommentlist"></ul>
</div><!--exfee-->
</div><!--exfer-->


</div><!--/#index-->
</div>


</body>
</html>
