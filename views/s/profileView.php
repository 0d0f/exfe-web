<?php include 'share/header.php'; ?>
<link rel="stylesheet" type="text/css" href="/static/css/profile.css">
<script src="/static/js/user/UserProfile.js"></script>
<script src="/static/js/user/UploadAvatar.js"></script>
<script src="/static/js/user/FileUploader.js"></script>
<script src="/static/js/libs/jquery.imgareaselect.js"></script>
<script src="/static/js/libs/detect_timezone.js"></script>
</head>
<body>
<?php include 'share/nav.php'; ?>
<?php
    $identities = $this->getVar('identities');
    $user = $this->getVar('user');
    $cross_num = $this->getVar('cross_num');
    $user['avatar_file_name'] = $user['avatar_file_name'] ?: 'default.png';
?>
<div class="content">
    <div class="edit_user" id="edit_user_area">
        <div id="profile_avatar">
        <?php if(trim($user['avatar_file_name']) == 'default.png') { ?>
            <a href="javascript:odof.user.uploadAvatar.init();"><img src="/static/images/add_avatar.png" alt="add avatar" /></a>
        <?php } else { ?>
            <a href="<?php echo getUserAvatar($user['avatar_file_name'], 240); ?>" target="_blank"><img class="big_header" src="<?php echo getUserAvatar($user['avatar_file_name'], 80); ?>" alt="" /></a>
            <button class="change" id="changeavatar">Change...</button>
        <?php } ?>
        </div>
        <div class="u_con">
            <h1 id="user_name"><?php echo $user["name"];?></h1>
            <?php foreach($identities as $identity) {
                if( $identity["provider"]!="iOSAPN") {
                    $identity=humanIdentity($identity,NULL);
                    if($identity["name"]==$identity["external_identity"]){ $identity["name"]=""; }
             ?>
                <p class="identity_list">
                <img class="s_header" src="<?php echo getUserAvatar($identity["avatar_file_name"], 80); ?>" alt="" />
                <?php if($identity["provider"] == "email"){ ?>
                <span class="id_name provider_<?php echo $identity["provider"]; ?>" id="identity_name_<?php echo $identity["id"]; ?>"><?php echo $identity["name"]; ?></span>
                <span class="identity_ec" id="identity_edit_container_<?php echo $identity["id"]; ?>" style="display:none"><input class='identity_input' id='cur_identity_name_<?php echo $identity["id"]; ?>' value='' />&nbsp;<input type='button' style='cursor:pointer' value='Done' class='identity_submit' id='submit_editid_<?php echo $identity["id"]; ?>'></span>
                <input type="hidden" id="identity_<?php echo $identity["id"]; ?>" value="<?php echo $identity["external_identity"]; ?>" />
                <input type="hidden" id="identity_provider_<?php echo $identity["id"]; ?>" value="<?php echo $identity["provider"]; ?>" />
                <?php }else{ ?>
                <span class="id_name"><?php echo $identity["name"]; ?></span>
                <?php } ?>
                <?php if($identity["status"] == 2 && $identity["provider"]=="email"){ ?>
                <span style="width:130px; overflow:hidden; text-overflow:ellipsis" title='<?php echo $identity["external_username"]; if($identity["provider"] != "google" && $identity["provider"] != "email"){ ?>@<?php echo $identity["provider"]; } ?>'><?php echo $identity["external_username"]; if($identity["provider"] != "google" && $identity["provider"] != "email"){ ?>@<?php echo $identity["provider"]; } ?></span>
                <?php }else{ ?>
                <span style="width:400px; overflow:hidden; text-overflow:ellipsis" title='<?php echo $identity["external_username"]; if($identity["provider"] != "google" && $identity["provider"] != "email"){ ?>@<?php echo $identity["provider"]; } ?>'><?php echo $identity["external_username"]; if($identity["provider"] != "google" && $identity["provider"] != "email"){ ?>@<?php echo $identity["provider"]; } ?></span>
                <?php } ?>
                <?php
                if($identity["status"] != 3 ) {
                    if($identity["status"]==2 && $identity["provider"]=="email"){
                        $curTime = time();
                        $dateExp = 0;
                        $active_exp_time = (int)$identity["active_exp_time"];
                        if($active_exp_time != 0){
                            $expTime = $active_exp_time+5*86400;
                            $dateExp = ceil(intval($expTime-$curTime)/86400);
                            $dateExp = abs($dateExp)==0 ? 0 : $dateExp;
                        }
                        $status = "Pending verification, {$dateExp} days left.";
                        $button="<button type='button' class='sendactiveemail' external_identity='".$identity["external_identity"]."' class='boright'>Re-verify...</button>";

                        echo '<i><img class="worning" src="/static/images/translation.gif" alt=""/>'.$status.$button.'</i>';
                    }
                }
                ?>
                </p>
           <?php
                }
            }
            ?>
            <!-- p><a href="javascript:odof.user.status.doShowAddIdentityDialog();">Add Identity...</a></p -->
        </div>
        <div class="u_num">
            <button id="set_password_btn" class="ch_pwd" style="display:none;">Change Password</button>
            <div id="user_cross_info">
                <p class="num"><?php echo $cross_num; ?></p>
                <p class="num_info"><span style="color:#0591af">X</span> attended</p>
            </div>
            <div>
                <a href="javascript:;" style="display:none;" id='discard_edit'>Discard</a>
                <button id="edit_profile_btn" style="display:none;">Edit...</button>
            </div>
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
