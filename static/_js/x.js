ns.arrRvsp   = ['', 'Accepted', 'Declined', 'Interested'];


ns.showDesc = function(editing)
{
    if (!this.expended && $('#x_desc').height() > 200) {
        $('#x_desc_expand').show();
    } else {
        $('#x_desc_expand').hide();
    }
    $('#x_desc_expand').bind('click', this.expendDesc);
};


ns.expendDesc = function() {
    var expanded = !!odof.x.render.expanded,
        act = (expanded ? 'remove' : 'add') + 'Class';
    $('#x_desc')[act]('expanded');
    $(this).find('div.triangle-bottomright')[act]('triangle-topleft');
    $(this).find('>span').html(expanded ? 'More' : 'Less');
    odof.x.render.expanded = !expanded;
};


var print_rsvp = function(myrsvp, username) {
    var str = '';
    switch (myrsvp) {
        case 1:
            str = 'Confirmed by <span class="bold">' + username + '</span>.';
            break;
        case 2:
            str = 'Declined by <span class="bold">' + username + '</span>.';
            break;
        case 3:
            break;
        case 0:
            str = 'Request invitation';
    }
    return str;
};


ns.showRsvp = function()
{
    $('#x_rsvp_typeinfo').hide();
    if (this.editable) {
        if (myrsvp) {
            $('#x_rsvp_area').addClass('x_rsvp_area_status');
            if (myIdentity.id !== crossData.host_id) $('#x_rsvp_typeinfo>span').html(print_rsvp(myrsvp, window['id_name']));
            $('#x_rsvp_status #x_rsvp_status_type').html(this.arrRvsp[myrsvp]);
            $('#x_rsvp_status').data('rsvp_status', myrsvp);
            $('#x_rsvp_msg').show();
            //$('.x_rsvp_button').hide();
            $('#x_rsvp_btns').hide();
            $('#x_rsvp_typeinfo').hide();
        } else {
            $('#x_rsvp_msg').hide();
            $('#x_rsvp_btns').show();
            $('#x_rsvp_typeinfo').hide();
        }
    } else {
        $('#x_rsvp_msg').hide();
        $('#x_rsvp_btns').show().find('.x_rsvp_button').addClass('readonly');
        $('#x_rsvp_typeinfo').hide();
    }
    //$('#x_exfee_users').html(this.showConfirmed(crossExfee));
};


ns.showTime = function()
{
    var strRelativeTime = '',
        strAbsoluteTime = '';
    if (!crossData.begin_at || crossData.begin_at === '0000-00-00 00:00:00') {
        strRelativeTime = 'Sometime';
    } else {
        var crossOffset = crossData.timezone ? odof.comm.func.convertTimezoneToSecond(crossData.timezone) : 0;
        if (crossOffset === window.timeOffset && window.timeValid) {
            strRelativeTime = odof.util.getRelativeTime(crossData.begin_at);
            strAbsoluteTime = odof.util.getHumanDateTime(crossData.begin_at);
            if (!strRelativeTime || !strAbsoluteTime) {
                crossData.begin_at = '';
                strRelativeTime = 'Sometime';
                strAbsoluteTime = '';
            }
        } else {
            var strTime = odof.util.parseHumanDateTime(crossData.origin_begin_at ? crossData.origin_begin_at : '', crossOffset);
            strRelativeTime = odof.util.getRelativeTime(strTime);
            strAbsoluteTime = odof.util.getHumanDateTime(strTime, crossOffset);
            if (!strRelativeTime || !strAbsoluteTime) {
                strRelativeTime = 'Sometime';
                strAbsoluteTime = '';
            } else {
                strAbsoluteTime += ' ' + crossData.timezone;
            }
        }
    }
    $('#x_time_relative').html(strRelativeTime);
    $('#x_time_absolute').html(strAbsoluteTime);
};


