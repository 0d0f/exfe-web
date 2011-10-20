<?php include "share/header.php"; ?>
<script type="text/javascript" src="/static/js/apps/gather.js"></script>

<!-- Exfe Calendar -->
<link type="text/css" href="/static/js/exlibs/excal/skin/default/excal.css" rel="stylesheet" />
<script type="text/javascript" src="/static/js/exlibs/excal/excal.js"></script>

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

    <li>
        <div id="gather_title_bg" class="gather_focus"><?php if($global_name != ""){ ?>Meet <?php echo $global_name; }else{ ?>Edit title here<?php } ?></div>
        <label class="title">Title</label>
        <input type="text" name="title" id="g_title" value="" />
    </li>

    <li id="gather_desc_blank">
        <div id="gather_desc_bg" class="gather_blur">Write some description for your exfe. (optional)</div>
        <label class="description">Description</label>
        <textarea name="description" id="g_description"></textarea>
    </li>

    <li>
        <div id="calendar_map_container"></div>
        <div id="gather_date_bg" class="gather_blur">Sometime</div>
        <label class="date">Date &amp; Time</label>
        <input type="text" name="datetime_original" id="datetime_original" />
        <input type="hidden" name="datetime" id="datetime" value="" />
        <p class="redbtn">Incorrect format. e.g:6:30pm, 1/15/2011</p>
    </li>

    <li id="gather_place_blank">
        <div id="gather_place_bg" class="gather_blur">
Crab House
Pier 39, 203 C
San Francisco, CA
(555) 434-2722
        </div>
        <label class="location">Location</label>
        <textarea name="place" id="g_place" ></textarea>
    </li>

    <li style="margin-top:15px;">
        <label class="hostby">Host By</label>
        <input type="text" name="hostby" id="hostby" <?php echo $external_identity ? 'enter="true" disabled="disabled" ' : ''; ?> value="<?php echo $external_identity ?: 'Your Identity'; ?>"/>
    </li>

    <li>
        <div id="gather_exfee_bg" class="gather_blur">Enter attendees’ email or id</div>
        <label class="exfee">Exfee</label>
        <p class="count"><a id="confirmed_all" check=false href="javascript:void(0);"> Mark all as confirmed</a> count: <span id="exfee_count">1</span></p>
        <span id="post_submit" title="Invite!"></span>
        <textarea name="comment" id="exfee" ></textarea>
        <div id="identity_ajax"></div>
        <div class="creattext">
            <div class="selecetafri">
                <div class="sover" id="exfee_pv">
                    <ul class="exfeelist">
                        <?php if ($external_identity!="") { ?>
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
        <label class="privacy">Privacy</label>
        <p class="privacy"><span>This is a private <strong>X</strong>.</span> <!--So only attendees could see details.--></p>
        <button type="button" id="gather_x" class="submit">Submit</button>
        <a href="/<?php echo $external_identity ? 's/profile' : ''; ?>" class="discard"> Discard </a>
    </li>

</ul>


  </div>
<!--input id="user_submit" name="commit"  type="submit" value="Submit" /-->


<div class="albg" id="content_x">
<div class="step" id="index">
<p class="Preview"></p>
<h2 id="pv_title" class="pv_title_normal"><?php if($global_name != ""){ ?>Meet <?php echo $global_name; }else{ ?>Edit title here<?php } ?></h2>
<div class="exfel">
<p class="text" id="pv_description">Write some description for your exfe. (optional)</p><!--a href="">Expand</a-->
<ul class="ynbtn">
<li><a class="yes_readonly" disabled="disabled">Yes</a></li>
<li><a class="no_readonly" disabled="disabled">No</a></li>
<li><a class="maybe_readonly" disabled="disabled">interested</a></li><li>
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
