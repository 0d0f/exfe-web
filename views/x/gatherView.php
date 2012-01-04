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
    $defaultTitle      = $global_name != '' ? "Meet {$global_name}" : 'Edit title here';
    echo '<script>'
       . "var defaultTitle = '{$defaultTitle}',"
       .     "defaultDesc  = '{$exfe_res['gather']['Write_some_words_about_this_X']}',"
       .     "defaultTime  = 'Sometime',"
       .     "defaultPlace = 'Somewhere';"
       . '</script>';
?>
<div class="content">
    <div id="gather_form">
        <ul>
            <li>
                <label class="title"><?php echo $exfe_res["gather"]["Title"];?></label>
                <input type="text" id="gather_title" class="gather_blur gather_input">
            </li>

            <li id="gather_desc_blank">
                <label class="description"><?php echo $exfe_res["gather"]["Description"];?></label>
                <div id="gather_desc_x" class="gather_blur gather_input"></div>
                <textarea id="gather_desc" class="gather_input"></textarea>
            </li>

            <li>
                <label class="date">Date &amp; Time</label>
                <div id="gather_date_x" class="gather_blur gather_input"></div>
                <input type="text" id="datetime_original" class="gather_input">
                <div id="calendar_map_container" class="gather_input"></div>
                <!-- @todo== p class="redbtn">Incorrect format. e.g:6:30pm, 1/15/2011</p -->
            </li>

            <li id="gather_place_blank">
                <label class="place">Location</label>
                <div id="gather_place_x" class="gather_blur gather_input"></div>
                <textarea id="gather_place" class="gather_input"></textarea>
            </li>

            <li>
                <label class="hostby">Host By</label>
                <input type="text" id="gather_hostby" class="gather_blur gather_input" <?php echo $external_identity ? 'disabled="disabled" ' : ''; ?> value="<?php echo $external_identity ?: 'Your Identity'; ?>"     >
            </li>

            <li>
                <label class="exfee">Exfee</label>
                <!--div id="exfee_warning">
                    No more than 12 attendees. Sorry we're still working on it.
                </div-->
            </li>

            <li id="gather_privacy_blank">
                <label class="privacy">Privacy</label>
                <p id="gather_privacy" class="gather_input">
                    <span id="gather_privacy_info">This is a private <span class="x">X</span>.</span>
                    <br>
                    <span id="gather_privacy_info_desc">Only attendees can access, and change other's status.</span>
                </p>
            </li>

            <li id="gather_submit_blank">
                <p id="gather_submit_area" class="gather_input">
                    <button type="button" id="gather_submit">Submit</button>
                    <a href="/<?php echo $external_identity ? 's/profile' : ''; ?>" id="gather_discard">Discard</a>
                </p>
                <div id="gather_failed_hint">Submission failed.</div>
            </li>
        </ul>
    </div>

    <div id="x_view"></div>

</div>
</body>
</html>
