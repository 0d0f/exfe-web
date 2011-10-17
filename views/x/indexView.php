<?php $page="cross";?>
<?php include "share/header.php"; ?>
<link type="text/css" href="/static/css/simplemodal.css" rel="stylesheet" />
<script type="text/javascript" src="/static/js/libs/jquery.simplemodal.1.4.1.min.js"></script>
<script type="text/javascript" src="/static/js/libs/activity-indicator.js"></script>
<script type="text/javascript" src="/static/js/libs/jquery.ba-outside-events.js"></script>
<script type="text/javascript" src="/static/js/apps/CrossEdit.js"></script>
<!-- Exfe Calendar -->
<link type="text/css" href="/static/js/excal/skin/default/excal.css" rel="stylesheet" />
<script type="text/javascript" src="/static/js/excal/excal.js"></script>
</head>

<body>
<?php include "share/nav.php"; ?>
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
<script type='text/javascript' src='/static/js/user/login.js'></script>
<script type="text/javascript" src="/static/js/comm/dialog.js"></script>
<script type="text/javascript" src="/static/js/apps/crossdialog.js"></script>
<?php
$original_desc_str = $cross["description"];
$desc_str_len = mb_strlen($original_desc_str);
$define_str_len = 300;

$description_lines=preg_split ("/\r\n|\r|\n/", $original_desc_str);

$description="";
foreach($description_lines as $line)
{
    $description.='<p class="text">'.$line.'</p>';
}
//=============================================================
$short_desc_str = mbString($original_desc_str, $define_str_len);
$temp_lines=preg_split ("/\r\n|\r|\n/", $short_desc_str);
$display_desc = "";
foreach($temp_lines as $s)
{
    $display_desc .= '<p class="text">'.$s.'</p>';
}
//=============================================================

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
<div class="cross_view_centerbg">
<div id="edit_cross_bar" style="display:none;">
    <p class="titles">Editing <span>X</span></p>
    <p id="error_msg" class="error_msg"></p>
    <p class="done_btn"><a href="javascript:void(0);" id="submit_data">Done</a></p>
    <p class="revert"><a id="revert_cross_btn" href="javascript:;">Revert</a></p>
