<?php include 'share/header.php'; ?>
</head>
<body id="special_page_body">
<?php include 'share/nav.php'; ?>
<?php $referer = $this->getVar('referer'); ?>
<script type="text/javascript">
var referer = "<?php echo $referer  ?>";
</script>
<div class="special_page">
    <div class="special_main_area">
        <p class="special_page_title">Sorry, the <span class="special_x">X</span> you're requesting is private.</p>
        <p class="special_page_subtitle">Please sign in as invited user.</p>
    </div>
    <div class="spacial_page_buttons">
        <button onclick="javascript:location.href = '/s/login';">Sign in</button>
    </div>
</div>
<script type="text/javascript">
    var showIdentificationDialog = false;
    jQuery(document).ready(function(){
        odof.user.status.doShowLoginDialog();
    });
</script>
</body>
</html>
