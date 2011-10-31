<?php include "share/header.php"; ?>
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
    var showIdentificationDialog = false;
    var user_identity = '<?php echo $user_identity; ?>';
    var user_name = '<?php echo $user_name; ?>';
    var user_token = '<?php echo $user_token; ?>';
    jQuery(document).ready(function(){
        odof.user.status.doShowResetPwdDialog("userResetPwdBox");
        jQuery("#show_identity_box").html(user_identity);
        jQuery("#user_display_name").val(user_name);
        jQuery("#identification_user_token").val(user_token);
    });
</script>
</body>
</html>