</div>
<div id="content" class="cross_view_container">
    <div class="exfe_bubble" id="cross_time_bubble" style="display:none;">
        <div class="cross_dt_input"><input name="cross_datetime_original" id="cross_datetime_original" value="" /></div>
        <div class="cross_dt_msg"></div>
        <div id="cross_time_container"></div>
    </div>
    <div class="exfe_bubble" id="cross_place_bubble" style="display:none;">
        <div class="input_box">
            <textarea name="place_content" id="place_content"><?php echo $place_line1; ?></textarea>
            <span class="icon"></span>
        </div>
    </div>
    <div class="menu_bar">
        <p class="lock_icon" id="private_icon"></p>
        <p class="lock_icon_desc" id="private_hint" style="display:none" ><span>Private exfe,</span><br />only attendees could see details.</p>
        <p class="edit_icon" id="edit_icon"></p>
        <p class="edit_icon_desc" id="edit_icon_desc" style="display:none" >Edit this cross.</p>
    </div>
    <div id="index" class="step">
        <textarea id="cross_titles_textarea" class="cross_titles_textarea" style="display:none;"><?php echo $cross["title"] ?></textarea>
            <a href="<?php echo SITE_URL."/mute/x?id=".int_to_base62($cross["id"])?>">mute</a>
        <h2 id="cross_titles"><?php echo $cross["title"]; ?></h2>
        <div class="exfel">
        <textarea id="cross_desc_textarea" style="display:none;"><?php echo $cross["description"]; ?></textarea>
            <div id="cross_desc"<?php if($desc_str_len > $define_str_len){ ?> style="display:none"<?php } ?>>
            <?php echo $description; ?>
            </div>
            <div id="cross_desc_short"<?php if($desc_str_len <= $define_str_len){ ?> style="display:none"<?php } ?>>
            <?php echo $display_desc; ?>
            <a id="desc_expand_btn" href="javascript:void(0);">Expand</a>
            </div>
            <ul class="ynbtn" id="rsvp_options" <?php if($interested === 'yes') { echo 'style="display:none"'; } ?> >
                <li><a id='rsvp_yes' value="yes" href="javascript:void(0);" class="yes">Yes</a></li>
                <li><a id='rsvp_no' value="no" href="javascript:void(0);" class="no">No</a></li>
                <li><a id='rsvp_maybe' value="maybe" href="javascript:void(0);" class="maybe">interested</a> <div style="display:none" id="rsvp_loading" ></div> <li>
            </ul>
            <div id="rsvp_submitted" <?php if($interested!="yes") { echo 'style="display:none"'; } ?>><span>Your RSVP is "<span id="rsvp_status"></span>". </span> <a href="javascript:void(0);" id="changersvp">Change?</a></div>

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

                    //if($identity["name"]=="")
                    //    $identity["name"]=$identity["external_identity"];
                ?>
                <li>
                <p class="pic40"><img src="/eimgs/80_80_<?php echo $identity["avatar_file_name"];?>" alt=""></p> <p class="comment"><span><?php echo $identity["name"]; ?>:</span><?php echo $conversation["content"];?></p> <p class="times"><?php echo $posttime?></p>
                </li>
                <?php
                    }
                }
                ?>
                </ul>

            </div>
        </div><!--exfel-->
        <div id="cross_container" class="exfer">
            <input type="hidden" name="datetime" id="datetime" value="<?php echo $cross["begin_at"]; ?>" />
            <div id="cross_times_area">
                <h3><?php echo $begin_at_relativetime;?></h3>
                <p class="tm" id="cross_times"><?php echo $begin_at_humandatetime;?></p>
            </div>
            <h3 id="cross_place_area"><?php echo $place_line1; ?></h3>
            <p class="tm"><?php echo $place_line2; ?></p>

            <div id="exfee_area" class="exfee">
                <div class="feetop"><h3>exfee</h3> <p class="of"><em class="bignb"><?php echo $confirmed; ?></em> of <em class="malnb"><?php echo $allinvitation; ?></em><br />confirmed</p></div>
                <div id="exfee_edit_box">
                    <span id="exfee_submit" title="Invite!"></span>
                    <input type="text" id="exfee_input" value="Enter attendees' information"><div id="identity_ajax"></div>
                    <span id="exfee_edit_buttons">
                        <span id="exfee_revert">Revert</span>
                        <button id="exfee_done" type="button" class="edit_button">Done</button>
                    </span>
                </div>
                <ul class="samlcommentlist">

                <?php foreach($host_exfee as $exfee) {
                //        if ($exfee["avatar_file_name"] === '') {
                //            $exfee["avatar_file_name"] = 'default.png';
                //        }
                ?>
                <li id="exfee_<?php echo $exfee["identity_id"];?>" identity="<?php echo $exfee["external_identity"]; ?>" identityid="<?php echo $exfee["identity_id"]; ?>" class="exfee_exist exfee_item">
                    <p class="pic20"><img src="/eimgs/80_80_<?php echo $exfee["avatar_file_name"];?>" alt=""></p>
                    <div class="smcomment"><div><span class="ex_name"><?php echo $exfee["name"]; ?></span><span class="lb">host</span><span class="ex_identity"> <?php echo $exfee["external_identity"] === $exfee["name"] ? '' : $exfee["external_identity"]; ?></span></div></div>
                    <p class="cs"><em class="<?php echo $exfee["state"] === INVITATION_YES ? 'c1' : 'c2'; ?>"></em></p>
                </li>
                <?php } ?>
                <?php foreach($normal_exfee as $exfee) { ?>
                <li id="exfee_<?php echo $exfee["identity_id"];?>" identity="<?php echo $exfee["external_identity"]; ?>" identityid="<?php echo $exfee["identity_id"]; ?>" class="exfee_exist exfee_item">
                    <button type="button" class="exfee_del"></button>
                    <p class="pic20"><img src="/eimgs/80_80_<?php echo $exfee["avatar_file_name"];?>" alt=""></p>
                    <div class="smcomment"><div><span><?php echo $exfee["name"]; ?></span><span class="ex_identity"> <?php echo $exfee["external_identity"] === $exfee["name"] ? '' : $exfee["external_identity"]; ?></span></div></div>
                    <p class="cs"><em class="<?php echo $exfee["state"] === INVITATION_YES ? 'c1' : 'c2'; ?>"></em></p>
                </li>
                <?php } ?>
                </ul>
            <div>
                <button id="exfee_edit"   type="button" class="edit_button">Edit...</button>
                <button id="exfee_remove" type="button" class="edit_button">Remove...</button>
                <span id="check_all"><span>Check all</span><em class="c1"></em></span>
            </div>
        </div><!--exfee-->
    </div><!--exfer-->
<script type="text/javascript" src="/static/js/apps/cross.js"></script>

</div><!--/#index-->
</div><!--/#content-->
</div>

<div id="footerBao">
</div><!--/#footerBao-->
</body>
</html>