ns.showPlace = function() {
    // Show google maps. added by handaoliang
    if (typeof crossData.place.lat !== 'undefined'
     && typeof crossData.place.lng !== 'undefined'
     && crossData.place.lat !== ''
     && crossData.place.lng !== ''
     && parseInt(crossData.place.lat) !== 0
     && parseInt(crossData.place.lng) !== 0) {
        odof.apps.maps.googleMapsContainerID = 'google_maps_cotainer';
        odof.apps.maps.drawGoogleMaps(crossData.place.lat, crossData.place.lng, crossData.place.line1, 280, 140)
    } else {
        $('#google_maps_cotainer').html('').hide();
    }
};


ns.showConversation = function()
{
    var tmpData    = this.sortConversationAndHistory(),
        strMessage = '';
    if (tmpData.length) {
        var self = this,
            g = tmpData.length - 1,
            identity = tmpData[g].by_identity || tmpData[g].identity || {},
            gather = '<li class="cleanup xhistory gather">'
                + 'Gathered' + (identity ? (' by <span class="bold">'
                + identity.name + '</span>.'
                + '<img alt="" width="20px" height="20px" src="'
                + identity.avatar_file_name + '" />') : '.');
        $.each(tmpData, function (i, v) {
            strMessage += g === i ? gather : (v.time ? (v.by_identity ? self.makeHistory(v) : '') : self.makeMessage(v));
        });
        tmpData = null;
    }
    $('#x_conversation_list').html(strMessage);
};


//concat conversation history and sort
ns.sortConversationAndHistory = function () {
    var tmpData = [].concat(crossData.history),
        i = 0, ccl = crossData.conversation.length,
        j = 0, cco = null, cho = null, ccot, chot,
        tl = tmpData.length;

    if (tl) {
      for (; i < ccl; i++) {
          cco = crossData.conversation[i];
          ccot = (+odof.util.getDateFromString(cco.created_at))/1000;

          while ((cho = tmpData[j])) {
              chot = (+odof.util.getDateFromString(cho.time || cho.created_at))/1000;
              if (ccot >= chot) {
                  tmpData.splice(j++, 0, cco);
                  break;
              }
              if (++j === tmpData.length) {
                  tmpData.splice(j, 0, cco);
                  break;
              }
          }
      }
    } else {
      tmpData = crossData.conversation;
    }
    return tmpData;
};


ns.makeHistory = function (o) {
    var str = '', info = '', c = '';
    switch (o.action) {
        case 'description':
            info = 'Description changed to <span class="bold">'
                + o.new_value.substr(0, 10)
                + '</span> by <span class="bold">'
                + o.by_identity.name
                + '</span>';
            break;
        case 'interested':
        case 'confirmed':
        case 'declined':
            info = '<span class="bold">'
                + o.by_identity.name
                + '</span> '
                + o.action;
            c = 'user';
            break;
        case 'place':
            info = 'Place changed to <span class="bold">'
                + o.new_value.line1.substr(0, 10)
                + '</span> by <span class="bold">'
                + o.by_identity.name
                + '</span>';
            c = 'place';
            break;
        case 'begin_at':
            info = 'Time changed to <span class="bold">'
                + odof.util.getRelativeTime(o.new_value.begin_at)
                + '</span> by <span class="bold">'
                + o.by_identity.name
                + '<span>';
            c = 'clock';
            break;
        case 'title':
            info = 'Title changed to <span class="bold">'
                + o.new_value.substr(0, 10)
                + '</span> by <span class="bold">'
                + o.by_identity.name
                + '</span>';
            break;
        case 'addexfee':
            info = '<span class="oblique">'
                + o.to_identity[0].external_identity
                + '</span> is invited by '
                + '<span class="bold">'
                + o.by_identity.name
                + '</span>.';
            c = 'user';
            break;
        default:
            return '';
    }

    str += info;
    str += '<img alt="" width="20px" height="20px" src="' + o.by_identity.avatar_file_name + '" />';
    return '<li class="cleanup xhistory' + (c ? (' ' + c) : '') +'">' + str + '</li>';
};


