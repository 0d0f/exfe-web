<?php include "share/header.php"; ?>
<style type="text/css">
#set_password_titles{ font-size:34px;height:60px; }
#set_password_desc{ height:70px;line-height:18px;text-align:center;font-size:21px; }
.identity_dialog_main .identification_bottom_btn{ text-align:center; }
.identity_dialog_main input.btn_85{ float:none; }
</style>
</head>
<body style="background-color:#EFEFEF;">
<?php
$user_identity = $this->getVar("userIdentity");
$user_name = $this->getVar("userName");
$user_token = $this->getVar("userToken");
?>
<?php include "share/nav.php"; ?>

<div id="userResetPwdBox" class="identification_dialog idialog_inpage"></div>
<script type="text/javascript">
    var showSpecialIdentityDialog = true;
    var user_identity = '<?php echo $user_identity; ?>';
    var user_name = '<?php echo $user_name; ?>';
    var user_token = '<?php echo $user_token; ?>';
    jQuery(document).ready(function(){
        odof.user.status.doShowResetPwdDialog("userResetPwdBox");
        jQuery("#show_identity_box").val(user_identity);
        jQuery("#user_display_name").val(user_name);
        jQuery("#identification_user_token").val(user_token);
    });
</script>
</body>
</html>
