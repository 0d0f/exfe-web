ns.make = function(domId, curExfee, curEditable, curDiffCallback, skipInitCallback) {
    $('#' + domId + '_exfeegadget_avatararea > ol > li > .exfee_avatarblock').live(
        'mouseover mouseout', this.eventAvatar
    );
    $('#' + domId + '_exfeegadget_avatararea > ol > li > .exfee_avatarblock > .exfee_avatar').live(
        'click', this.eventAvatar
    );
    $('body').bind('click', this.cleanFloating);
    $('#' + domId + '_exfeegadget_addbtn').live(
        'keydown click', this.eventAddbutton
    );
    $('#' + domId + '_exfeegadget_autocomplete > ol > li').live(
        'mousemove mousedown', this.eventCompleteItem
    );
    $('#' + domId + '_exfeegadget_avatararea > ol > li .exfee_rsvpblock').live(
        'click', this.eventAvatarRsvp
    );
    $('#' + domId + '_exfeegadget_avatararea > ol > li .exfee_main_identity_remove').live(
        'click', this.removeMainIdentity
    );
    $('#' + domId + '_exfeegadget_avatararea > ol > li .exfee_main_identity_cancel').live(
        'click', this.cancelLeavingCross
    );
    $('#' + domId + '_exfeegadget_avatararea > ol > li.last button').live('click', function () {
        $('#' + domId + '_exfeegadget_listarea').toggle();
    });
};


ns.eventAddbutton = function(event) {
    var domId = event.target.id.split('_')[0];
    if (event.type === 'click'
    || (event.type === 'keydown' && event.which === 13)) {
        odof.exfee.gadget.chkInput(domId, true);
    }
};


ns.eventCompleteItem = function(event) {
    var objEvent = event.target;
    while (!$(objEvent).hasClass('autocomplete_item')) {
        objEvent = objEvent.parentNode;
    }
    var domId    = objEvent.parentNode.parentNode.id.split('_')[0],
        identity = $(objEvent).attr('identity');
    if (!identity) {
        return;
    }
    switch (event.type) {
        case 'mousemove':
            odof.exfee.gadget.selectCompleteResult(domId, identity);
            break;
        case 'mousedown':
            odof.exfee.gadget.addExfeeFromCache(domId, identity);
            odof.exfee.gadget.displayComplete(domId, false);
            $('#' + domId + '_exfeegadget_inputbox').val('');
    }
};


ns.selectCompleteResult = function(domId, identity) {
    var strBaseId = '#' + domId + '_exfeegadget_autocomplete > ol > li',
        className = 'autocomplete_selected';
    $(strBaseId).removeClass(className);
    $(strBaseId + '[identity="' + identity + '"]').addClass(className);
};


ns.getClassRsvp = function(rsvp) {
    return 'exfee_rsvp_'
         + this.arrStrRsvp[rsvp].split(' ').join('').toLowerCase();
};


ns.addExfeeFromCache = function(domId, identity) {
    for (var i in odof.exfee.gadget.exfeeAvailable) {
        if (odof.exfee.gadget.exfeeAvailable[i].external_identity === identity) {
            var objExfee = odof.util.clone(odof.exfee.gadget.exfeeAvailable[i]);
            odof.exfee.gadget.exfeeAvailable.splice(i, 1);
            odof.exfee.gadget.cacheExfee([objExfee], true);
            odof.exfee.gadget.addExfee(domId, [objExfee], true);
            break;
        }
    }
};


