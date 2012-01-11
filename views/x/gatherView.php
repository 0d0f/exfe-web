<?php
    include 'share/header.php';
    global $exfe_res;
?>
<script src="/static/js/libs/showdown.js"></script>
<script src="/static/js/libs/jquery.ba-outside-events.js"></script>
<!-- Exfee Widget -->
<link type="text/css" href="/static/css/exfee.css" rel="stylesheet">
<script src="/static/js/apps/exfee.js"></script>
<!-- X Render -->
<link type="text/css" href="/static/css/x.css" rel="stylesheet">
<script src="/static/js/apps/x.js"></script>
<!-- X Gather -->
<link type="text/css" rel="stylesheet" href="/static/css/gather.css">
<script src="/static/js/apps/gather.js"></script>
<!-- Exfe Calendar -->
<link type="text/css" rel="stylesheet" href="/static/js/exlibs/excal/skin/default/excal.css">
<script src="/static/js/exlibs/excal/excal.js"></script>
</head>
<body>
<?php
    include 'share/nav.php';
    $myIdentity        = $this->getVar('myidentity');
    $external_identity = $myIdentity ? $myIdentity['external_identity'] : null;
    $defaultTitle      = $global_name != '' ? "Meet {$global_name}" : 'Edit title here';
    echo '<script>'
       . "var myIdentity   = " . json_encode($myIdentity) . ","
       .     "defaultTitle = '{$defaultTitle}',"
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
                <input type="text" id="gather_hostby" class="gather_blur gather_input" <?php echo $external_identity ? 'disabled="disabled" ' : ''; ?> value="<?php echo $external_identity ?: 'Your Identity'; ?>">
            </li>

            <li id="gather_exfee_blank">
                <label class="exfee">Exfee</label>
                <div id="gatherExfee" class="gather_input"></div>
                <!--div id="exfee_warning">
                    No more than 12 attendees. Sorry we're still working on it.
                </div-->
            </li>

            <li id="gather_privacy_blank">
                <label class="privacy">Privacy</label>
                <div id="gather_privacy" class="gather_input">
                    <span id="gather_privacy_info">This is a private <span class="x">X</span>.</span>
                    <br>
                    <span id="gather_privacy_info_desc">Only attendees can access, and change other's status.</span>
                    <div id="gather_failed_hint">Submission failed.</div>
                </div>
            </li>

            <li id="gather_submit_blank">
                <div id="gather_submit_area" class="gather_input">
                    <button type="button" id="gather_submit">Submit</button>
                    <a href="/<?php echo $external_identity ? 's/profile' : ''; ?>" id="gather_discard">Discard</a>
                </div>
            </li>
        </ul>
    </div>
    <div style="clear: both;"></div>

    <div id="x_view">
        <div id="x_view_preview"></div>
        <div id="x_view_content"></div>
    </div>

</div>
</body>
</html>
