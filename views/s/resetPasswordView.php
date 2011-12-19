<?php include "share/header.php"; ?>
<style type="text/css">
#set_password_titles{ font-size:34px;height:60px; }
.identity_dialog_main #set_password_desc{ height:65px;line-height:18px;text-align:center;font-size:21px; }
.identity_dialog_main .identification_bottom_btn{ text-align:center; }
.identity_dialog_main input.btn_85{ float:none; }
.identity_dialog_main .identification_bottom_btn { top:290px; }
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
<div id="resetPwdSuccess" style="text-align:center; font-size:34px; margin-top:140px; display:none;" class="identification_dialog idialog_inpage">Password set</div>
<script>
    var showSpecialIdentityDialog = true;
    var user_identity = '<?php echo $user_identity; ?>';
    var user_name = '<?php echo $user_name; ?>';
    var user_token = '<?php echo $user_token; ?>';
    jQuery(document).ready(function(){
        odof.user.status.doShowResetPwdDialog("userResetPwdBox");
        jQuery("#show_identity_box").val(user_identity);
        jQuery("#user_display_name").val(user_name);
        jQuery("#identification_user_token").val(user_token);
        jQuery("#set_password_titles").html("Forgot Password");
        jQuery("#set_password_desc").html("Please set your new password.");
    });
</script>

</body>
</html>