ns.addExfee = function(domId, exfees, noIdentity, noCallback) {
    //for (var k = 0; k < 4; k++) {
    // @cfd 为什么注释这里的代码？这里是用来排序显示的，根据rsvp状态优先显示exfee的。 @todo by @leask
        for (var i = 0; i < exfees.length; i++) {
            var objExfee = odof.util.clone(exfees[i]);
            objExfee.external_identity = objExfee.external_identity.toLowerCase();
            if (typeof this.exfeeInput[domId][objExfee.external_identity] !== 'undefined') {
                continue;
            }
            if (!noIdentity) {
                for (var j in this.exfeeAvailable) {
                    if (this.exfeeAvailable[j].external_identity.toLowerCase()
                    === objExfee.external_identity) {
                        objExfee = odof.util.clone(this.exfeeAvailable[j]);
                        break;
                    }
                }
            }
            objExfee.avatar_file_name = objExfee.avatar_file_name
                                      ? objExfee.avatar_file_name : 'default.png';
            objExfee.host             = typeof  exfees[i].host  === 'undefined'
                                      ? false : exfees[i].host;
            objExfee.rsvp             = typeof  exfees[i].rsvp  === 'undefined'
                                      ?(typeof  exfees[i].state === 'undefined'
                                      ? 0 : exfees[i].state) : exfees[i].rsvp;
            /*
            if ((k == 0 && objExfee.rsvp !== 1)
             || (k == 1 && objExfee.rsvp !== 3)
             || (k == 2 && objExfee.rsvp !== 0)
             || (k == 3 && objExfee.rsvp !== 2)) {
                continue;
            }
            */
            var strClassRsvp = this.getClassRsvp(objExfee.rsvp),
                thisIsMe     = objExfee.external_identity === (myIdentity ? myIdentity.external_identity : ''),
                disIdentity  = this.displayIdentity(objExfee);
            /*+     '<div class="exfee_baseinfo floating">'
              +         '<span class="exfee_name exfee_baseinfo_name">'
              +             objExfee.name
              +         '</span>'
              +        (exfees[i].provider
              ?        ('<span class="exfee_identity exfee_baseinfo_identity">'
              +             disIdentity
              +         '</span>') : '')
              +     '</div>'
              +     '<div class="exfee_extrainfo floating">'
              +        (objExfee.host
              ?         '<div class="exfee_hostmark">host</div>' : '')
              +         '<div class="exfee_extrainfo_avatar_area">'
              +             '<img src="' + odof.comm.func.getUserAvatar(
                            objExfee.avatar_file_name, 80, img_url)
              +             '" class="exfee_avatar">'
              +             '<img src="/static/images/exfee_extrainfo_avatar_mask.png" class="exfee_avatar_mask">'
              +         '</div>'
              +         '<div class="exfee_name exfee_extrainfo_name_area">'
              +             objExfee.name
              +         '</div>'
              +         '<div class="exfee_extrainfo_rsvp_area">'
              +             this.arrStrRsvp[objExfee.rsvp]
              +         '</div>'
              +         '<div class="exfee_extrainfo_mainid_area">'
              +             '<span class="exfee_identity">'
              +                 disIdentity
              +             '</span>'
              +            (this.editable[domId] && !objExfee.host
              ?            ('<button class="exfee_main_identity_remove' + (thisIsMe ? ' this_is_me' : '') + '">'
              +                 ' ⊖ '
              +             '</button>'
              +            (thisIsMe
              ?            ('<div class="exfee_main_identity_leave_panel">'
              +                 '<span class="title">Remove yourself?</span><br>'
              +                 'You will <span class="not">NOT</span> be able to access any information in this <span class="x">X</span>. '
              +                 'Confirm leaving?'
              +                 '<div class="exfee_main_identity_leave_panel_button_area">'
              +                     '<button class="exfee_main_identity_cancel">Cancel</button>'
              +                 '</div>'
              +             '</div>') : '')) : '')
              +         '</div>')
              +         '<div class="exfee_extrainfo_extraid_area">'
              +         '</div>'
              +     '</div>'
            */
        //}
    }
    if (!noIdentity) {
        setTimeout(function() {
            ns.ajaxIdentity(exfees);
        }, 1000);
    }
};


ns.delExfee = function(domId, exfees) {
    if (exfees) {
        this.rawDelExfee(domId, exfees);
    } else {
        this.rawDelExfee(domId, this.exfeeSelected[domId]);
        this.exfeeSelected = [];
    }
};


ns.rawDelExfee = function(domId, exfees) {
    var leaving = false;
    for (var i in exfees) {
        $('#' + domId + '_exfeegadget_avatararea > ol > li[identity="'
              + exfees[i] + '"]').remove();
        $('#' + domId + '_exfeegadget_listarea > ol > li[identity="'
              + exfees[i] + '"]').remove();
        if (typeof this.exfeeInput[domId][exfees[i]] !== 'undefined') {
            delete this.exfeeInput[domId][exfees[i]];
            if (exfees[i].toLowerCase() === (myIdentity ? myIdentity.external_identity : '').toLowerCase()) {
                this.left = true;
            }
        }
    }
    this.updateExfeeSummary(domId);
    if (this.diffCallback[domId]) {
        this.diffCallback[domId]();
    }
};


ns.updateExfeeSummary = function(domId) {
    var confirmed = 0;
        total     = 0;
    for (var i in odof.exfee.gadget.exfeeInput[domId]) {
        var curNo = 1
                  + (typeof odof.exfee.gadget.exfeeInput[domId][i].plus !== 'undefined'
                   ? typeof odof.exfee.gadget.exfeeInput[domId][i].plus
                   : 0);
        total += curNo;
        confirmed += odof.exfee.gadget.exfeeInput[domId][i].rsvp === 1 ? 1 : 0;
    }
    $('#' + domId + '_exfeegadget_num_accepted').html(confirmed);
    $('#' + domId + '_exfeegadget_num_summary').html(total);
};


