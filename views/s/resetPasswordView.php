<?php include "share/header.php"; ?>
</head>
<body style="background-color:#EFEFEF;">
<?php include "share/nav.php"; ?>

<div id="userResetPwdBox" class="identification_dialog idialog_inpage"></div>
<script type="text/javascript">
    var showIdentificationDialog = false;
    var identity = "handaoliang@gmail.com";
    jQuery(document).ready(function(){
        odof.user.status.doShowResetPwdDialog("userResetPwdBox");
    });
</script>
</body>
</html>
