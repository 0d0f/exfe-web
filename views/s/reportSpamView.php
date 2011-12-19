<?php include "share/header.php"; ?>
<style type="text/css">
.reporting_msg { width:860px; margin:auto; padding:90px 0px; overflow:hidden; }
.reporting_msg p { font-size:18px; text-align:center; }
.reporting_msg p.titles{ font-size:34px; height:70px; }
</style>
</head>
<body style="background-color:#EFEFEF;">
<?php include "share/nav.php"; ?>
<div class="reporting_msg" id="reporting_msg">
    <p class="titles">Thank you for reporting spam.</p>
    <p>Redirecting to <span style="color:#0591AC">EXFE</span> in seconds…</p>
</div>
<script>
jQuery(document).ready(function(){
    //jQuery("#reporting_msg").fadeIn(3000);
    jQuery("#reporting_msg").fadeTo(4000, 0.2, function(){
        window.location.href="/";
    });
});
</script>
</body>
</html>
