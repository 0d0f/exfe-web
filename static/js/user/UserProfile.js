var moduleNameSpace = 'odof.user.profile';
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    ns.strLsKey = 'profile_cross_fetchArgs';

    ns.updateavatar = function(name) {
        $.ajax({
            type: "GET",
            url: site_url+"/s/GetUserProfile",
            dataType:"json",
            success: function(data){
                if(data.response.user!=null)
                {
                var name=data.response.user.avatar_file_name;
                var Timer=new Date();
                $('#profile_avatar').html("<img class='big_header' src='"+odof.comm.func.getUserAvatar(name, 80, img_url)+"'/>");
                }
            }
        });
    };


    ns.sentActiveEmail=function(external_identity,button) {
        var poststr="identity="+external_identity;
        $.ajax({
        type: "POST",
        data: poststr,
        url: site_url+"/s/sendVerifyingMail",
        dataType:"json",
        success: function(JSONData){
            if(!JSONData.error)
            {
                button.text("sent");
                button.unbind("click");
            }
        }
        });

    };


    ns.makeCross = function(data) {
        var result = {crosses : {}, more : {}};
        for (var i in data) {
            if (!result['crosses'][data[i]['sort']]) {
                result['crosses'][data[i]['sort']] = '';
                result['more'][data[i]['sort']] = 0;
            }
            if (data[i]['more']) {
                result['more'][data[i]['sort']]++;
                continue;
            }
            var confirmed = [];
            for (var j in data[i].exfee) {
                if (parseInt(data[i].exfee[j].rsvp) === 1) {
                    confirmed.push(data[i].exfee[j].name);
                }
            }
            if (confirmed.length) {
                confirmed = confirmed.length + ' of ' + data[i].exfee.length
                          + ' confirmed: '   + confirmed.join(', ');
            } else {
                confirmed = '0 of ' + data[i].exfee.length + ' confirmed';
            }
            var strCross = '<a id="past_cross_' + data[i]['id'] + '" class="cross_link x_' + data[i]['sort'] + '" href="/!' + data[i]['base62id'] + '">'
                         +     '<div class="cross">'
                         +         '<h5>' + data[i]['title'] + '</h5>'
                         +         '<p>' + data[i]['begin_at'] + '</p>'
                         +         '<p>' + data[i]['place_line1'] + (data[i]['place_line2'] ? (' <span>(' + data[i]['place_line2'] + ')</span>') : '') + '</p>'
                         +         '<p>' + confirmed + '</p>'
                         +     '</div>'
                         + '</a>';
            result['crosses'][data[i]['sort']] += strCross;
        }
        return result;
    };


    ns.getCross = function() {
        var fetchArgs = null;
        if (typeof localStorage !== 'undefined') {
            fetchArgs = localStorage.getItem(odof.user.profile.strLsKey);
            try {
                fetchArgs = JSON.parse(fetchArgs);
            } catch (err) {
                fetchArgs = {};
            }
        }
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getcross',
            data     : fetchArgs,
            dataType : 'json',
            success  : function(data) {
                if (data && (data.error || data.length === 0)) {
                    return;
                }
                var crosses   = odof.user.profile.makeCross(data),
                    fetchArgs = null;
                $('#cross_list > .category').hide();
                if (typeof localStorage !== 'undefined') {
                    fetchArgs = localStorage.getItem(odof.user.profile.strLsKey);
                    if (fetchArgs) {
                        try {
                            fetchArgs = JSON.parse(fetchArgs);
                        } catch (err) {
                            fetchArgs = {};
                        }
                    } else {
                        fetchArgs = {};
                    }
                }
                for (var i in crosses['crosses']) {
                    var xCtgrId = '#xType_' + i,
                        xListId = xCtgrId + ' > .crosses';
                    $(xListId).html(crosses['crosses'][i]);
                    if (crosses['crosses'][i]) {
                        $(xCtgrId).show();
                    }
                    if (crosses['more'][i]) {
                        if (i === 'past') {
                            endlessScrollAvail = true;
                        } else {
                            $(xCtgrId + ' > .more_or_less_area > .more_or_less').show();
                        }
                    } else {
                        if (i === 'past') {
                            endlessScrollAvail = false;
                        } else {
                            $(xCtgrId + ' > .more_or_less_area > .more_or_less').hide();
                        }
                    }
                    if (typeof fetchArgs[i + '_folded'] !== 'undefined'
                            && fetchArgs[i + '_folded']) {
                        $(xCtgrId + ' > .category_title > .arrow').removeClass('arrow').addClass('arrow_up');
                        $(xListId).hide();
                        $(xCtgrId + ' > .more_or_less_area').hide();
                    }
                }
            }
        });
    };


    ns.rawGetCross = function(strXType) {
        if (!strXType) {
            return;
        }
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getcross',
            data     : {'upcoming_included'  : strXType === 'upcoming',
                        'upcoming_more'      : true,
                        'anytime_included'   : strXType === 'anytime',
                        'anytime_more'       : true,
                        'sevenDays_included' : strXType === 'sevenDays',
                        'sevenDays_more'     : true,
                        'later_included'     : strXType === 'later',
                        'later_more'         : true,
                        'past_included'      : strXType === 'past',
                        'past_more'          : true,
                        'past_quantity'      : $('#xType_past > .crosses > a').length},
            dataType : 'json',
            success  : function(data) {
                if (data && (data.error || data.length === 0)) {
                    return;
                }
                var crosses   = odof.user.profile.makeCross(data);
                for (var i in crosses['crosses']) {
                    var xCtgrId = '#xType_' + i;
                    switch (i) {
                        case 'upcoming':
                        case 'anytime':
                        case 'sevenDays':
                        case 'later':
                            if (crosses['crosses'][i]) {
                                $(xCtgrId + ' > .crosses').html(crosses['crosses'][i]);
                            }
                            if (crosses['more'][i]) {
                                $(xCtgrId + ' > .more_or_less_area > .more_or_less').show();
                            } else {
                                $(xCtgrId + ' > .more_or_less_area > .more_or_less').hide();
                            }
                            break;
                        case 'past':
                            if (crosses['crosses'][i]) {
                                $(xCtgrId + ' > .crosses').append(crosses['crosses'][i]);
                            }
                            if (crosses['more'][i]) {
                                endlessScrollAvail = true;
                            } else {
                                endlessScrollAvail = false;
                            }
                            endlessScrollDoing = false;
                    }
                }
            }
        });
    };


    ns.getMoreCross = function(event) {
        var objEvent = event.target;
        while (!$(objEvent).hasClass('category')) {
            objEvent = objEvent.parentNode;
        }
        ns.rawGetCross(objEvent.id.split('_')[1]);
    };


    ns.getInvitation = function() {
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getinvitation',
            dataType : 'json',
            success  : function(data) {
                if (data && (data.error || data.length === 0)) {
                    return;
                }
                var strInvt = '';
                $('#invitations').hide();
                $('#invitations_shadow').hide();
                for (var i in data) {
                    strInvt += '<div id="cross_invitation_' + data[i]['base62id'] + '" class="invitation cross">'
                             +     '<button type="button" id="accept_button_' + data[i]['base62id'] + '">Accept</button>'
                             +     '<h5><a href="/!' + data[i]['base62id'] + '">' + data[i]['cross']['title'] + '</a></h5>'
                             +     '<p>' + data[i]['cross']['begin_at'] + ' by ' + data[i]['sender']['name'] + '</p>'
                             + '</div>';
                }
                $('#invitations > .crosses').html(strInvt);
                if (strInvt) {
                    $('#invitations').show();
                    $('#invitations_shadow').show();
                }
                $('.invitation > button').hide();
            }
        });
    };


    ns.getUpdate = function() {
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getupdate',
            dataType : 'json',
            success  : function(data) {
                if (data && (data.error || data.length === 0)) {
                    return;
                }
                var strLogs = '';
                $('#recently_updates').hide();
                $('#recently_updates_shadow').hide();
                for (var i in data) {
                    var j, arrExfee,
                        strLogX = '<a class="cross_link" href="/!' + data[i]['base62id'] + '">'
                                +     '<div class="cross">'
                                +         '<h5>' + data[i]['title'] + '</h5>',
                        numChgs = 0;
                    if (data[i]['change']) {
                        if (data[i]['change']['begin_at']) {
                            strLogX += '<p class="clock"><em>' + data[i]['change']['begin_at']['new_value'] + '</em></p>';
                        }
                        if (data[i]['change']['place']) {
                            strLogX += '<p class="place"><em>' + data[i]['change']['place']['new_value'] + '</em></p>';
                        }
                        if (data[i]['change']['title']
                         && data[i]['change']['title']['old_value']) {
                            strLogX += '<p class="title">Title changed from: <em>' + data[i]['change']['title']['old_value'] + '</em></p>';
                        }
                        numChgs++;
                    }
                    if (data[i]['confirmed']) {
                        arrExfee = [];
                        for (j in data[i]['confirmed']) {
                            if (arrExfee.push(
                                    data[i]['confirmed'][j]['to_name']
                                ) === 3) {
                                break;
                            }
                        }
                        strLogX += '<p class="confirmed"><em>'
                                 + data[i]['confirmed'].length
                                 + '</em> confirmed: <em>'
                                 + arrExfee.join('</em>, <em>') + '</em>'
                                 + (arrExfee.length
                                 < data[i]['confirmed'].length
                                 ? ' and others' : '')
                                 + '.</p>';
                        numChgs++;
                    }
                    if (data[i]['declined']) {
                        arrExfee = [];
                        for (j in data[i]['declined']) {
                            if (arrExfee.push(
                                    data[i]['declined'][j]['to_name']
                                ) === 3) {
                                break;
                            }
                        }
                        strLogX += '<p class="declined"><em>'
                                 + data[i]['declined'].length
                                 + '</em> declined: <em>'
                                 + arrExfee.join('</em>, <em>') + '</em>'
                                 + (arrExfee.length
                                 < data[i]['declined'].length
                                 ? ' and others' : '')
                                 + '.</p>';
                        numChgs++;
                    }
                    if (data[i]['addexfee']) {
                        arrExfee = [];
                        for (j in data[i]['addexfee']) {
                            if (arrExfee.push(
                                    data[i]['addexfee'][j]['to_name']
                                ) === 3) {
                                break;
                            }
                        }
                        strLogX += '<p class="invited"><span>'
                                 + data[i]['addexfee'].length
                                 + '</em> invited: <em>'
                                 + arrExfee.join('</em>, <em>') + '</em>'
                                 + (arrExfee.length
                                 < data[i]['addexfee'].length
                                 ? ' and others' : '')
                                 + '.</p>';
                        numChgs++;
                    }
                    if (data[i]['conversation']) {
                        strLogX += '<p class="conversation"><em>'
                                 + data[i]['conversation'][0]['by_name']+'</em>: '
                                 + data[i]['conversation'][0]['message']+'<br><em>'
                                 + data[i]['conversation'].length
                                 + '</em> new post in conversation.</p>';
                        numChgs++;
                    }
                    strLogX += '</div></a>';
                    strLogs += numChgs ? strLogX : '';
                }
                $('#recently_updates > .crosses').html(strLogs);
                if (strLogs) {
                    $('#recently_updates').show();
                    $('#recently_updates_shadow').show();
                }
            }
        });
    };


    ns.endlessScroll = function() {
        if (!endlessScrollAvail) {
            endlessScrollDoing = false;
            return;
        }
        var objDoc   = $(document);
        if (objDoc.scrollTop() + odof.util.getClientSize()['height'] === objDoc.height()) {
            if (endlessScrollDoing) {
                return;
            }
            endlessScrollDoing = true;
            ns.rawGetCross('past');
        }
    };

    ns.showChangePasswordDialog = function(userName, callBackFunc){
        var html = odof.user.identification.createDialogDomCode("change_pwd");
        odof.exlibs.ExDialog.initialize("identification", html);
        console.log(userName);
        //绑定事件。
        jQuery("#show_username_box").val(userName);
        jQuery("#o_pwd_ic").bind("click",function(){
            odof.comm.func.displayPassword("o_pwd");
        });
        jQuery("#new_pwd_ic").bind("click",function(){
            odof.comm.func.displayPassword("new_pwd");
        });

        jQuery("#change_pwd_discard").unbind("click");
        jQuery("#change_pwd_discard").bind("click", function(){
            odof.exlibs.ExDialog.removeDialog();
            odof.exlibs.ExDialog.removeCover();
        });

        /*
        jQuery("#forgot_password").bind("click", function(){
            ns.showForgotPwdDialog(userIdentity);
        });
        */
        jQuery("#change_pwd_form").submit(function(){
            var userPassword = jQuery("#o_pwd").val();
            var userNewPassword = jQuery("#new_pwd").val();
            if(userPassword == ""){
                jQuery("#change_pwd_error_msg").html("Password cannot be empty.");
                jQuery("#change_pwd_error_msg").show();
                return false;
            }
            if(userNewPassword == ""){
                jQuery("#change_pwd_error_msg").html("New password cannot be empty.");
                jQuery("#change_pwd_error_msg").show();
                return false;
            }
            var postData = {
                jrand:Math.round(Math.random()*10000000000),
                u_pwd:userPassword,
                u_new_pwd:userNewPassword
            };

            jQuery.ajax({
                type: "POST",
                data: postData,
                url: site_url+"/s/changePassword",
                dataType:"json",
                success: function(JSONData){
                    if(JSONData.error){
                        jQuery("#change_pwd_error_msg").html(JSONData.msg);
                        jQuery("#change_pwd_error_msg").show();
                    }else{
                        odof.exlibs.ExDialog.removeDialog();
                        odof.exlibs.ExDialog.removeCover();
                    }
                }
            });
            return false;
        });
    };

    ns.editProfileBtnShow = function(){
        jQuery("#edit_profile_btn").hide();
        jQuery("#edit_user_area").bind("mouseover",function(){
            jQuery("#edit_profile_btn").show();
        });
        jQuery("#edit_user_area").bind("mouseout",function(){
            jQuery("#edit_profile_btn").hide();
        });
    };
    ns.editProfileDoneBtnShow = function(){
        jQuery("#edit_user_area").unbind("mouseover");
        jQuery("#edit_user_area").unbind("mouseout");
        jQuery("#edit_profile_btn").show();
    };

    ns.editUserProfile = function(e) {
        var userName = odof.util.trim(jQuery("#user_name").html());
        jQuery("#user_name").html("<input id='edit_profile_name' value='"+userName+"' />");
        jQuery("#edit_profile_btn").html("Done");
        ns.editProfileDoneBtnShow();

        //edit identity name
        jQuery(".id_name").css({"cursor":"pointer"});
        var editUserIdentityName = function(e){
            var curElementID = e.currentTarget.id;
            var curIdentityName = e.currentTarget.innerHTML;
            jQuery("#"+curElementID).unbind("click");
            jQuery("#"+curElementID).html("<input class='identity_input' id='editid_"+curElementID+"' value='"+curIdentityName+"' />&nbsp;<input type='button' style='cursor:pointer' value='Done' class='identity_submit' id='submit_editid_"+curElementID+"'>");
            jQuery("#submit_editid_"+curElementID).bind("click",function(){
                var newIdentityName = jQuery("#editid_"+curElementID).val();
                var userIdentity = jQuery("#identity_"+curElementID).val();
                var identityProvider = jQuery("#identity_provider_"+curElementID).val();
                var postData = {
                    jrand:Math.round(Math.random()*10000000000),
                    identity_name:newIdentityName,
                    identity:userIdentity,
                    identity_provider:identityProvider
                };
                jQuery.ajax({
                    type: "POST",
                    data: postData,
                    url: site_url+"/s/editUserIdentityName",
                    dataType:"json",
                    success: function(JSONData){
                        if(!JSONData.error){
                            console.log(JSONData.response.identity_name);
                            jQuery("#"+curElementID).html(JSONData.response.identity_name);
                            jQuery("#"+curElementID).bind("click",function(e){
                                editUserIdentityName(e);
                            });
                        }
                    }
                });
            });
        };

        jQuery(".id_name").bind("click",function(e){
            editUserIdentityName(e);
        });

        var discardEditUserProfile = function(userName){
            if(typeof userName == "undefined"){
                userName = jQuery("#edit_profile_name").val();
            }
            jQuery('#user_name').html(userName);
            jQuery("#discard_edit").hide();
            jQuery("#discard_edit").unbind("click");
            jQuery("#edit_profile_btn").html("Edit...");
            jQuery("#edit_profile_btn").unbind("click");
            jQuery('#edit_profile_btn').bind("click", function(event){
                odof.user.profile.editUserProfile(event);
            });
            ns.editProfileBtnShow();
            jQuery("#user_cross_info").show();
            jQuery("#set_password_btn").hide();
            jQuery("#set_password_btn").unbind("click");
        };

        var editUserProfileCallBack = function(JSONData){
            var userName = JSONData.response.user.name;
            discardEditUserProfile(userName);
        };

        jQuery("#discard_edit").show();
        jQuery("#discard_edit").bind("click",function(){
            discardEditUserProfile();
        });

        //显示修改密码按钮。
        jQuery("#user_cross_info").hide();
        jQuery("#set_password_btn").show();
        jQuery("#set_password_btn").unbind("click");
        jQuery("#set_password_btn").bind("click",function(){
            ns.showChangePasswordDialog(userName);
        });
        
        jQuery("#edit_profile_btn").unbind("click");
        jQuery("#edit_profile_btn").bind("click",function(){
            var userName = jQuery("#edit_profile_name").val();
            var postData = {user_name:userName};
            jQuery.ajax({
                type: "POST",
                data: postData,
                url: site_url+"/s/editUserProfile",
                dataType:"json",
                success: function(JSONData){
                    if(JSONData.error){
                        alert(JSONData.msg);
                    }else{
                        editUserProfileCallBack(JSONData);
                        odof.user.status.checkUserLogin();
                    }
                }
            });
        });

        /*
        if($('#user_name').attr("status")=='view')
        {
            $('#user_name').html("<input id='edit_profile_name' value='"+$('#user_name').html()+"'>");
            $('#user_name').attr("status","edit");
            $('#changeavatar').show();
        } else {
            var name_val=$("#edit_profile_name").val();
            $('#user_name').html(name_val);
            odof.user.profile.editUserProfile(name_val);
            $('#user_name').attr("status","view");
            $('#changeavatar').hide();
        }
        */
    };

})(ns);


