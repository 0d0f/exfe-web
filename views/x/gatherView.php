<?php
    include 'share/header.php';
    global $exfe_res;
?>
<link type="text/css" rel="stylesheet" href="/static/css/gather.css">
<!-- Exfe Calendar -->
<link type="text/css" rel="stylesheet" href="/static/js/exlibs/excal/skin/default/excal.css">
<script src="/static/js/exlibs/excal/excal.js"></script>
<script src="/static/js/libs/showdown.js"></script>
<script src="/static/js/libs/jquery.ba-outside-events.js"></script>
<script src="/static/js/apps/gather.js"></script>
</head>
<body>
<?php
    include 'share/nav.php';
    $external_identity = $this->getVar('external_identity');
?>
<div id="content">
    <div id="gather_form">
        <ul>
            <li>
                <label class="title"><?php echo $exfe_res["gather"]["Title"];?></label>
                <input type="text" name="title" id="g_title" class="gather_blur" value="<?php if($global_name != ""){ ?>Meet <?php echo $global_name; }else{ ?>Edit title here<?php } ?>" />
            </li>

            <li id="gather_desc_blank">
                <div id="gather_desc_bg" class="gather_blur"><?php echo $exfe_res["gather"]["Write_some_words_about_this_X"];?></div>
                <label class="description"><?php echo $exfe_res["gather"]["Description"];?></label>
                <textarea name="description" id="g_description"></textarea>
            </li>

            <li>
                <div id="calendar_map_container"></div>
                <div id="gather_date_bg" class="gather_blur">Sometime</div>
                <label class="date">Date &amp; Time</label>
                <input type="text" name="datetime_original" id="datetime_original">
                <input type="hidden" name="datetime" id="datetime" value="">
                <!-- @todo== p class="redbtn">Incorrect format. e.g:6:30pm, 1/15/2011</p -->
            </li>

            <li id="gather_place_blank">
                <div id="gather_place_bg" class="gather_blur">Somewhere</div>
                <label class="location">Location</label>
                <textarea name="place" id="g_place" ></textarea>
            </li>

            <li>
                <label class="hostby">Host By</label>
                <input type="text" name="hostby" id="hostby" class="gather_blur" <?php echo $external_identity ? 'enter="true" disabled="disabled" ' : ''; ?> value="<?php echo $external_identity ?: 'Your Identity'; ?>"/>
            </li>

            <li>
                <!--div id="exfee_warning">No more than 12 attendees. Sorry we're still working on it.</div-->
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
    </div>

</div>
</body>
</html>
