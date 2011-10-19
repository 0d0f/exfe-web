<?php include "share/header.php" ?>
    <link type="text/css" rel="stylesheet" href="/static/css/home.style.css" />
</head>
<body id="home">
    <?php include "share/nav.php" ?>
    <div class="home_banner" id="home_banner"><span id="x_code_img"></span></div>
    <div class="home_bottom">
        <div class="gather_btn">
            <a href="/x/gather"><img src="/static/images/home_gather_btn.jpg" alt="Gather" title="Gather" /></a>
        </div>
        <ul>
            <li class="one"></li>
            <li class="two"></li>
            <li class="three"></li>
            <li class="four"></li>
            <li class="five"></li>
            <li class="six"></li>
            <li class="seven"></li>
            <li class="eight"></li>
            <li class="nine"></li>
            <li class="ten"></li>
            <li class="eleven"></li>
        </ul>
    </div>
</body>
<script type="text/javascript">
jQuery(document).ready(function(){
    var winSize = odof.util.getWindowSize();
    winHeight = winSize.height;
    if(winHeight < 600){
        jQuery("#home_banner").css({"height":"460px"});
        jQuery("#x_code_img").css({"margin-top":"20px"});
    }
});
</script>
</html>