ns.eventAvatarRsvp = function(event) {
    var domLi    = event.target.parentNode.parentNode,
        identity = $(domLi).attr('identity'),
        domId    = domLi.parentNode.parentNode.id.split('_')[0],
        $span = $('#x_exfee_users ul li:last > span');

    switch (event.type) {
        case 'click':
            switch (odof.exfee.gadget.exfeeInput[domId][identity].rsvp) {
                case 1:
                    odof.exfee.gadget.changeRsvp(
                        domId,
                        identity,
                        domId === 'gatherExfee' ? 0 : 2
                    );
                    // 先兼容，后期必须分拆
                    if ($span) {
                        c = ~~$span.html();
                        $span.html(c - 1);
                    }
                    break;
                case 0:
                case 2:
                case 3:
                default:
                    odof.exfee.gadget.changeRsvp(domId, identity, 1);
                    // 先兼容，后期必须分拆
                    if ($span) {
                        c = ~~$span.html();
                        $span.html(c + 1);
                    }
            }
    }
};


ns.cleanFloating = function(event) {
    var objTarget = $(event.target);
    while (!objTarget.hasClass('floating') && objTarget[0].parentNode) {
        objTarget = $(objTarget[0].parentNode);
    }
    if (!objTarget.hasClass('floating')) {
        $('.floating').hide();
    }
};


ns.eventAvatar = function(event) {
    var domTarget = $(event.target)[0];
    do {
        domTarget = domTarget.parentNode;
    } while (domTarget.tagName !== 'LI' && domTarget.parentNode)
    var domItemId = domTarget.parentNode.parentNode.id.split('_')[0],
        objItem   = $(domTarget),
        identity  = objItem.attr('identity');
    switch (event.type) {
        case 'mouseover':
            if (typeof odof.exfee.gadget.timerBaseInfo[domItemId][identity]
            === 'undefined') {
                $('.floating').hide();
                odof.exfee.gadget.timerBaseInfo[domItemId][identity]
              = setTimeout(
                    "odof.exfee.gadget.showBaseInfo('" + domItemId + "', '" + identity + "', true)",
                    500
                );
            }
            break;
        case 'mouseout':
            odof.exfee.gadget.showBaseInfo(domItemId, identity, false);
            break;
        case 'click':
            for (var i in odof.exfee.gadget.timerBaseInfo[domItemId]) {
                clearTimeout(odof.exfee.gadget.timerBaseInfo[domItemId][i]);
            }
            $('.floating').hide();
            var objRemove = $('#' + domItemId + '_exfeegadget_avatararea > ol > li .exfee_main_identity_remove'),
                objLeave  = $('#' + domItemId + '_exfeegadget_avatararea > ol > li .exfee_main_identity_leave_panel');
            if (objRemove.length) {
                objRemove.html(' ⊖ ');
                objRemove.removeClass('ready');
            }
            if (objLeave.length) {
                objLeave.removeClass('ready');
            }
            objItem.children('.exfee_extrainfo').fadeIn(100);
    }
};


ns.cancelLeavingCross = function(event) {
    var domTarget = $(event.target)[0];
    do {
        domTarget = domTarget.parentNode;
    } while (domTarget.tagName !== 'LI' && domTarget.parentNode)
    var domItemId = domTarget.parentNode.parentNode.id.split('_')[0];
    var objRemove = $('#' + domItemId + '_exfeegadget_avatararea > ol > li .exfee_main_identity_remove'),
        objLeave  = $('#' + domItemId + '_exfeegadget_avatararea > ol > li .exfee_main_identity_leave_panel');
    if (objRemove.length) {
        objRemove.html(' ⊖ ');
        objRemove.removeClass('ready');
    }
    if (objLeave.length) {
        objLeave.removeClass('ready');
    }
};


ns.showBaseInfo = function(domId, identity, display) {
    var objBsInfo = $(
            '#' + domId + '_exfeegadget_avatararea > ol > li[identity="'
                + identity + '"] > .exfee_baseinfo'
        );
    clearTimeout(odof.exfee.gadget.timerBaseInfo[domId][identity]);
    delete odof.exfee.gadget.timerBaseInfo[domId][identity];
    if (display) {
        objBsInfo.fadeIn(300);
    } else {
        objBsInfo.hide();
    }
};


