<?php
    $page = 'cross';
    include 'share/header.php';
?>
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
       . "var user_token='".$this->getVar('user_token')."'; \r\n"
       . "var token='".$_GET["token"]."'; \r\n"
       . "var id_name='".$global_name."'; \r\n"
       . "var location_uri='".SITE_URL."/!".$cross["id"]."';\r\n"
       . "</script>\r\n";

    // ready cross data
    $exfees = $cross['exfee'];
    unset($cross['exfee']);
    echo '<script>'
       . 'var myIdentity = ' . json_encode($myidentity) . ','
       .     'crossData  = ' . json_encode($cross)      . ','
       .     'crossExfee = ' . json_encode($exfees)     . ','
       .     "myrsvp     = {$myrsvp};"
       . '</script>';
?>
<script type="text/javascript" src="/static/?f=js/libs/showdown.js"></script>
<script type="text/javascript" src="/static/?f=js/libs/jquery.ba-outside-events.js"></script>

<!-- Exfee Widget -->
<link type="text/css" href="/static/?f=css/exfee.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>" rel="stylesheet">
<script src="/static/?f=js/apps/exfee.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
<!-- X Render -->
<link type="text/css" href="/static/?f=css/x.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>" rel="stylesheet">
<script src="/static/?f=js/apps/x.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
<!-- X Edit -->
<link type="text/css" href="/static/?f=css/xedit.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>" rel="stylesheet">
<script src="/static/?f=js/apps/record.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
<script src="/static/?f=js/apps/xedit.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>
<!-- EXFE Maps -->
<link type="text/css" rel="stylesheet" href="/static/?f=css/maps.css&t=<?php echo STATIC_CODE_TIMESTAMP; ?>">
<script src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script src="/static/?f=js/apps/maps.js&t=<?php echo STATIC_CODE_TIMESTAMP; ?>"></script>

<!-- 兼容代码 -->
<style>
#x_conversation_list {
  margin: 10px 0 0;
}
#xExfeeArea .exfeegadget_inputbox {
  height: 20px;
  padding: 4px;
}
#x_conversation_input {padding: 0;}
</style>
<script>
    define(function (require) {
      var Bus = require('bus');
      Bus.on('app:crossdata', function (token, status) {
      });
    });
</script>

<div class="content">
    <div id="edit_x_bar" style="display:none;">
        <div id='edit_x_submit_loading' style="display:none;"></div>
        <p class="titles">Editing</p>
        <p id="error_msg" class="error_msg"></p>
        <p class="done_btn">
            <a href="javascript:void(0);" id="submit_data">Done</a>
        </p>
        <p class="revert">
            <a id="revert_x_btn" href="javascript:void(0);">Revert</a>
        </p>
    </div>
    <div id="x_view">
        <div class="exfe_bubble" id="x_time_bubble" style="display:none;">
            <div class="x_dt_input">
                <input name="x_datetime_original" id="x_datetime_original">
            </div>
            <div class="x_dt_msg"></div>
            <div id="x_time_container"></div>
        </div>
        <div class="exfe_bubble" id="x_place_bubble" style="display:none;">
            <div class="input_box">
                <textarea name="place_content" id="place_content"></textarea>
                <span class="icon"></span>
                <div id="gather_place_selector" style="display:none;"></div>
            </div>
        </div>
        <div id="x_menu_bar">
            <p class="lock_icon" id="private_icon"></p>
            <p class="lock_icon_desc" id="private_hint" style="display:none;">
                <span>Private X:</span>
                <br>
                Only attendees can access, and change other’s status.
            </p>
            <p class="edit_icon" id="edit_icon"></p>
            <p class="edit_icon_desc" id="edit_icon_desc" style="display:none;">
                Edit this cross.
            </p>
        </div>
        <div id="x_view_content"></div>
    </div>
</div>
</body>
</html>