ns.makeMessage = function(objItem)
{
    return '<li class="cleanup">'
         +     '<img src="' + odof.comm.func.getUserAvatar(
               objItem.identity.avatar_file_name, 80, img_url)
         +     '" class="x_conversation_avatar">'
         +     '<div class="x_conversation_message">'
         +         '<p class="x_conversation_content_area">'
         +             '<span class="x_conversation_identity">'
         +                 objItem.identity.name + ': '
         +             '</span>'
         +             '<span class="x_conversation_content">'
         +                 objItem.content
         +             '</span>'
         +         '</p>'
         +         '<span class="x_conversation_time">'
         +             odof.util.getRelativeTime(objItem.created_at)
         +         '</span>'
         +     '</div>'
         + '</li>';
};


ns.showConfirmed = function (users) {
    var str = '<ul>', i = 0, l = users.length, j = 0;
    for (; i < l; i++) {
        if (users[i].state === 1) {
            ++j;
        }
        if (users[i].state === 1 && users[i].identity_id === crossData.host_id) {
            str += '<li><img alt="" src="' + users[i].avatar_file_name + '" width="20px" height="20px" /></li>'
        }
    }
    str += '<li class="' + (j?'':'hide') + '"><span>' + j + '</span> confirmed.</li>';
    str += '</ul>';
    return str;
};


ns.show = function(editable)
{
    // state: {0: 未知，1：去，2：不去，3：感兴趣}
    var crossHtml = '<div id="x_title_area">'
                  +     '<h2 id="x_title" class="x_title x_title_normal"></h2>'
                  +     '<input id="x_title_edit" class="x_title" style="display:none;">'
                  + '</div>'
                  + '<div id="x_content" class="cleanup">'
                  +     '<div id="x_mainarea">'
                  +         '<div id="x_rsvp_area" class="cleanup">'
                  +             '<div id="x_rsvp_msg">'
                  +                 '<div id="x_rsvp_status">'
                  +                     '<span id="x_rsvp_status_type"></span>'
                  +                     '<span id="x_rsvp_typeinfo"><span></span><a id="x_rsvp_change" href="javascript:void(0);">Change attendance</a></span>'
                  +                 '</div>'
                  +                 '<div id="x_exfee_users"></div>'
                  +             '</div>'
                  +             '<div id="x_rsvp_btns">'
                  +                 '<a id="x_rsvp_yes" href="javascript:void(0);" class="x_rsvp_button">Accept</a>'
                  +                 '<a id="x_rsvp_no" href="javascript:void(0);" class="x_rsvp_button">Decline</a>'
                  +                 '<a id="x_rsvp_maybe" href="javascript:void(0);" class="x_rsvp_button">Interested</a>'
                  +                 '<div id="x_exfee_by_user"></div>'
                  +             '</div>'
                  +         '</div>'
                  + '</div>';

    $('#x_view_content').html(crossHtml);

    if (window['crossExfee']) {
        var my = this.fetchUserByIdentityId(myIdentity.id);
        if (my != null && my.identity_id !== my.by_identity_id) {
            var by_identity = this.fetchUserByIdentityId(my.by_identity_id);
            if (by_identity) {
                $('#x_view_content')
                    .find('#x_exfee_by_user')
                    .html((by_identity.state === 0 ? 'Invitation from ' : 'Set by ') + '<img alt="" src="' + by_identity.avatar_file_name + '" width="20px" height="20px" /><span class="x_conversation_identity" style="padding-left: 2px;">' + by_identity.name + '</span>.');
            }
        }
        $('#x_exfee_users').html(this.showConfirmed(crossExfee));
    }

    if ((this.editable = editable)) {
        $('#x_conversation_my_avatar').attr(
            'src',
            odof.comm.func.getUserAvatar(
                myIdentity.avatar_file_name, 80, img_url
            )
        );
        this.showConversation();
    }
    this.showComponents();
    this.showRsvp();
};


