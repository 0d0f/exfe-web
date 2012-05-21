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


// console.log(data[i]);


            if (confirmed.length) {
                confirmed = confirmed.length + ' <em class="muted">of</em> ' + data[i].exfee.length
                          + ' <em class="muted">confirmed:</em> '   + confirmed.join(', ');
            } else {
                confirmed = '0 <em class="muted">of</em> ' + data[i].exfee.length + ' <em class="muted">confirmed</em>';
            }
            var strCross = '<a id="past_cross_' + data[i]['id'] + '" class="cross_link x_' + data[i]['sort'] + '" href="/!' + data[i]['base62id'] + '">'
                         +     '<div class="cross">'
                         +         '<h5>' + data[i]['title'] + '</h5>'
                         +         '<p>' + this.showXTime(data[i]['begin_at']) + '</p>'
                         +         '<p>' + data[i]['place_line1'] + (data[i]['place_line2'] ? (' <em class="muted">(' + data[i]['place_line2'] + ')</em>') : '') + '</p>'
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
        ns.getCross_dfd = $.ajax({
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
        ns.getInvitation_dfd = $.ajax({
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
                             +     '<p>' + odof.user.profile.showXTime(data[i]['cross']['begin_at']) + ' by ' + data[i]['sender']['name'] + '</p>'
                             + '</div>';
                }
                $('#invitations > .crosses').html(strInvt);
                if (strInvt) {
                    $('#invitations').show();
                    //$('#invitations_shadow').show();
                }
                $('.invitation > button').hide();
            }
        });
    };


    ns.showXTime = function(objTime) {
        var result = '';
        if (!objTime.begin_at || objTime.begin_at === '0000-00-00 00:00:00') {
            result = 'Sometime';
        } else {
            var crossOffset = objTime.timezone ? odof.comm.func.convertTimezoneToSecond(objTime.timezone) : 0;
            if (crossOffset === window.timeOffset && window.timeValid || !objTime.origin_begin_at) {
                if (!(result = odof.util.getHumanDateTime(objTime.begin_at))) {
                    result  = 'Sometime';
                }
            } else {
                var strTime = odof.util.parseHumanDateTime(objTime.origin_begin_at, crossOffset);
                if (!(result = odof.util.getHumanDateTime(strTime, crossOffset))) {
                    result  = 'Sometime';
                } else {
                    result += ' ' + objTime.timezone;
                }
            }
        }
        return result;
    };


    ns.getUpdate = function() {
        ns.getUpdate_dfd = $.ajax({
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
                            strLogX += '<p class="clock"><em>' + odof.user.profile.showXTime(data[i]['change']['begin_at']['new_value']) + '</em></p>';
                        }
                        if (data[i]['change']['place']) {
                            strLogX += '<p class="place"><em>' + data[i]['change']['place']['new_value']['line1'] + '</em></p>';
                        }
                        if (data[i]['change']['title']
                         && data[i]['change']['title']['old_value']) {
                            strLogX += '<p class="title">Title changed'
                                     + (data[i]['change']['title']['old_value'] === '' ? '' : (' from: <em>' + data[i]['change']['title']['old_value'] + '</em>'))
                                     + '</p>';
                        }
                        numChgs++;
                    }
                    if (data[i]['confirmed']) {
                        arrExfee = [];
                        for (j in data[i]['confirmed']) {
                            if(data[i]['confirmed'][j]['to_identity'] != null){
                                if (arrExfee.push( data[i]['confirmed'][j]['to_identity']['name']) === 3) {
                                    break;
                                }
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
                            if (arrExfee.push( data[i]['declined'][j]['to_identity']['name']) === 3) {
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
                            if(data[i]['addexfee'][j]['to_identity'] != null){
                                if (arrExfee.push(data[i]['addexfee'][j]['to_identity']['name']) === 3) {
                                    break;
                                }
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
                                 + data[i]['conversation'][0]['by_identity']['name']+'</em>: '
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
                    //$('#recently_updates_shadow').show();
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

    (function( jQuery ) {

      if ( window.XDomainRequest ) {
        jQuery.ajaxTransport(function( s ) {
          if ( s.crossDomain && s.async ) {
            if ( s.timeout ) {
              s.xdrTimeout = s.timeout;
              delete s.timeout;
            }
            var xdr;
            return {
              send: function( _, complete ) {
                function callback( status, statusText, responses, responseHeaders ) {
                  xdr.onload = xdr.onerror = xdr.ontimeout = jQuery.noop;
                  xdr = undefined;
                  complete( status, statusText, responses, responseHeaders );
                }
                xdr = new XDomainRequest();
                xdr.open( s.type, s.url );
                xdr.onload = function() {
                  callback( 200, "OK", { text: xdr.responseText }, "Content-Type: " + xdr.contentType );
                };
                xdr.onerror = function() {
                  callback( 404, "Not Found" );
                };
                if ( s.xdrTimeout ) {
                  xdr.ontimeout = function() {
                    callback( 0, "timeout" );
                  };
                  xdr.timeout = s.xdrTimeout;
                }
                xdr.send( ( s.hasContent && s.data ) || null );
              },
              abort: function() {
                if ( xdr ) {
                  xdr.onerror = jQuery.noop();
                  xdr.abort();
                }
              }
            };
          }
        });
      }
    })( jQuery );
    ns.showSetPasswordDialog = function () {
      var html = odof.user.identification.createDialogDomCode("set_pwd");
      odof.exlibs.ExDialog.initialize("identification", html);

      $('#submit_set_password').bind('click', function (e) {
          e.preventDefault();
          var new_password = $.trim($('#o_pwd').val());
          var SSID = odof.util.getCookie('PHPSESSID');
          if (new_password) {
            $.ajax({
              type: 'post',
              cache: false,
              data: {
                new_password: new_password
              },
              dataType: 'json',
              url: 'https://api.exfe.com/v2/users/SetPassword?ssid='+SSID,
              xhrFields: { withCredentials: true },
              success: function (data) {
                //data = $.parseJSON(data);
                if (data.meta.code === 200) {
                  odof.exlibs.ExDialog.removeDialog();
                  odof.exlibs.ExDialog.removeCover();
                } else {
                  $("#set_pwd_error_msg").html(data.meta.errorType);
                  $("#set_pwd_error_msg").show();
                }
              }
            });
          }
      });
    };

    ns.showChangePasswordDialog = function(userName, callBackFunc){
        var html = odof.user.identification.createDialogDomCode("change_pwd");
        odof.exlibs.ExDialog.initialize("identification", html);
        //console.log(userName);
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
        var editUserIdentityName = function(e){
            var curElementID = e.currentTarget.id;
            var curIdentityID = curElementID.split('_')[2];
            var curIdentityName = e.currentTarget.innerHTML;
            jQuery("#"+curElementID).unbind("click");
            jQuery("#"+curElementID).hide();
            jQuery("#identity_edit_container_"+curIdentityID).show();
            jQuery("#cur_identity_name_"+curIdentityID).val(curIdentityName);

            jQuery("#submit_editid_"+curIdentityID).bind("click",function(){
                var newIdentityName = jQuery("#cur_identity_name_"+curIdentityID).val();
                var userIdentity = jQuery("#identity_"+curIdentityID).val();
                var identityProvider = jQuery("#identity_provider_"+curIdentityID).val();
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
                            //console.log(JSONData.response.identity_name);
                            jQuery("#"+curElementID).html(JSONData.response.identity_name);
                            jQuery("#"+curElementID).show();
                            jQuery("#identity_edit_container_"+curIdentityID).hide();
                            jQuery("#"+curElementID).bind("click",function(e){
                                editUserIdentityName(e);
                            });
                        }
                    }
                });
            });
        };
        var unEditUserIdentityName = function(){
            jQuery(".identity_ec").hide();
            jQuery(".id_name.provider_email").show();
            jQuery(".id_name.provider_email").css({"cursor":"default"});
            jQuery(".id_name.provider_email").unbind("click");
        };

        jQuery(".id_name.provider_email").css({"cursor":"pointer"});
        jQuery(".id_name.provider_email").bind("click",function(e){
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

            unEditUserIdentityName();
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

    ns.editInput = function () {};

    ns.newbie = function (n) {
      //console.log('Hello newbie!');

      var s = '<div class="newbie_box">'
            + '<div class="con"></div>'
            + '<div class="close"><span>&times;</span></div>'
          + '</div>'
        , c1 = '<p>All your <span class="x">X</span> are listed here with basic information.</p>'
            + '<p><span class="x">X</span> (cross) is<br /> a gathering of people,<br />on purpose or not.</p>'
            + '<p class="detail">Meals, meetings, hang-outs, events, etc. <br />All <span class="x">X</span> are private by default, <br />accessible to only attendees.</p>'
            + '<p>Try <a href="#" id="gather_new_x">Gathering new <span class="x">X</span></a>?</p>'
        , c2 = '<p>Incoming invitations will be listed here.</p>'
        , c3 = '<p>Here you can find recent updates of<br /> all your <span class="x">X</span> (cross).</p>'
        , c4 = '<div class="newbie_gather"><div class="arrow-right"></div>Gather new <span class="x">X</span> here.</div>';

      $('#myexfe').mouseleave(function (e) {
        $('.newbie_gather').hide();
      });

      $(document).on('click', '.newbie_box .close', function (e) {
        if ('localStorage' in window) {
          localStorage.setItem('newbie', 1);
        }
        $(this).parent().fadeOut().remove();
      });

      return function (){
        if (!/\/s\/profile/g.test(window.location.href)) return;
        if (n > 3 || (window['localStorage'] && Boolean(+window['localStorage'].getItem('newbie')))) return false;
        $('#cross_list').append($(s).find('.con').html(c1).end());
        $('#invitations').append($(s).find('.con').html(c2).end()).show();
        $('#recently_updates').append($(s).find('.con').html(c3).end()).show();
        $('#myexfe').append($(c4));
        $('#gather_new_x').click(function (e) {
          //if (!$('.newbie_gather').is(':hidden')) return;
          if (parseInt($('.name').css('top')) === 50) return;
          $('.name').trigger('mouseenter');
          $('.newbie_gather').fadeIn(100)
          if ($(document).scrollTop()) $(document).scrollTop(0);
          return false;
        });
      };
    };

})(ns);


$(document).ready(function() {
    // by Handaoliang
    jQuery('#edit_profile_btn').bind("click", function(event){
        odof.user.profile.editUserProfile(event);
    });
    ////odof.user.profile.editProfileBtnShow();

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
    document.title = 'EXFE - ' + $('#user_name > span.edit-area').html();
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

    $('.more_or_less > a').click(odof.user.profile.getMoreCross);

    window.endlessScrollDoing = false;
    window.endlessScrollAvail = false;
    window.endlessScrollTimer = setInterval('odof.user.profile.endlessScroll()',
                                            500);

    // Todo: 这个必须解耦，后期必须剥离这种依赖，或者使用异步队列
    // 简化命名空间，去除这种模块架构 - @,@!
    var _US = odof.user.status, _UP = odof.user.profile;
    if (_US.checkUserLogin_dfd) {
      _US.checkUserLogin_dfd.then(function (a) {
        var d = a;
        /*
        _UP.getCross();
        _UP.getCross_dfd.then(function () {
          _UP.getInvitation();
          _UP.getInvitation_dfd.then(function () {
            _UP.getUpdate();
            _UP.getUpdate_dfd.then(_UP.newbie(d.cross_num));
          });
        });
        */
        _UP.getCross();
        _UP.getInvitation();
        _UP.getUpdate();
        $.when(_UP.getCross_dfd, _UP.getInvitation_dfd, _UP.getUpdate_dfd).then(_UP.newbie(d.cross_num));

        var $password = $('#set_password_btn');
        $password.data('no_password', d.no_password);
        if (d.no_password) {
            $password.html('Set Password...')
        }
      });
    }
    //odof.user.profile.getCross();
    //odof.user.profile.getInvitation();
    //odof.user.profile.getUpdate();

    var DOC = $(document);

    function mhover(e) {
      $(this).toggleClass('cross_mouseover');
    }
    DOC.on('hover', '#cross_list .cross', mhover);
    DOC.on('hover', '#invitation_n_update .cross_link', mhover);

    $('.invitation > button').live('click', function(e) {
        location.href = '/rsvp/accept?xid=' + e.target.id.split('_')[2];
    });

    // edit_user_area hover
    DOC.delegate('div#edit_user_area', 'mouseenter', function (e) {
      $(this).data('out', 0);
        var $icons = $('span.identity_icon');
        if ($icons.filter('span.identity_remove').size() === 1) {
            $icons = $icons.filter(':not(.identity_remove)');
        }
        $('#set_password_btn').css('display', 'inline-block');
        // TODO: 隐去多身份操作
        //$icons.show();
        //$('#user_cross_info').hide().prev().css('display', 'block');
    });
    DOC.delegate('div#edit_user_area', 'mouseleave', function (e) {
      $(this).data('out', 1);
        $('span.identity_icon').hide();
        $('span.identity_remove_submit:not(hide)').hide();
        //$('#set_password_btn').hide().next().show();
        $('#set_password_btn').hide();
    });

    // change pwd
    DOC.delegate('#set_password_btn', "click", function(event){
        var userName = odof.util.trim(jQuery("#user_name > span.edit-area").html());
        if ($(this).data('no_password')) {
          ns.showSetPasswordDialog();
        } else {
          ns.showChangePasswordDialog(userName);
        }
    });

    // add identity
    DOC.delegate('p#identity_add > span', 'click', function (e) {
        odof.user.status.doShowAddIdentityDialog();
    });
    // delete identity
    DOC.delegate('p.identity_list > span.identity_remove', 'click', function (e) {
        $(this).hide().next().show();
    });

    DOC.delegate('p.identity_list > span.identity_remove_submit', 'click', function (e) {
        var $that = $(this),
            clicked = $(this).data('clicked');
        if (!clicked) {
        $that.data('clicked', 1);
           var identity_id = $that.data('id');
            $.post(site_url + '/s/deleteIdentity', {identity_id: identity_id}, function (data) {
                if (!data.error) {
                    $that.parent().hide().remove();
                    $('span.identity_remove:not(hide)').hide();
                } else {
                    $that.data('clicked', 0);
                }
            }, 'json');
        }
    });

    // edit
    //https://gist.github.com/1539457
    DOC.delegate('div.u_con .edit-area', 'dblclick', function (e) {
        var value = $.trim($(this).html());
        var $input = $('<input type="text" value="' + value + '" />');
        $(this).after($input).hide();
        $input.focus();
        $('#set_password_btn').hide();
    });

    DOC.delegate('h1#user_name > input', 'focusout keydown', function (e) {
        var t = e.type, kc = e.keyCode;
        if (t === 'focusout' || (kc === 9 || (!e.shiftKey && kc === 13))) {
            var value = $.trim($(this).val());
            $(this).hide().prev().html(value).show();
            $(this).remove();
            !$('#edit_user_area').data('out') && $('#set_password_btn').css('display', 'inline-block');
            $.post(site_url + '/s/editUserProfile', {user_name: value}, function (data) {
                if (!data.error) {
                    odof.user.status.checkUserLogin();
                }
            },  'json');
        }
    });

    DOC.delegate('span.id_name > input', 'focusout keydown', function (e) {
        var t = e.type, kc = e.keyCode;
        if (t === 'focusout' || (kc === 9 || (!e.shiftKey && kc === 13))) {
            var identityName = $.trim($(this).val()),
                identityId = $(this).parents('p.identity_list').data('id'),
                userIdentity = $("#identity_" + identityId).val(),
                identityProvider = $("#identity_provider_" + identityId).val();
            $(this).hide().prev().html(identityName).show();
            $(this).remove();
            $.post(site_url + '/s/editUserIdentityName', {
                jrand: Math.round(Math.random()*10000000000),
                identity_name: identityName,
                identity: userIdentity,
                identity_provider: identityProvider
            }, function (data) {
                if (!data.error) {
                }
            }, 'json');
        }
    });

    if ('localStorage' in window) {
      var dismiss = localStorage.getItem('dismiss');
      if (!Number(dismiss)) {
        var $ios = $('#ios-app');
        $ios.show();
        DOC.on('click', '.dismiss > a', function (e) {
          $ios.hide();
          localStorage.setItem('dismiss', 1);
          return false;
        });
      }
    }

    // DOC.delegate('span[title] > span.edit-area + input', 'focusout keydown', function (e) {
    //     var t = e.type, kc = e.keyCode;
    //     if (t === 'focusout' || (kc === 9 || (!e.shiftKey && kc === 13))) {
    //         var value = $.trim($(this).val());
    //     }
    // });
});
