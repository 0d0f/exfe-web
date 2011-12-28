<?php include 'share/header.php'; ?>
<link rel="stylesheet" type="text/css" href="/static/css/profile.css">
<script src="/static/js/user/UserProfile.js"></script>
<script src="/static/js/user/UploadAvatar.js"></script>
<script src="/static/js/user/FileUploader.js"></script>
<script src="/static/js/libs/jquery.imgareaselect.js"></script>
</head>
<body>
<?php include 'share/nav.php'; ?>
<?php
    $identities = $this->getVar('identities');
    $user       = $this->getVar('user');
    $user['avatar_file_name'] = $user['avatar_file_name'] ?: 'default.png';
?>
<div class="content">
    <div class="edit_user">
        <div id="profile_avatar">
            <img class="big_header" src="<?php echo getUserAvatar($user['avatar_file_name'], 80); ?>" alt="" />
            <button style="display:none;" class="change" id="changeavatar">Change...</button>

            <!-- upload avatar windows
            <div id="upload_avatar_window" class="upload_avatar_dialog" style="display:none;">
                <div class="titles">
                    <p class="l"><a href="javascript:void(0);" id="close_upload_avatar_window_btn">Close Window</a></p>
                    <p class="r">Portrait</p>
                </div>
                <div id="upload_status" style="display:none;"></div>
                <div id="container">
                    <div id="dragdrop_info">Drag and drop<br />your portrait here</div>
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

                        <div id="preview_container">
                            <div class="preview"><div id="preview"></div></div>
                            <span style="position:absolute;left:0px;top:90px;width:100px;text-align:center;">Small Avatar 80x80px</span>
                            <a id="save_btn" href="javascript:void(0);" onclick="odof.user.uploadAvatar.saveImage();"></a>
                        </div>
                    </div>
                </div>
            </div>
            upload avatar windows -->

        </div>
        <div class="u_con">
            <h1 id="profile_name" status="view"><?php echo $user["name"];?></h1>
            <?php foreach($identities as $identity) {
                if( $identity["provider"]!="iOSAPN")
                {
                    $identity=humanIdentity($identity,NULL);
                    if($identity["name"]==$identity["external_identity"]){
                        $identity["name"]="";
                    }
                    if($identity["status"]!=3 )
                    {
                        if($identity["status"]==2){
                            $status = "Verifying";
                        }
                        if($identity["provider"]=="email"){
                            $button="<button type='button' class='sendactiveemail' external_identity='".$identity["external_identity"]."' class='boright'>ReSend</button>";
                        }
                    ?>
                    <p>
                        <img class="s_header" src="<?php echo getUserAvatar($identity["avatar_file_name"], 80); ?>" alt="" />
                        <b><span class="id_name"><?php echo $identity["name"];?></span>
                        <em><?php echo $identity["external_username"]; ?></em>
                        </b>
                        <i><img class="worning" src="/static/images/translation.gif" alt=""/><?php echo $status;?> <?php echo $button?></i>
                    </p>
                    <?php
                    } else {
                    ?>
                    <p><img class="s_header" src="<?php echo getUserAvatar($identity["avatar_file_name"], 80); ?>" alt="" />
                        <b><span class="id_name"><?php echo $identity["name"];?></span>
                        <?php if($identity["provider"]=="email"){ ?>
                        <em><a href="javascript:odof.user.status.doShowChangePwdDialog('<?php echo $identity["external_identity"]; ?>');"><?php echo $identity["external_username"]; ?></a></em>
                        <?php }else{ ?>
                        <em><a href="javascript:odof.user.status.changeOAuthAccountPWD('<?php echo $identity["external_identity"]; ?>');"><?php echo $identity["external_username"]; ?>@<?php echo $identity["provider"]; ?></a></em>
                        <?php } ?>
                         </b>
                    </p>
                    <?php
                    }
                }
            }
            ?>
            <!--p><img class="s_header" src="/static/images/user_header_2.jpg" alt=""/><em>steve@0d0f.com</em><i><img class="worning" src="/static/images/translation.gif" alt=""/>Authorization failed <button type='button' class="boright">Resend</button></i></p-->
        </div>
        <div class="u_num">
            <p><!--57-->&nbsp;</p>
            <span><!--exfes attended-->&nbsp;</span>
            <button id="editprofile">Edit</button>
        </div>
    </div>
    <div class="shadow_840"></div>
    <div id="cross_area">
        <div id="cross_list">
            <div id="xType_upcoming" class="category">
                <div class="category_title">
                    <img src="/static/images/translation.gif" class="category_icon"/>
                    Today & Upcoming
                    <img src="/static/images/translation.gif" class="arrow"/>
                </div>
                <div class="crosses"></div>
                <div class="more_or_less"></div>
            </div>
            <div id="xType_anytime" class="category">
                <div class="category_title">
                    No date
                    <img src="/static/images/translation.gif" class="arrow"/>
                </div>
                <div class="crosses"></div>
                <div class="more_or_less_area">
                    <div class="more_or_less"><a>more...</a></div>
                </div>
            </div>
            <div id="xType_sevenDays" class="category">
                <div class="category_title">
                    Next 7 days
                    <img src="/static/images/translation.gif" class="arrow"/>
                </div>
                <div class="crosses"></div>
                <div class="more_or_less_area">
                    <div class="more_or_less"><a>more...</a></div>
                </div>
            </div>
            <div id="xType_later" class="category">
                <div class="category_title">
                    Later
                    <img src="/static/images/translation.gif" class="arrow"/>
                </div>
                <div class="crosses"></div>
                <div class="more_or_less_area">
                    <div class="more_or_less"><a>more...</a></div>
                </div>
            </div>
            <div id="xType_past" class="category">
                <div class="category_title">
                    Past
                    <img src="/static/images/translation.gif" class="arrow"/>
                </div>
                <div class="crosses"></div>
            </div>
        </div>
        <div id="invitation_n_update">
            <div id="invitations" class="category">
                <div class="category_title">
                    <img src="/static/images/translation.gif" class="category_icon"/>
                    Invitations
                </div>
                <div class="crosses"></div>
            </div>
            <div id="invitations_shadow" class="shadow_310"></div>
            <div id="recently_updates" class="category">
                <div class="category_title">
                    <img src="/static/images/translation.gif" class="category_icon"/>
                    Recently updates
                </div>
                <div class="crosses"></div>
            </div>
            <div id="recently_updates_shadow" class="shadow_310"></div>
        </div>
    </div>
</div>
</body>
</html>
