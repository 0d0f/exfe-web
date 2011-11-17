<?php include "share/header.php" ?>
    <link type="text/css" rel="stylesheet" href="/static/css/home.css" />
    <script type="text/javascript">
        var page_flag = "home_page";
    </script>
</head>
<body id="home">
    <?php include "share/nav.php" ?>
    <div class="home_banner" id="home_banner"><span id="x_code_img"></span></div>
    <div class="home_bottom">
        <div class="gather_btn">
            <a href="/x/gather"><img src="/static/images/home_gather_btn.jpg" alt="Gather" title="Gather" /></a>
        </div>
        <div class="home_bottom_btn"></div>
    </div>
</body>
<script type="text/javascript">
jQuery(document).ready(function() {
    document.title = 'EXFE';
    var winSize = odof.util.getWindowSize();
    winHeight = winSize.height;
    if(winHeight < 600){
        jQuery("#home_banner").css({"height":"460px"});
        jQuery("#x_code_img").css({"margin-top":"20px"});
    }
});
</script>
</html>
