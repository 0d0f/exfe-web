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
$identityInfo = $this->getVar("identityInfo");
?>
<?php include "share/nav.php"; ?>
<?php
//如果已经设置密码。则直接显示验证成功的效果。
if($identityInfo["need_set_pwd"] == "no"){
?>
<div id="verify_success" style="text-align:center; font-size:34px; margin-top:140px;" class="identification_dialog idialog_inpage">Verification completed</div>
<script type="text/javascript">
jQuery("#verify_success").fadeTo("slow", 0.3, function(){
    window.location.href="/s/profile";
});
</script>
<?php
}else{
?>
<div id="userResetPwdBox" class="identification_dialog idialog_inpage"></div>
<script type="text/javascript">
    var showSpecialIdentityDialog = true;
    var user_identity = '<?php echo $identityInfo["identity"]; ?>';
    var user_name = '<?php echo $identityInfo["display_name"]; ?>';
    var user_token = '<?php echo $identityInfo["reset_pwd_token"]; ?>';
    jQuery(document).ready(function(){
        odof.user.status.doShowResetPwdDialog("userResetPwdBox");
        jQuery("#show_identity_box").val(user_identity);
        jQuery("#user_display_name").val(user_name);
        jQuery("#identification_user_token").val(user_token);
        jQuery("#set_password_titles").html("Identity verified");
        jQuery("#set_password_desc").html("Please set password to complete.");
    });
</script>
<?php
}
?>
</body>
</html>