ns.changeConfirmed = function (new_myrsvp, user_id) {
    var old_myrsvp = window['myrsvp'];
    var i = 0;
    if (old_myrsvp === new_myrsvp) return;
    if (old_myrsvp !== new_myrsvp && new_myrsvp === 1) i = 1;
    if (old_myrsvp === 1 && (new_myrsvp === 2 || new_myrsvp === 3)) i = -1;
    var $span = $('#x_exfee_users ul li:last > span');
    var c = ~~$span.html();
    $span.html(c+i);
};


ns.updateDesc = function() {
    var maxChrt = 33,
        maxLine = 9,
        extSpce = 10,
        objDesc = $('#gather_desc');
    crossData.description = odof.util.trim(objDesc.val());
    odof.x.render.showDesc();
    if (crossData.description === '') {
        $('#gather_desc_x').html(defaultDesc);
    } else {
        var arrDesc = crossData.description.split(/\r|\n|\r\n/),
            intLine = arrDesc.length;
        for (var i in arrDesc) {
            intLine += arrDesc[i].length / maxChrt | 0;
        }
        var difHeight = parseInt(objDesc.css('line-height'))
                      * (intLine ? (intLine > maxLine ? maxLine : intLine) : 1)
                      + extSpce - (objDesc.height());
        if (difHeight <= 0) {
            $('#gather_desc_x').html('');
            return;
        }
        objDesc.animate({'height' : '+=' + difHeight}, 100);
        $('#gather_desc_x').animate({'height' : '+=' + difHeight}, 100);
        $('#gather_desc_blank').animate({'height' : '+=' + difHeight}, 100);
        $('#gather_desc_x').html('');
    }
};


ns.afterLogin = function(status) {
    // check status
    if (status.response.success !== 'undefined' && status.response.success) {
        // update my identity
        myIdentity = {
            avatar_file_name  : status.response.user_info.user_avatar_file_name,
            external_identity : status.response.user_info.external_identity,
            identityid        : status.response.user_info.identity_id,
            name              : status.response.user_info.identity_name,
            provider          : status.response.user_info.provider
        };
        odof.exfee.gadget.exfeeChecked = [];
    } else {
        return;
    }
    // update title
    var oldDefaultTitle = defaultTitle;
    defaultTitle = 'Meet ' + status.response.user_info.user_name;
    if (crossData.title === oldDefaultTitle || crossData.title === '') {
        $('#gather_title').val('');
        odof.x.gather.updateTitle(true);
    }
    // update host @todo: set me as host!
    $('#gather_hostby').attr('disabled', true);
    $('#gather_hostby').val(odof.exfee.gadget.displayIdentity(myIdentity));
    // add me as exfee
    var meExfee = odof.util.clone(myIdentity);
    meExfee.host = true;
    meExfee.rsvp = 1;
    odof.exfee.gadget.addExfee('gatherExfee', [meExfee], true);
    // auto submit
    if (odof.x.gather.autoSubmit) {
        odof.x.gather.autoSubmit = false;
        odof.x.gather.submitX();
    }
}


$(document).ready(function() {
    // place
    $('#gather_place').bind('focus blur keyup', function (event) {
        switch (event.type) {
            case 'focus':
                $('#gather_place_x').addClass('gather_focus').removeClass('gather_blur');
                break;
            case 'blur':
                $('#gather_place_x').addClass('gather_blur').removeClass('gather_focus');
                odof.x.gather.updatePlace();
                break;
            case 'keyup':
                odof.x.gather.updatePlace();
                odof.apps.maps.getLocation('gather_place','calendar_map_container', 'create_cross');
        }
    });
    odof.x.gather.updatePlace();
});


