<?php
    $page = 'cross';
    include 'share/header.php';
?>
<script src="/static/js/libs/showdown.js"></script>
<script src="/static/js/libs/jquery.ba-outside-events.js"></script>
<!-- X Render -->
<link type="text/css" href="/static/css/x.css" rel="stylesheet">
<script src="/static/js/apps/x.js"></script>
<!-- X Exit -->
<link type="text/css" href="/static/css/xedit.css" rel="stylesheet">
<script src="/static/js/apps/xedit.js"></script>
<!-- Exfe Calendar -->
<link type="text/css" href="/static/js/exlibs/excal/skin/default/excal.css" rel="stylesheet">
<script src="/static/js/exlibs/excal/excal.js"></script>
</head>

<body>
<?php
    include 'share/nav.php';


    $cross  = $this->getVar('cross');
 // $user   = $this->getVar('user');
    $myrsvp = intval($this->getVar('myrsvp'));


    // handle login box
    $myidentity    = $this->getVar('myidentity');
    $token_expired = $this->getVar('token_expired');
    if ($token_expired === '') {
        $token_expired = 'false';
    }
    $login_type = $this->getVar('login_type');
    echo "<script>\r\n"
       . "var external_identity='".$myidentity["external_identity"]."';\r\n"
       . "var cross_id=".$cross["id"].";\r\n"
       . "var show_idbox='".$this->getVar("showlogin")."'; \r\n"
    // . "var show_idbox='login'; \r\n"
       . "var login_type='".$login_type."'; \r\n"
       . "var token_expired='".$token_expired."'; \r\n"
    // . "var token_expired='true'; \r\n"
       . "var myrsvp=".$myrsvp."; \r\n"
       . "var token='".$_GET["token"]."'; \r\n"
       . "var id_name='".$global_name."'; \r\n"
       . "var location_uri='".SITE_URL."/!".int_to_base62($cross["id"])."';\r\n"
       . "</script>\r\n";


    // ready cross data
    echo '<script>'
       . 'var myIdentity = ' . json_encode($myidentity) . ','
       .     'crossData  = ' . json_encode($cross) . ','
       .     "myrsvp     = {$myrsvp};"
       . '</script>';



if(0) {
    // format
    include_once "lib/markdown.php";
    $original_desc_str = $cross["description"];

    $parser = new Markdown_Parser;
    $parser->no_markup = true;
    $description= $parser->transform($original_desc_str);

    $text_desc_str=strip_tags($description);
    $desc_str_len = mb_strlen($text_desc_str);

    $define_str_len = 300;

    $desc_len=0;
    $display_desc="";
    if($desc_str_len>$define_str_len )
    {
        $description_lines=preg_split ("/\r\n|\r|\n/", $description);
        foreach($description_lines as $line)
        {
            $line_len=mb_strlen(strip_tags($line));
            $desc_len=$desc_len+$line_len;
            $display_desc.=$line;
            if($desc_len>$define_str_len)
                break;
        }
    }
    #//=============================================================
    #$short_desc_str = mbString($original_desc_str, $define_str_len);
    #$temp_lines=preg_split ("/\r\n|\r|\n/", $short_desc_str);
    #$display_desc = "";
    #foreach($temp_lines as $s)
    #{
    #    $display_desc .= '<p class="text">'.ParseURL($s).'</p>';
    #}
    #//=============================================================

    $place_line1   = $cross['place']['line1'];
    $place_line2   = str_replace('\r', "\n", $cross['place']['line2']);
    $host_exfee    = $cross['host_exfee'];
    $normal_exfee  = $cross['normal_exfee'];
    $confirmed     = 0;
    $allinvitation = count($host_exfee) + count($normal_exfee);
    foreach ($host_exfee as $exfee) {
        if ($exfee['state'] == INVITATION_YES) {
            $confirmed = $confirmed + 1;
        }
    }
    foreach($normal_exfee as $exfee) {
        if ($exfee["state"] == INVITATION_YES) {
            $confirmed = $confirmed + 1;
        }
    }

    $begin_at_relativetime=RelativeTime(strtotime($cross["begin_at"]));
    $begin_at_humandatetime=humanDateTime(strtotime($cross["begin_at"]),intval($cross["time_type"]));
    $token=$_GET["token"];
}
?>
<div class="content">
    <div id="x_view"></div>
</div>





