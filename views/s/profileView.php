<?php include "share/header.php"; ?>
<script type="text/javascript" src="/static/js/profile.js"></script>
<body>
<?php include "share/nav.php"; ?>
<?php 
$identities=$this->getVar("identities");
//print_r($identities);
$user=$this->getVar("user");
?>
<!--profile_for_develop-->
<div class="centerbg">
<div class="edit_user">
<div id="profile_avatar"><img class="big_header" src="/eimgs/80_80_<?php echo $user["avatar_file_name"];?>" alt="" /></div>
<button style="display:none" id="changeavatar">Change...</button>
<div class="u_con">
<h1 id="profile_name" status="view"><?php echo $user["name"];?></h1>
<p><img class="s_header" src="" alt="" /><b><span class="name">SteveE</span> @<em>stevexfee</em></b> <i><img class="worning" src="images/translation.gif" alt="" />Authorization failed <button type='button' class="boright">Re-Authorize</button></i></p>
<p><img class="s_header" src="images/user_header_2.jpg" alt="" /><em>steve@0d0f.com</em><i><img class="worning" src="images/translation.gif" alt="" />Authorization failed <button type='button' class="boright">Resend</button></i></p>
</div>
<div class="u_num">
<p>57</p>
<span>exfes attended</span>
<button id="editprofile">Edit</button>
</div>

</div>
<div class="shadow_840"></div>
	<div class="profile_main">
<div class="left">
<div class="p_right"><img src="images/translation.gif" class="l_icon"  />Upcoming<img src="images/translation.gif" class="arrow"  /></div>
			  <div class="coming">
			  <div class="a_tltle">Dinner in SF</div>
			  <div class="maringbt">
			  <p>6:30PM Tomorrow, Friday April 8 </p>
			  <p>Crab House <span>(Pier 39, 203 C, San Francis…)</span></p>
			  <p>4 confirmed: gkp, dm, Virushuo x2</p>
			  </div>
			  </div>
			  <div class="coming">
			  <div class="a_tltle">Ferry famers market</div>
			  <div class="maringbt">
			  <p>10AM, Saturday April 9  </p>
			  <p>Ferry market <span>(1 Ferry Building, San Francisco…)</span></p>
			  <p>6 confirmed: gkp, dm, Virushuo x2, delphij, Rainux</p>
			  </div>
			  </div>
			  <div class="coming">
			  <div class="a_tltle">0d0f team con call</div>
			  <div class="maringbt">
			  <p>11PM, Saturday April 9</p>
			  <p>4 confirmed: gkp, dm, Virushuo, Rainux</p>
			  </div>
			  </div>
		      <div class="p_right">Next 7 days<img src="images/translation.gif" class="arrow"  /></div>
			  <div class="coming">
			  <div class="a_tltle">0d0f team con call</div>
			  <div class="maringbt">
		 	  <p>11PM, Saturday April 24 </p>
			  <p>4 confirmed: gkp, dm, Virushuo, Rainux</p>
			  </div>
			  </div>
			  <div class="p_right">Later<img src="images/translation.gif" class="arrow"  /></div>
			  <div class="coming">
			  <div class="a_tltle">Farewell party</div>
		 	  <div class="maringbt">
			  <p>5PM, Friday April 1</p>
			  <p>12 confirmed: gkp, dm, Virushuo x2, Rainux, joexfee@0d0f.com …</p>
			  </div>
			  </div>
			  <div class="p_right">Past<img src="images/translation.gif" class="arrow"  /></div>
			  <div class="coming">
			  <div class="a_tltle">0d0f team con call</div>
		 	  <div class="maringbt">
			  <p>11PM, Saturday April 16</p>
			  <p>4 confirmed: gkp, dm, Virushuo, Rainux</p>
			  </div>
			  </div>
</div>
<div class="right">
<div class="invitations">
		    <div class="p_right"><img class="text" src="images/translation.gif"  /><a href="#">invitations</a></div>
		    <dl class="bnone">
				<dt><a href="#">Bay Area VC Talk</a></dt>
				<dd><a href="#">10AM Tuesday by gkp</a></dd>
				<dd><button type='button'>Accept</button></dd>
			</dl>
			<dl class="bnone">
				<dt><a href="#">Film Kungfu Panda</a></dt>
				<dd><a href="#">4 Jun at Mountain View by Xin Li</a></dd>
				<dd><button type='button'>Accept</button></dd>
			</dl>
		  </div>
		  <div class="shadow_310"></div>
		    <div class="Recently_updates">
			<div class="p_right"><img class="update" src="images/translation.gif"  /><a href="#">Recently updates</a></div>
			<div class="redate">
			<h5>Dinner in SF</h5>
			<div class="maringbt">
			<p><span>dm</span>: My only missing food in US, dudes ! yummy!^</p>
			</div>
			</div>
			<div class="redate">
			<h5>Ferry famers Market</h5>
			<div class="maringbt">
			 <p><span>6</span> confirmed: <span>Gokeep, DuanMu, Arthur369, Virushuo</span>…</p>
			 <p><span>Virushuo:</span> Lorem ipsum dolor sit amet, ligula suspendi...</p>
			</div>
			</div>
			<div class="redate">
			<h5>0d0f team meeting 1 day</h5>
			<div class="maringbt">
			<p class="clock"><span>11:30PM Saturday</span>, April 9</p>
			<p class="on_line"><span>Online</span></p>
			</div>
			</div>
			<div class="more"><a href="">more…</a></div>
</div>
		<div class="shadow_310"></div>
</div>

		<!--right end -->
	</div>
</div>





<!--/#content-->
<div id="footerBao">

</div><!--/#footerBao-->



</body>
</html>

