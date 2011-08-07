<?php include "share/header.php"; ?>
<link type="text/css" href="/static/css/ui-lightness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<script type="text/javascript" src="/static/js/gather.js"></script>
<script type="text/javascript" src="/static/js/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="/static/js/timepicker.js"></script>
<body>
<?php include "share/nav.php"; ?>
<h3>Gather for your X</h3>
<form action="" method="post" id="gatherxform">
<label>Title:</label><input type="text"  name="title" id="g_title" class="inputText"/><br/>
<label>Description:</label><textarea name="description" id="g_description"></textarea> <br/>
<label>Date & time:</label><input type="text"  name="datetime" id="datetime" class="inputText"/><br/>
<label>place:</label><textarea name="place" id="g_place"></textarea><br/>
<label>exfee:</label><input type="text"  name="exfee" id="exfee" clas="inputText"/><br/>
<input type="text"  name="exfee_list" id="exfee_list" clas="inputText"/>
<div id="exfee_pv">
</div>
<a href="#" id="gather_x">submit</a>
<!--input id="user_submit" name="commit"  type="submit" value="Submit" /-->

</form>

<div class="albg" id="content">
<div class="step" id="index">
<h2 id="pv_title">Title</h2>
<div class="exfel">
<p class="text" id="pv_description">new cross for test</p><a href="">Expand</a>

<ul class="ynbtn">
<li><a class="yes" href="/3/rsvp/yes">Yes</a></li>
<li><a class="no" href="/3/rsvp/no">No</a></li>
<li><a class="maybe" href="/3/rsvp/maybe">Maybe</a></li><li>
</li></ul>


</div><!--exfel-->


<div class="exfer">
<h3>3 months later</h3>
<p class="tm">
12:00 AM, Oct 20, 2011 </p>
<h3 id="pv_place_line1">huoju's home</h3>
<p class="tm" id="pv_place_line2">shanghai<br>pudong</p>

<div class="exfee">
<div class="feetop"><h3>exfee</h3>  <p class="of"><em class="bignb">2</em> <em class="malnb">3 of <br>confirmed</em></p></div>
<ul class="samlcommentlist">

<li>
<p class="pic20"><img alt="" src="/eimgs/1.png"></p>
<p class="smcomment"><span></span> <span class="lb">host</span>virushuo@gmail.com</p>
<p class="cs"><em class="c1"></em></p>
</li>
<li>
<p class="pic20"><img alt="" src="/eimgs/"></p>
<p class="smcomment"><span></span> gokeeper@gmail.com </p>
<p class="cs"><em class="c1"></em></p>
</li>

</ul>
</div><!--exfee-->
</div><!--exfer-->


</div><!--/#index-->
</div>


</body>
</html>
