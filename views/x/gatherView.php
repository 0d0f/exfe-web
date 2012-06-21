<?php
    include 'share/header.php';
    global $exfe_res;
?>
<script type="text/javascript" src="/static/?f=js/libs/showdown.js"></script>
<script type="text/javascript" src="/static/?f=js/libs/jquery.ba-outside-events.js"></script>

<!-- Exfee Widget -->
<link type="text/css" href="/static/?f=css/exfee.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>" rel="stylesheet">
<script src="/static/?f=js/apps/exfee.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
<!-- X Render -->
<link type="text/css" href="/static/?f=css/x.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>" rel="stylesheet">
<script src="/static/?f=js/apps/x.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
<!-- X Gather -->
<link type="text/css" rel="stylesheet" href="/static/?f=css/gather.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>">
<script src="/static/?f=js/apps/gather.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
<!-- EXFE Maps -->
<link type="text/css" rel="stylesheet" href="/static/?f=css/maps.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>">
<script src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script src="/static/?f=js/apps/maps.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

</head>
<body>
<?php
    include 'share/nav.php';
    $myIdentity        = $this->getVar('myidentity');
    $external_identity = $myIdentity ? $myIdentity['external_identity'] : null;
    $defaultTitle      = $global_name != '' ? "Meet {$global_name}" : 'Edit title here';
    $backgrounds       = $this->getVar('backgrounds');
    echo '<script>'
       . "var myIdentity   = " . json_encode($myIdentity) . ",\r\n"
       .     "defaultTitle = '{$defaultTitle}',\r\n"
       .     "defaultDesc  = '{$exfe_res['gather']['Write_some_words_about_this_X']}',\r\n"
       .     "defaultTime  = 'Sometime',\r\n"
       .     "sampleTime   = '12-20-2012 09:00 AM',\r\n"
       .     'backgrounds  = ' . json_encode($backgrounds) . ",\r\n"
       .     "defaultPlace = 'Somewhere';"
       . '</script>';
?>

<!-- 兼容性代码 -->
<style>
  #gather_form input[type="text"] {
    margin-left: 0;
  }
  #gatherExfee_exfeegadget_inputbox_desc {
    height: 20px; padding: 4px;
  }
</style>

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
                <div id="gather_place_selector" style="display:none;"></div>
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
                    <span id="exfe_iphone_ad">
                        <a href="http://itunes.apple.com/us/app/exfe/id514026604" target="_blank" class="exfe">EXFE</a> for iPhone, keep everything on track.
                    </span>
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
