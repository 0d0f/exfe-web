<?php include "share/header.php"; ?>
<link type="text/css" href="/static/css/ui-lightness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<script type="text/javascript" src="/static/js/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="/static/js/gather.js"></script>
<script type="text/javascript" src="/static/js/timepicker.js"></script>
<body>
<?php include "share/nav.php"; ?>
<div class="centerbg">
  <div class="createset">
  <!--<h3>Gather for your <span>X</span></h3>-->

<ul>
<form action="" method="post" id="gatherxform">
<li><label class="title">Title:</label><input type="text"  name="title" id="g_title" onfocus="this.str='Edit title here'; if(this.value==this.str){this.value=''}" onblur="if(this.value==''){this.value=this.str}"  value="Edit title here"/></li>

<li><label class="description">Description:</label><textarea name="comment2" id="g_description" onfocus="if(this.className.indexOf('description')!=-1){quickreplytxt=this.value;this.value='';this.style.color='#696969'}" onblur="if(this.value==''){this.value=quickreplytxt;this.style.color='#d2d2d2'}">Write some description for your exfe. (optional)</textarea>
</li>
       
<li><label class="date">Date &amp; Time</label>  <input type="text"  name="title" id="datetime" onfocus="this.str='6:30PM, this Friday'; if(this.value==this.str){this.value='';this.style.color='#696969'}" onblur="if(this.value==''){this.value=this.str;this.style.color='#d2d2d2'}" value="6:30PM, this Friday"/>
<p class="redbtn">Incorrect format. e.g:6:30pm, 1/15/2011 </p>
</li>

<li><label class="location">Location:</label>  <textarea name="comment2" id="g_place"  onfocus="if(this.className.indexOf('location')!=-1){quickreplytxt=this.value;this.value='';this.style.color='#696969'}" onblur="if(this.value==''){this.value=quickreplytxt;this.style.color='#d2d2d2'}">Crab House
Pier 39, 203 C
San Francisco, CA
(555) 434-2722</textarea></li>

<li><label class="exfee">exfee:</label>  
<p class="count"> <a href="#"> Mark all as confirmed</a> count: <span>16</span></p>
<input type="submit" id="post_submit" name="commit" title="Say!" value="">
<textarea name="comment" id="exfee" onfocus="if(this.className.indexOf('exfee')!=-1){quickreplytxt=this.value;this.value='';this.style.color='#696969'}" onblur="if(this.value==''){this.value=quickreplytxt;this.style.color='#d2d2d2'}" >Enter attendees’ email or id</textarea>        
<div class="creattext">
  <div class="selecetafri">
    <div class="sover" id="exfee_pv">
      <ul class="samlcommentlist">
        <li class="addjn">
          <p class="pic20"><img src="/eimgs/<?php echo $global_avatar_file_name;?>" alt="" /></p>
          <p class="smcomment"><span><?php echo $global_name;?></span><span class="lb">host</span></p>
          <button type="button"></button>
        </li>
      </ul>
    </div>
  </div>
</div>
</li>

 <li>
<label class="privacy">Privacy:</label><p class="privacy"><span>This is a private <strong>X</strong>.</span> <!--So only attendees could see details.--></p>
        <button type="button" class="submit">Submit</button> <a href="#" class="discard"> Discard </a> </li>
</form>
  </ul>

  
  </div>  


<a href="#" id="gather_x">submit</a>
<!--input id="user_submit" name="commit"  type="submit" value="Submit" /-->


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