ns.changeRsvp = function(domId, identity, rsvp) {
    if (typeof this.exfeeInput[domId][identity] === 'undefined') {
        return;
    }
    this.exfeeInput[domId][identity].rsvp = rsvp;
    var strCatchKey   = ' > ol > li[identity="' + identity + '"] ';
    for (var i in this.arrStrRsvp) {
        var intRsvp = parseInt(i),
            strRsvp = this.getClassRsvp(intRsvp);
        if (intRsvp === rsvp) {
            $('#' + domId + '_exfeegadget_avatararea'
                  + strCatchKey + '.exfee_rsvpblock').addClass(strRsvp);
            $('#' + domId + '_exfeegadget_listarea'
                  + strCatchKey + '.exfee_rsvpblock').addClass(strRsvp);
        } else {
            $('#' + domId + '_exfeegadget_avatararea'
                  + strCatchKey + '.exfee_rsvpblock').removeClass(strRsvp);
            $('#' + domId + '_exfeegadget_listarea'
                  + strCatchKey + '.exfee_rsvpblock').removeClass(strRsvp);
        }
        $('#' + domId + '_exfeegadget_avatararea'
                  + strCatchKey + '.exfee_extrainfo_rsvp_area').html(
            this.arrStrRsvp[rsvp]
        );
    }
    this.updateExfeeSummary(domId);
    if (this.diffCallback[domId]) {
        this.diffCallback[domId]();
    }
};


ns.removeMainIdentity = function(event) {
    var objTarget = $(event.target),
        domItemLi = objTarget[0].parentNode.parentNode.parentNode,
        identity  = $(domItemLi).attr('identity'),
        domId     = domItemLi.parentNode.parentNode.id.split('_')[0];
    if (!objTarget.hasClass('exfee_main_identity_remove')) {
        return;
    }
    switch (objTarget.html()) {
        case ' ⊖ ':
            objTarget.html(identity === (myIdentity ? myIdentity.external_identity : '') ? 'Leave' : 'Remove');
            objTarget.addClass('ready');
            var objLeave = $('#' + domId     + '_exfeegadget_avatararea > ol > li[identity="'
                                 + identity  + '"] .exfee_main_identity_leave_panel');
            if (objLeave.length) {
                objLeave.addClass('ready');
            }
            break;
        case 'Remove':
        case 'Leave':
            odof.exfee.gadget.delExfee(domId, [identity]);
    }
};


ns.getExfees = function(domId) {
    var arrExfees = [];
    for (var i in this.exfeeInput[domId]) {
        if (this.exfeeInput[domId][i].provider) {
            var itemExfee = {
                exfee_identity   : this.exfeeInput[domId][i].external_identity,
                exfee_ext_name   : this.exfeeInput[domId][i].external_username,
                exfee_name       : this.exfeeInput[domId][i].name,
                avatar_file_name : this.exfeeInput[domId][i].avatar_file_name,
                bio              : this.exfeeInput[domId][i].bio,
                confirmed        : this.exfeeInput[domId][i].rsvp,
                identity_type    : this.exfeeInput[domId][i].provider,
                isHost           : this.exfeeInput[domId][i].host
            };
            if (typeof this.exfeeInput[domId][i].identityid !== 'undefined') {
                itemExfee.exfee_id = this.exfeeInput[domId][i].identityid;
            }
            arrExfees.push(itemExfee);
        }
    }
    return arrExfees;
};


ns.ajaxIdentity = function(identities) {
    for (var i in identities) {
        if (typeof this.exfeeIdentified[
                identities[i].external_identity.toLowerCase()
            ] !== 'undefined') {
            identities.splice(i, 1);
        }
    }
    if (!identities.length) {
        return;
    }
    $.ajax({
        type     : 'GET',
        url      : site_url + '/identity/get',
        data     : {identities : JSON.stringify(identities)},
        dataType : 'json',
        success  : function(data) {
            var arrExfee = [];
            for (var i in data.response.identities) {
                var arrCatch = ['avatar_file_name', 'external_identity', 'name',
                                'external_username', 'identityid', 'bio', 'provider'],
                    objExfee = {};
                for (var j in arrCatch) {
                    objExfee[arrCatch[j]] = data.response.identities[i][arrCatch[j]];
                }
                objExfee.identityid = parseInt(objExfee.identityid)
                var curId    = objExfee.external_identity.toLowerCase(),
                    domExfee = $(
                        '.exfeegadget_avatararea > ol > li[identity="' + curId + '"]'
                    );
                for (j in odof.exfee.gadget.exfeeInput) {
                    if (typeof odof.exfee.gadget.exfeeInput[j][curId] === 'undefined' ) {
                        continue;
                    }
                    for (var k in arrCatch) {
                        if (typeof objExfee[arrCatch[k]] === 'undefined') {
                            continue;
                        }
                        odof.exfee.gadget.exfeeInput[j][curId][arrCatch[k]]
                      = objExfee[arrCatch[k]];
                    }
                }
                if (domExfee.length) {
                    domExfee.find('.exfee_avatar').attr(
                        'src', odof.comm.func.getUserAvatar(
                        objExfee.avatar_file_name,
                        80, img_url)
                    );
                    domExfee.find('.exfee_name').html(objExfee.name);
                    domExfee.find('.exfee_identity').html(objExfee.external_identity);
                }
                arrExfee.push(objExfee);
            }
            odof.exfee.gadget.cacheExfee(arrExfee);
        }
    });
};



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
