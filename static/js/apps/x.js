ns.arrRvsp   = ['', 'Accepted', 'Declined', 'Interested'];

ns.editable  = false;

ns.expended  = false;


ns.showDesc = function(editing)
{
    var strDesc = crossData.description === '' && (editing || !odof.x.render.editable)
    ? 'Write some words about this X.'
    : crossData.description,
    converter = new Showdown.converter();
    $('#x_desc').html(converter.makeHtml(strDesc));
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


ns.showPlace = function()
{
    var objPlace = $('#x_place_line1');
    objPlace.html(
        crossData.place.line1
      ? crossData.place.line1 : 'Somewhere'
    );
    $('#x_place_line2').html(
        crossData.place.line2.replace(/\r/g, '<br>')
    );
    if (objPlace.hasClass('x_place_line1_double') && objPlace.height() < 70) {
        objPlace.addClass('x_place_line1_normal').removeClass('x_place_line1_double');
    }
    if (objPlace.hasClass('x_place_line1_normal') && objPlace.height() > 53) {
        objPlace.addClass('x_place_line1_double').removeClass('x_place_line1_normal');
    }

    //Show google maps. added by handaoliang
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
    var strCnvstn = editable
                  ? '<div id="x_conversation_area">'
                  +     '<a id="x_hide_history" href="javascript:void(0);"><span>Show</span> history</a>'
                  +     '<h3 id="x_conversation">Conversation</h3>'
                  +     '<div id="x_conversation_input_area" class="cleanup">'
                  +         '<img id="x_conversation_my_avatar" class="x_conversation_avatar">'
                  +         '<textarea id="x_conversation_input"></textarea>'
                  +     '</div>'
                  +     '<ol id="x_conversation_list"></ol>'
                  + '</div>'
                  : '',
        crossHtml = '<div id="x_title_area">'
                  +     '<h2 id="x_title" class="x_title x_title_normal"></h2>'
                  +     '<input id="x_title_edit" class="x_title" style="display:none;">'
                  + '</div>'
                  + '<div id="x_content" class="cleanup">'
                  +     '<div id="x_mainarea">'
                  +         '<div id="x_desc_area">'
                  +             '<div id="x_desc" class="x_desc"></div>'
                  +             '<textarea id="x_desc_edit" class="x_desc" style="display:none;"></textarea>'
                  +             '<div id="x_desc_expand">'
                  +                 '<div class="triangle-bottomright"><em></em></div>'
                  +                 '<span>More</span>'
                  +             '</div>'
                  +         '</div>'
                  +         '<div id="x_rsvp_area" class="cleanup">'
                  //+             '<span id="x_rsvp_msg">'
                  //+                 'Your RSVP is "<span id="x_rsvp_status"></span>".'
                  //+             '</span>'
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
                  +         strCnvstn
                  +     '</div>'
                  +     '<div id="x_sidebar">'
                  +         '<div id="x_time_area">'
                  +             '<h3   id="x_time_relative"></h3>'
                  +             '<span id="x_time_absolute"></span>'
                  +         '</div>'
                  +         '<div id="x_place_area">'
                  +             '<h3   id="x_place_line1" class="x_place_line1_normal"></h3>'
                  +             '<span id="x_place_line2"></span>'
                  +         '</div>'
                  +         '<div id="google_maps_cotainer"></div>'
                  +         '<div id="xExfeeArea"></div>'
                  +     '</div>'
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

ns.fetchUserByIdentityId = function (identity_id) {
    var user = null;
    $.each(crossExfee, function (i, v) {
        if (v.identity_id === identity_id) {
            user = v;
        }
    });
    return user;
};

ns.showComponents = function()
{
    this.showTitle();
    this.showDesc();
    this.showTime();
    this.showPlace();
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

ns.setXTitleBackground = function () {
    // 设置标题背景图片
    var bkgIMG = new Image();
    if (crossData.background) {
        bkgIMG.src = img_url + '/xbgimage/' + crossData.background + '_web.jpg';
    } else {
        bkgIMG.src = '/static/images/x_background_pure.png';
    }
    bkgIMG.onload = function () {
        $('#x_view').css('background', 'url(' + bkgIMG.src + ') no-repeat 0 -198px');
    }
    bkgIMG.onerror = function () {};
}

$(function () {
    var DOC = $(document);
    DOC.delegate('#x_desc_area', 'mouseenter mouseleave', function (e) {
        var $x_desc_expand = $('#x_desc_expand'),
            isMouseEnter = e.type === 'mouseenter';
        if ($x_desc_expand.is(':hidden')) return;
        $x_desc_expand
            .toggleClass('x_desc_expand_hover')
            .find('>a')[isMouseEnter ? 'show' : 'hide']();
    });

    DOC.delegate('#x_rsvp_msg', 'mouseenter mouseleave', function (e) {
        var timer = $(this).data('xtimer'),
            isMouseEnter = e.type === 'mouseenter';
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }

        if (isMouseEnter) {
            timer = setTimeout(function () {
                $('#x_exfee_users').hide();
                $('#x_rsvp_typeinfo').show();
            }, 500);

            $(this).data('xtimer', timer);
        } else {
            $('#x_exfee_users').show();
            $('#x_rsvp_typeinfo').hide();
        }
    });

    // History
    DOC.delegate('a#x_hide_history', 'click', function (e) {
        e.preventDefault();
        var $span = $(this).find('span'), str = $span.html();
        $span.html(str === 'Show' ? 'Hide' : 'Show');
        $('#x_conversation_list').toggleClass('show_history');
    });
});