<div class="cross_view_centerbg">
    <div id="edit_cross_bar" style="display:none;">
        <div id='edit_cross_submit_loading' style="display:none;"></div>
        <p class="titles">Editing <span>X</span></p>
        <p id="error_msg" class="error_msg"></p>
        <p class="done_btn">
            <a href="javascript:void(0);" id="submit_data">Done</a>
        </p>
        <p class="revert">
            <a id="revert_cross_btn" href="javascript:;">Revert</a>
        </p>
    </div>

    <div id="content" class="cross_view_container">
        <div class="exfe_bubble" id="cross_time_bubble" style="display:none;">
            <div class="cross_dt_input">
                <input name="cross_datetime_original" id="cross_datetime_original" value="" />
            </div>
            <div class="cross_dt_msg"></div>
            <div id="cross_time_container"></div>
        </div>
        <div class="exfe_bubble" id="cross_place_bubble" style="display:none;">
            <div class="input_box">
                <textarea name="place_content" id="place_content"><?php echo "{$place_line1}\n{$place_line2}"; ?></textarea>
                <span class="icon"></span>
            </div>
        </div>
        <div class="menu_bar">
            <p class="lock_icon" id="private_icon"></p>
            <p class="lock_icon_desc" id="private_hint" style="display:none" >
                <span>Private X:</span>
                <br>
                Only attendees can access, and change other’s status.
            </p>
            <p class="edit_icon" id="edit_icon"></p>
            <p class="edit_icon_desc" id="edit_icon_desc" style="display:none" >
                Edit this cross.
            </p>
        </div>
        <div id="index" class="step">
            <input id="cross_titles_textarea" class="cross_titles_textarea" style="display:none;" value="<?php echo $cross["title"] ?>">
            <h2 id="cross_titles" class="pv_title_normal">
                <?php echo $cross["title"]; ?>
            </h2>
            <div class="exfel">
                <textarea id="cross_desc_textarea" style="display:none;"><?php echo $cross["description"]; ?></textarea>
                <div id="cross_desc"<?php if($desc_str_len > $define_str_len){ ?> style="display:none"<?php } ?>>
                    <?php echo $description; ?>
                </div>
                <div id="cross_desc_short"<?php if($desc_str_len <= $define_str_len){ ?> style="display:none"<?php } ?>>
                    <?php echo $display_desc; ?>
                    <a id="desc_expand_btn" href="javascript:void(0);">Expand</a>
                </div>
                <ul class="ynbtn" id="rsvp_options" <?php echo $myrsvp ? 'style="display:none"' : ''; ?> >
                    <li>
                        <a id='rsvp_yes' value="yes" href="javascript:void(0);" class="yes">Accept</a>
                    </li>
                    <li>
                        <a id='rsvp_no' value="no" href="javascript:void(0);" class="no">Decline</a>
                    </li>
                    <li>
                        <a id='rsvp_maybe' value="maybe" href="javascript:void(0);" class="maybe">interested</a>
                        <div style="display:none" id="rsvp_loading" ></div>
                    <li>
                </ul>
                <div id="rsvp_submitted" <?php echo $myrsvp ? '' : 'style="display:none"'; ?>>
                    <span>Your RSVP is "<span id="rsvp_status"></span>". </span>
                    <a href="javascript:void(0);" id="changersvp">Change?</a>
                </div>
                <div class="Conversation">
                    <h3>Conversation</h3>
                    <div class="commenttext">
                        <img style="width:40px;height:40px" src="<?php echo getUserAvatar($global_avatar_file_name, 80); ?>">
                        <input type="submit" value="" title="Say!" name="post_commit" id="post_submit">
                        <textarea tabindex="4" rows="10" class="ctext" name="comment"></textarea>
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
                            <p class="pic40">
                                <img src="<?php echo getUserAvatar($identity["avatar_file_name"], 80); ?>" alt="">
                            </p>
                            <p class="comment">
                                <span><?php echo $identity["name"]; ?>:</span>
                                <?php echo $conversation["content"];?>
                            </p>
                            <p class="times">
                                <?php echo $posttime?>
                            </p>
                        </li>
                        <?php
                            }
                        }
                        ?>
                    </ul>

                </div>
            </div>
            <div id="cross_container" class="exfer">
                <input type="hidden" name="datetime" id="datetime" value="<?php echo $cross["begin_at"]; ?>">
                <div id="cross_times_area">
                    <h3 id="pv_relativetime">
                        <?php if($begin_at_relativetime == 0){ echo "Anytime"; } else { echo $begin_at_relativetime; } ?>
                    </h3>
                    <p class="tm" id="cross_times">
                        <?php echo $begin_at_humandatetime;?>
                    </p>
                </div>
                <div id="cross_place_area">
                    <h3 id="pv_place_line1" class="pv_place_line1_normal">
                        <?php echo ParseURL($place_line1) ?: 'Somewhere'; ?>
                    </h3>
                    <p id="pv_place_line2" class="tm">
                        <?php echo ParseURL(str_replace('\r', '<br />', $cross["place"]["line2"])); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>














</body>
</html>
