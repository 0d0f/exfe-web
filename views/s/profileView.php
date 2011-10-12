<?php include "share/header.php"; ?>
<script type="text/javascript" src="/static/js/user/profile.js"></script>
<script type="text/javascript" src="/static/js/user/UploadAvatar.js"></script>
<script type="text/javascript" src="/static/js/user/FileUploader.js"></script>
<script type="text/javascript" src="/static/js/libs/jquery.imgareaselect.js"></script>
</head>
<body>
<?php include "share/nav.php"; ?>
<?php
    $identities = $this->getVar('identities');
    $user       = $this->getVar('user');
    $crosses    = $this->getVar('crosses');
    $newInvt    = $this->getVar('newInvt');
    if ($user["avatar_file_name"]=="")
        $user["avatar_file_name"]="default.png";
?>
<div class="centerbg">
<div class="edit_user">
    <div id="profile_avatar">
        <img class="big_header" src="/eimgs/80_80_<?php echo $user["avatar_file_name"];?>" alt="" />
        <button style="display:none" class="change" id="changeavatar">Change...</button>

        <!-- upload avatar windows -->
        <div id="upload_avatar_window" style="display:none;">
            <div class="titles">
                <p class="l"><a href="javascript:void(0);" id="close_upload_avatar_window_btn">Close Window</a></p>
                <p class="r">Portrait</p>
            </div>
            <div id="container">
                <div id="dragdrop_info">Drag and drop<br />your portrait here</div>
                <!-- a id="selectBtn" href="javascript:void(0);" onclick="document.getElementById('avatar_files').click();"></a>
                <input type="file" id="avatar_files" size="10" name="files[]" style="visibility:hidden;" / -->
                <!-- a id="rotateRightBtn" href="javascript:void(0);" onclick="odof.user.uploadAvatar.rotateImage(event);"></a -->
                <div id="upload_btn_container"></div>
                
                <div id="wrapper">
                	<div id="div_upload_big"></div>
                    <input type="hidden" name="img_src" id="img_src" class="img_src" value="" /> 
                    <input type="hidden" name="height" id="height" class="height" value="0" />
                    <input type="hidden" name="width" id="width" class="width" value="0" />
                    <input type="hidden" id="y1" class="y1" name="y" />
                    <input type="hidden" id="x1" class="x1" name="x" />
                    <input type="hidden" id="y2" class="y2" name="y1" />
                    <input type="hidden" id="x2" class="x2" name="x1" />                         

                    <span id="upload_status" style="display:none;"></span>
                    <div id="preview_container">
                        <div id="preview" class="preview"></div>
                        <span style="position:absolute;left:0px;top:90px;width:100px;text-align:center;">Small Avatar 80x80px</span>
                        <a id="saveBtn" href="javascript:void(0);" onclick="odof.user.uploadAvatar.saveImage();"></a>
                    </div>
                </div>
            </div>
        </div>
        <!-- upload avatar windows -->
    </div>
    <div class="u_con">
        <h1 id="profile_name" status="view"><?php echo $user["name"];?></h1>
        <?php foreach($identities as $identity) {
            $identity=humanIdentity($identity,NULL);
            if($identity["name"]==$identity["external_identity"])  
                $identity["name"]="";
            if($identity["status"]!=3)
            {
                if($identity["status"]==2)
                    $status="Verifying";
                if($identity["provider"]=="email")
                    $button="<button type='button' class='sendactiveemail' external_identity='".$identity["external_identity"]."' class='boright'>ReSend</button>";
            ?>
            <p><img class="s_header" src="/eimgs/80_80_<?php echo $identity["avatar_file_name"];?>" alt="" /><b><span class="name"><?php echo $identity["name"];?></span> <em><?php echo $identity["external_identity"];?></em></b> <i><img class="worning" src="/static/images/translation.gif" alt=""/><?php echo $status;?> <?php echo $button?></i></p>
            <?php 
            }
            else
            {
            ?>
            <p><img class="s_header" src="/eimgs/80_80_<?php echo $identity["avatar_file_name"];?>" alt="" /><b><span class="name"><?php echo $identity["name"];?></span> <em><?php echo $identity["external_identity"];?></em></b> </p>
            <?php 
            } 
        }
        ?>
        <!--p><img class="s_header" src="/static/images/user_header_2.jpg" alt=""/><em>steve@0d0f.com</em><i><img class="worning" src="/static/images/translation.gif" alt=""/>Authorization failed <button type='button' class="boright">Resend</button></i></p-->
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
<?php
    $upcoming  = '';
    $sevenDays = '';
    $later     = '';
    $past      = '';
    foreach ($crosses as $crossI => $crossItem) {
        if ($crossItem['confirmed']) {
            $arrConfirmed = array();
            foreach ($crossItem['confirmed'] as $cfmI => $cfmItem) {
                array_push($arrConfirmed, $cfmItem['name']);
            }
            $strConfirmed = count($crossItem['confirmed']) . " of {$crossItem['numExfee']} confirmed: " . implode(', ', $arrConfirmed);
        } else {
            $strConfirmed = "0 of {$crossItem['numExfee']} confirmed";
        }
        $strCross = '<a class="cross_link x_' . $crossItem['sort'] . '" href="/!' . int_to_base62($crossItem['id']) . '"><div class="coming">'
                  .     "<div class=\"a_tltle\">{$crossItem['title']}</div>"
                  .     '<div class="maringbt">'
                  .         "<p>{$crossItem['begin_at']}</p>"
                  .         "<p>{$crossItem['place_line1']}" . ($crossItem['place_line2'] ? " <span>({$crossItem['place_line2']})</span>" : '') . '</p>'
                  .         "<p>{$strConfirmed}</p>"
                  .     '</div>'
                  . '</div></a>';
        switch ($crossItem['sort']) {
            case 'upcoming':
                $upcoming  = ($upcoming  ?: '<div class="p_right" id="xType_upcoming"><img src="/static/images/translation.gif" class="l_icon"/>Today & Upcoming<img src="images/translation.gif" class="arrow"/></div>') . $strCross;
                break;
            case 'sevenDays':
                $sevenDays = ($sevenDays ?: '<div class="p_right" id="xType_sevenDays">Next 7 days<img src="/static/images/translation.gif" class="arrow"/></div>') . $strCross;
                break;
            case 'later':
                $later     = ($later     ?: '<div class="p_right" id="xType_later">Later<img src="/static/images/translation.gif" class="arrow"/></div>') . $strCross;
                break;
            case 'past':
                $past      = ($past      ?: '<div class="p_right" id="xType_past">Past<img src="/static/images/translation.gif" class="arrow"/></div>') . $strCross;
        }
    }
    echo $upcoming . $sevenDays . $later . $past;
?>
</div>
<div class="right">
<?php
    $strInvt = $newInvt ? '<div class="invitations"><div class="p_right"><img class="text" src="/static/images/translation.gif"/><a href="#">invitations</a></div>' : '';
    if($newInvt)
        foreach ($newInvt as $newInvtI => $newInvtItem) {
            $xid62 = int_to_base62($newInvtItem['cross']['id']);
            $strInvt .= '<dl class="bnone">'
                      .     "<dt><a href=\"/!{$xid62}\">{$newInvtItem['cross']['title']}</a></dt>"
                      .     "<dd>{$newInvtItem['cross']['begin_at']} by {$newInvtItem['sender']['name']}</dd>"
                      .     "<dd><button type=\"button\" id=\"acpbtn_{$xid62}\" class=\"acpbtn\">Accept</button></dd>"
                      . '</dl>';
        }
    $strInvt .= $newInvt ? '</div><div class="shadow_310"></div>' : '';
    echo $strInvt;
?>
<div class="Recently_updates">
<div class="p_right"><img class="update" src="/static/images/translation.gif"/><a href="#">Recently updates</a></div>
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
