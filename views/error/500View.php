<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en" dir="ltr"> <!--<![endif]-->
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1" />
  <title>Server Error · EXFE.COM</title>
  <meta name="author" content="EXFE Inc." />
  <meta name="robots" content="index, follow" />
  <meta name="keywords" content="EXFE, ·X·, cross, exfee, gather, Gather a ·X·, hangout, gathering, invite, RSVP" />
  <meta name="description" content="EXFE, a utility for hanging out with friends." />
  <meta name="copyright" content="Copyright 2012 EXFE Inc" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <link rel="shortcut icon" href="/static/img/favicon.png" />
  <link rel="apple-touch-icon" href="/static/img/favicon.png" />
  <style>
    body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,code,form,fieldset,legend,input,textarea,p,blockquote,th,td,hr,button,img { margin: 0; padding:0; border: 0; }
    html, body { height: 100%; }
    body {
      background-attachment: fixed;
      background-color: #84A4BF;
      background-image: -moz-linear-gradient(top, #5A7A95, #C3E3FD);
      background-image: -ms-linear-gradient(top, #5A7A95, #C3E3FD);
      background-image: -moz-linear-gradient(center top , #5A7A95, #C3E3FD);
      background-image: -webkit-linear-gradient(top, #5A7A95, #C3E3FD);
      background-image: -o-linear-gradient(top, #5A7A95, #C3E3FD);
      background-image: linear-gradient(top, #5A7A95, #C3E3FD);
      background-repeat: repeat-x;
    }
    .container {
      display: table;
      margin: 0 auto;
      width: 340px;
      height: 100%;
    }
    .outer {
      display: table-cell;
      vertical-align: middle;
      position: relative;
    }
    .inner {
      -webkit-perspective: 600px;
      -moz-perspective: 600px;
      -ms-perspective: 600px;
      -o-perspective: 600px;
      perspective: 600px;
      position: relative;
      width: 340px;
      height: 340px;
    }

    #exfe {
      /*
      -moz-transition: -moz-transform 144ms ease 0s;
      -webkit-transition: -webkit-transform 144ms ease 0s;
      */
      position: absolute;
      width: 100%;
      height: 100%;
    }

    #bubble {
      -webkit-transform: translateZ(50px) scale(0.89583333, 0.89583333);
      -moz-transform: translateZ(50px) scale(0.89583333, 0.89583333);
      -os-transform: translateZ(50px) scale(0.89583333, 0.89583333);
      -o-transform: translateZ(50px) scale(0.89583333, 0.89583333);
      transform: translateZ(50px) scale(0.89583333, 0.89583333);
      position: absolute;
      top: -70px;
      left: -70px;
    }
  </style>
  <script type="text/javascript" src="/static/js/jquery/1.8.0/jquery.min.js"></script>
  <script type="text/javascript" src="/static/js/modernizr/2.5.3/modernizr.min.js"></script>
</head>
<body>
  <div class="container">
    <div class="outer">
      <div class="inner">
        <img id="exfe" src="/static/img/exfe.png" width="340" height="340" alt="" />
        <img id="bubble" src="/static/img/500_bubble.png" width="480" height="480" alt="" />
      </div>
    </div>
  </div>

  <script type="text/javascript">
    var centerX, centerY, pageX, pageY
      , $exfe = $('#exfe')
      , $bubble = $('#bubble')
      , w = 340 , centre = w / 2
      , offset = $exfe.offset()
      , dx , dy , tx = 0 , ty = 0
      , rx = 0 , ry = 0 , st, sr, sr2, ss;

    centerX = offset.left + centre;
    centerY = offset.top + centre;

    $(window).resize(function () {
      offset = $exfe.offset();
      centerX = offset.left + centre;
      centerY = offset.top + centre;
    });

    $(document).on('mousemove touchmove', function (e) {
      document.title = e.type + ', ' + e.touches[0].pageX + ', ' + e.touches[0].pageY;
      if (e.type === 'touchmove') {
        var touch = e.touches[0];
        pageX = touch.pageX;
        pageY = touch.pageY;
        alert(pageX);
        document.title = pageX + ', ' + pageY;
      }
      else {
        pageX = e.pageX;
        pageY = e.pageY;
      }

      dx = pageX - centerX;
      dy = pageY - centerY;

      tx = -dx / centerX * 5;
      ty = -dy / centerY * 5;

      rx = dx / centerX * 8;
      ry = -dy / centerY * 8;

      sr = 'rotateY(' + rx + 'deg) rotateX(' + ry + 'deg)';
      sr2 = 'rotateY(' + -rx + 'deg) rotateX(' + -ry + 'deg)';
      st = 'translate(' + tx + 'px, ' + ty + 'px)';
      ss = sr2 + ' ' + st;

      $bubble.css({
        '-webkit-transform': 'translateZ(50px) scale(0.89583333, 0.89583333) ' + ss,
        '-moz-transform': 'translateZ(50px) scale(0.89583333, 0.89583333) ' + ss,
        '-ms-transform': 'translateZ(50px) scale(0.89583333, 0.89583333) ' + ss,
        // wtf? Opera css3D
        '-o-transform': st,
        'transform': 'translateZ(50px) scale(0.89583333, 0.89583333) ' + ss
      });

      $exfe.css({
        '-webkit-transform': sr,
        '-moz-transform': sr,
        '-ms-transform': sr,
        '-o-transform': sr,
        'transform': sr
      });
    });
  </script>
<?php if (SITE_URL === 'https://exfe.com'): ?>
  <script type="text/javascript">
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-31794223-2']);
    _gaq.push(['_trackPageview']);
    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();
  </script>
<?php endif; ?>
</body>
</html>