ns.editPlace = function(event) {
    if (event) {
        if (!$('#x_place_bubble').data('events')) {
            $('#x_place_bubble').bind('clickoutside', function(event) {
                if (event.target.id === 'revert_x_btn') {
                    return;
                }
                if (event.target.parentNode === $('#x_place_area')[0]) {
                    $('#place_content').bind('keyup', function() {
                        var arrPlace = odof.util.parseLocation($('#place_content').val());
                        if (crossData.place.line1 !== arrPlace[0]) {
                            crossData.place.lat         = '';
                            crossData.place.lng         = '';
                            crossData.place.external_id = '';
                            crossData.place.provider    = '';
                        }
                        crossData.place.line1 = arrPlace[0];
                        crossData.place.line2 = arrPlace[1];
                        odof.x.render.showPlace();
                        //place search
                        odof.apps.maps.getLocation('place_content', 'google_maps_cotainer', 'edit_cross');
                    });
                    $('#place_content').html(
                        crossData.place.line1 !== '' || crossData.place.line2 !== ''
                     ? (crossData.place.line1 + '\r' +  crossData.place.line2) :  ''
                    );
                    $('#x_place_bubble').show();
                    $('#place_content').focus();
                } else {
                    odof.x.edit.savePlace();
                }
            });
            $('#place_content').bind('keydown', function(event) {
                switch (event.keyCode) {
                    case 9:
                        odof.x.edit.savePlace();
                        odof.x.edit.editTitle(true);
                        event.preventDefault();
                }
            });
        }
        if (event === true) {
            $('#x_place_bubble').show();
            $('#place_content').focus();
        }
    } else {
        $('#x_place_area').removeClass('x_editable');
        $('#x_place_area').unbind('click');
        $('#x_place_bubble').hide();
        $('#x_place_bubble').unbind('clickoutside');
        $('#place_content').unbind('keydown');
    }
};


ns.editRsvp = function(event) {
    $('#x_rsvp_area').removeClass('x_rsvp_area_status');
    if (event.target.id === 'x_rsvp_change') {
        $('#x_rsvp_msg').hide();
        //$('#x_rsvp_change').hide();
        $('#x_rsvp_typeinfo').hide();
        //$('.x_rsvp_button').show();
        //$('#x_exfee_by_user').show();
        $('#x_rsvp_btns').show();
        $('#x_exfee_users').show();
        $('#x_rsvp_typeinfo').hide();
    } else {
        switch (event.target.id) {
            case 'x_rsvp_yes':
            case 'x_rsvp_no':
            case 'x_rsvp_maybe':
                var strRsvp = event.target.id.split('_')[2];
                if (token_expired == 'true') {
                    odof.x.edit.rsvpAction = strRsvp;
                    odof.x.edit.setreadonly(function() {
                        $.ajax({
                            type : 'POST',
                            data : {cross_id : odof.x.edit.cross_id,
                                    rsvp     : odof.x.edit.rsvpAction},
                            url  : site_url + '/rsvp/save',
                            dataType : 'json',
                            success  : function(data) {
                                if (data != null && data.response.success === 'true') {
                                    var new_myrsvp = {yes : 1, no : 2, maybe : 3}[data.response.state];
                                    odof.x.render.changeConfirmed(new_myrsvp);
                                    odof.exfee.gadget.changeRsvp(
                                        'xExfeeArea', myIdentity.external_identity,
                                        myrsvp = new_myrsvp
                                    );
                                    odof.x.render.showRsvp();
                                }
                            }
                        });
                    });
                    return;
                }

                $.ajax({
                    type : 'POST',
                    data : {cross_id : cross_id, rsvp : strRsvp, token : token},
                    url  : site_url + '/rsvp/save',
                    dataType : 'json',
                    success : function(data) {
                        if (data != null) {
                            if (data.response.success === 'true') {
                                // by handaoliang {
                                odof.x.edit.setreadonly(clickCallBackFunc);
                                if (data.response.token_expired == '1' && login_type == 'token') {
                                    token_expired = true;
                                }
                                // }
                                var new_myrsvp = {yes : 1, no : 2, maybe : 3}[data.response.state];
                                odof.x.render.changeConfirmed(new_myrsvp);
                                odof.exfee.gadget.changeRsvp(
                                    'xExfeeArea', myIdentity.external_identity,
                                    myrsvp = new_myrsvp
                                );
                                odof.x.render.showRsvp();
                            } else {
                                // by handaoliang {
                                //var args = {"identity":external_identity};
                                //alert("show login dialog.");
                                //$('#pwd_hint').html("<span>Error identity </span>");
                                //$('#login_hint').show();
                                /*
                                var callBackFunc = function(args){
                                    window.location.href=location_uri;
                                }
                                if(show_idbox == "setpassword"){
                                    odof.user.status.doShowResetPwdDialog(null, 'setpwd');
                                    jQuery("#show_identity_box").val(external_identity);
                                }else{
                                    odof.user.status.doShowLoginDialog(null, callBackFunc);
                                }
                                */
                                odof.x.edit.setreadonly();
                                // }
                            }
                        }
                        // $('#rsvp_loading').hide();
                        // $('#rsvp_loading').unbind('ajaxStart ajaxStop');
                    },
                    error: function(data) {
                        // $('#rsvp_loading').hide();
                        // $('#rsvp_loading').unbind('ajaxStart ajaxStop');
                    }
                });
        }
        $('#x_rsvp_msg').show();
        //$('#x_rsvp_change').show();
        $('#x_rsvp_typeinfo').show();
        $('#x_rsvp_btns').hide();
        //$('.x_rsvp_button').hide();
        event.preventDefault();
    }
};


