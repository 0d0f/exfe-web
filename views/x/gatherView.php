<?php include "share/header.php"; ?>
<script type="text/javascript" src="/static/js/libs/jquery.ba-outside-events.js"></script>
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
        <label class="title">Title</label>
        <input type="text" name="title" id="g_title" class="gather_blur" value="<?php if($global_name != ""){ ?>Meet <?php echo $global_name; }else{ ?>Edit title here<?php } ?>" />
    </li>

    <li id="gather_desc_blank">
        <div id="gather_desc_bg" class="gather_blur">Write some words about this X.</div>
        <label class="description">Description</label>
        <textarea name="description" id="g_description"></textarea>
    </li>

    <li>
        <div id="calendar_map_container"></div>
        <div id="gather_date_bg" class="gather_blur">Sometime</div>
        <label class="date">Date &amp; Time</label>
        <input type="text" name="datetime_original" id="datetime_original" />
        <input type="hidden" name="datetime" id="datetime" value="" />
        <!-- @todo== p class="redbtn">Incorrect format. e.g:6:30pm, 1/15/2011</p -->
    </li>

    <li id="gather_place_blank">
        <div id="gather_place_bg" class="gather_blur">Somewhere</div>
        <label class="location">Location</label>
        <textarea name="place" id="g_place" ></textarea>
    </li>

    <li style="margin-top:15px;">
        <label class="hostby">Host By</label>
        <input type="text" name="hostby" id="hostby" class="gather_blur" <?php echo $external_identity ? 'enter="true" disabled="disabled" ' : ''; ?> value="<?php echo $external_identity ?: 'Your Identity'; ?>"/>
    </li>

    <li>
        <div id="post_submit" title="Invite!"></div>
        <div id="gather_exfee_bg" class="gather_blur">Enter attendees’ email or id</div>
        <select id="exfee_complete" size="5"></select>
        <label class="exfee">Exfee</label>
        <p class="count"><a id="confirmed_all" check=false href="javascript:void(0);"> Mark all as confirmed</a> count: <span id="exfee_count">1</span></p>
        <textarea id="exfee"></textarea>
        <div id="identity_ajax"></div>
        <div class="creattext">
            <div class="selecetafri">
                <div class="sover" id="exfee_pv">
                    <ul class="exfeelist">
                        <?php if ($external_identity != '') { ?>
                        <li class="addjn">
                            <p class="pic20">
                                <img src="<?php echo IMG_URL."/".getHashFilePath("", $global_avatar_file_name); ?>/80_80_<?php echo $global_avatar_file_name;?>" alt="" />
                            </p>
                            <p class="smcomment">
                                <span id="exfee_<?php echo $global_identity_id; ?>"
                                      class="exfee_exist"
                                      identityid="<?php echo $global_identity_id; ?>"
                                      value="<?php echo $global_external_identity; ?>"
                                      avatar="<?php echo $global_avatar_file_name; ?>">
                                      <?php echo $global_name;?>
                                </span>
                                <input id="confirmed_exfee_<?php echo $global_identity_id;?>"
                                       class="confirmed_box" checked=true type="checkbox" />
                                <span class="lb">host</span>
                            </p>
                            <button type="button" class="exfee_del"></button>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
        <div id="exfee_warning">
            No more than 12 attendees. Sorry we're still working on it.
        </div>
    </li>

    <li>
        <label class="privacy">Privacy</label>
        <p class="privacy">
            <span class="inform">This is a private <span class="x">X</span>.</span>
            <br>
            <span class="subinform">Only attendees can access, and change other's status.</span>
        </p>
    </li>

    <li id="gather_submit_blank">
        <a href="/<?php echo $external_identity ? 's/profile' : ''; ?>" id="discard">Discard</a>
        <button type="button" id="gather_x" class="submit">Submit</button>
        <div id="gather_submit_ajax"></div>
        <div id="gather_failed_hint">Submission failed.</div>
    </li>

</ul>


  </div>


<div class="albg" id="content_x">
<div class="step" id="index">
<p class="Preview"></p>
<h2 id="pv_title" class="pv_title_normal"><?php if($global_name != ""){ ?>Meet <?php echo $global_name; }else{ ?>Edit title here<?php } ?></h2>
<div class="exfel">
<p class="text" id="pv_description">Write some words about this X.</p><!--a href="">Expand</a-->
<ul class="ynbtn">
<li><a class="yes_readonly" disabled="disabled">Yes</a></li>
<li><a class="no_readonly" disabled="disabled">No</a></li>
<li><a class="maybe_readonly" disabled="disabled">interested</a></li><li>
</li></ul>


</div><!--exfel-->

<div class="exfer">
<h3 id="pv_relativetime">Sometime</h3>
<p id="pv_origintime"class="tm"></p>
<h3 id="pv_place_line1" class="pv_place_line1_normal">Somewhere</h3>
<p class="tm" id="pv_place_line2"></p>

<div class="exfee">
<div class="feetop"><h3>Exfee</h3>  <p class="of"><em id="exfee_confirmed" class="bignb">0</em> of <span id="exfee_summary">0</span><br>confirmed</p></div>
<ul id="exfeelist" class="samlcommentlist"></ul>
</div><!--exfee-->
</div><!--exfer-->


</div><!--/#index-->
</div>

</body>
</html>
