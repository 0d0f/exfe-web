<?php include 'share/header.php'; ?>
<link rel="stylesheet" type="text/css" href="/static/css/forbidden.css">
</head>
<body id="special_page_body">
<?php include 'share/nav.php'; ?>
<?php $referer = $this->getVar('referer'); ?>
<?php $cross_id = $this->getVar('cross_id'); ?>
<script type="text/javascript">
var referer = "<?php echo strip_tags($referer);  ?>";
var cross_id = "<?php echo strip_tags($cross_id); ?>";
</script>
<div class="special_page">
    <div class="special_main_area">
        <p class="special_page_title">Sorry, the <span class="special_x">X</span> you're requesting is private.</p>
        <p class="special_page_subtitle">Please sign in as attendee.</p>
    </div>
    <!-- div class="spacial_page_buttons">
        <button onclick="javascript:location.href = '/s/login';">Sign in</button>
    </div -->
</div>
<script type="text/javascript">
    var showSpecialIdentityDialog = true;
    var pageFlag = "forbidden";
    jQuery(document).ready(function(){
        odof.user.status.doShowLoginDialog(null, null, null, "win", 200);
        //jQuery("#global_user_login_btn").unbind("click");
        jQuery("#global_user_login_btn").bind("click",function(){
            odof.user.status.doShowLoginDialog(null, null, null, "win", 200);
            //odof.user.status.doShowLoginDialog();
        });
    });
</script>
</body>
</html>