$(document).ready(function() {
    // by Handaoliang
    jQuery('#edit_profile_btn').bind("click", function(event){
        odof.user.profile.editUserProfile(event);
    });
    odof.user.profile.editProfileBtnShow();

    $('.sendactiveemail').click(function(e) {
        var external_identity=$(this).attr("external_identity");
        odof.user.profile.sentActiveEmail(external_identity,$(this));
    });

    /*
    $('#changeavatar').click(function(e) {
        var AWnd=window.open('/s/uploadavatar','fwId','resizable=yes,scrollbars=yes,width=600,height=600');
        AWnd.focus();
    });
    */

    // by Leask Huang
    document.title = 'EXFE - ' + $('#user_name').html();
    $('.invitation').live('mousemove mouseout', function(event) {
        var objEvent = event.target;
        while (!$(objEvent).hasClass('invitation')) {
            objEvent = objEvent.parentNode;
        }
        var objBtn   = $('#' + objEvent.id + ' > button');
        switch (event.type) {
            case 'mousemove':
                objBtn.show();
                break;
            case 'mouseout':
                objBtn.hide();
        }
    });

    $('.category_title').click(function(event) {
        var objEvent = event.target;
        while (!$(objEvent).hasClass('category')) {
            objEvent = objEvent.parentNode;
        }
        var fetchArgs = null,
            objArrow  = null,
            bolFolded = false,
            strXType  = objEvent.id.split('_')[1];
        if (typeof localStorage !== 'undefined') {
            fetchArgs = localStorage.getItem(odof.user.profile.strLsKey);
            if (fetchArgs) {
                try {
                    fetchArgs = JSON.parse(fetchArgs);
                } catch (err) {
                    fetchArgs = {};
                }
            } else {
                fetchArgs = {};
            }
        }
        if ((objArrow
          = $('#' + objEvent.id + ' > .category_title > .arrow')).length) {
            objArrow.removeClass('arrow').addClass('arrow_up');
            $('#' + objEvent.id + ' > .crosses').hide();
            $('#' + objEvent.id + ' > .more_or_less_area').hide();
            bolFolded = true;
        } else if ((objArrow
          = $('#' + objEvent.id + ' > .category_title > .arrow_up')).length) {
            objArrow.removeClass('arrow_up').addClass('arrow');
            $('#' + objEvent.id + ' > .crosses').show();
            $('#' + objEvent.id + ' > .more_or_less_area').show();
            bolFolded = false;
        }
        if (fetchArgs) {
            fetchArgs[strXType + '_folded'] = bolFolded;
            localStorage.setItem(odof.user.profile.strLsKey,
                                 JSON.stringify(fetchArgs));
        }
    });

    $('.cross').live('mousemove mouseout', function(event) {
        var objEvent = event.target;
        while (!$(objEvent).hasClass('cross')) {
            objEvent = objEvent.parentNode;
        }
        switch (event.type) {
            case 'mousemove':
                $(objEvent).addClass('cross_mouseover');
                break;
            case 'mouseout':
                $(objEvent).removeClass('cross_mouseover');
        }
    });

    $('.invitation > button').live('click', function(e) {
        location.href = '/rsvp/accept?xid=' + e.target.id.split('_')[2];
    });

    $('.more_or_less > a').click(odof.user.profile.getMoreCross);

    window.endlessScrollDoing = false;
    window.endlessScrollAvail = false;
    window.endlessScrollTimer = setInterval('odof.user.profile.endlessScroll()',
                                            500);

    odof.user.profile.getCross();
    odof.user.profile.getInvitation();
    odof.user.profile.getUpdate();
});