ns.submitExfee = function() {
    if (!odof.x.edit.skipFreeze) {
        odof.x.edit.freeze();
    }
    for (var i in odof.exfee.gadget.exfeeInput['xExfeeArea']) {
        if (parseInt(odof.exfee.gadget.exfeeInput['xExfeeArea'][i].identity_id)
        === parseInt(myIdentity.id)) {
            myrsvp = odof.exfee.gadget.exfeeInput['xExfeeArea'][i].rsvp;
            odof.x.render.showRsvp();
            break;
        }
    }
    if (typeof window.mapRequest !== 'undefined') {
        window.mapRequest.abort();
    }
    $.ajax({
        url  : location.href.split('?').shift() + '/crossEdit',
        type : 'POST',
        dataType : 'json',
        data : {
            title      : crossData.title,
            exfee_only : true,
            exfee      : JSON.stringify(odof.exfee.gadget.getExfees('xExfeeArea'))
        },
        success : function(data) {
            if (odof.exfee.gadget.left) {
                location.href = '/s/profile';
            }
            if (!data) {
                return;
            }
            if (!data.success) {
                switch (data.error) {
                    case 'token_expired':
                        odof.x.edit.setreadonly();
                }
            }
        }
    });
};


ns.freeze = function(xOnly) {
    var lastX = odof.record.last(),
        curX  = {title           : crossData.title,
                 description     : crossData.description,
                 begin_at        : crossData.begin_at,
                 time_type       : crossData.time_type,
                 timezone        : crossData.timezone,
                 origin_begin_at : crossData.origin_begin_at,
                 state           : crossData.state,
                 place           : crossData.place};
    if (xOnly && lastX && JSON.stringify(lastX) === JSON.stringify(curX)) {
        return;
    }
    odof.record.push({cross : curX, exfee : odof.exfee.gadget.exfeeInput['xExfeeArea']});
};


ns.record = function(item) {
    if (!item) {
        return;
    }
    if (odof.x.edit.xBackup) {
        for (var i in item.cross) {
            crossData[i] = odof.util.clone(item.cross[i]);
        }
        odof.x.render.showTitle();
        odof.x.render.showDesc(true);
        odof.x.render.showTime();
        odof.x.render.showPlace();
    }
    crossExfee = odof.util.clone(item.exfee);
    odof.x.edit.skipFreeze = true;
    odof.exfee.gadget.make('xExfeeArea', crossExfee, true, odof.x.edit.submitExfee);
    odof.x.edit.skipFreeze = false;
};
