/**
 * @Description: Exfee Editing Gadget
 * @Author:      Leask Huang <leask@exfe.com>
 * @createDate:  Dec 9, 2011
 * @CopyRights:  http://www.exfe.com
 */


var moduleNameSpace = 'odof.exfee.gadget';
var ns = odof.util.initNameSpace(moduleNameSpace);


(function(ns) {

    ns.exfeeAvailableIdK = 'exfee_available_for_id'

    ns.exfeeAvailableKey = 'exfee_available';
    
    ns.strInputTips      = 'Enter attendees’ information';

    ns.arrStrRsvp        = ['Not responded', 'Accepted', 'Declined', 'Interested'];

    ns.inputed           = {};

    ns.editable          = {};

    ns.exfeeInput        = {};

    ns.exfeeAvailable    = [];

    ns.completimer       = {};

    ns.keyComplete       = {};

    ns.curComplete       = {};

    ns.exfeeChecked      = [];

    ns.exfeeIdentified   = {_fake_host_ : true};

    ns.exfeeSelected     = {};

    ns.completing        = {};

    ns.diffCallback      = {};

    ns.timerBaseInfo     = {};


    ns.make = function(domId, curExfee, curEditable, curDiffCallback, skipInitCallback) {
        var strHtml = '<div id="' + domId + '_exfeegadget_infoarea" '
                    +                 'class="exfeegadget_infoarea">'
                    +     '<div id="' + domId + '_exfeegadget_info_totalarea" '
                    +                     'class="exfeegadget_info_totalarea">'
                    +     '</div>'
                    +     '<div id="' + domId + '_exfeegadget_info_labelarea" '
                    +                     'class="exfeegadget_info_labelarea">'
                    +         'Exfee'
                    +     '</div>'
                    +     '<div id="' + domId + '_exfeegadget_info" '
                    +                     'class="exfeegadget_info">'
                    +         '<span id="' + domId + '_exfeegadget_num_accepted" '
                    +                          'class="exfeegadget_num_accepted">'
                    +         '</span>'
                    +         '<span class="exfeegadget_num_of"> of '
                    +             '<span id="' + domId + '_exfeegadget_num_summary" '
                    +                              'class="exfeegadget_num_summary">'
                    +             '</span>'
                    +         '</span>'
                    +         '<span class="exfeegadget_num_confirmed">confirmed</span>'
                    +     '</div>'
                    + '</div>'
                    + '<div id="' + domId + '_exfeegadget_avatararea" '
                    +                 'class="exfeegadget_avatararea">'
                    +     '<ol></ol>'
                    +     '<button id="' + domId + '_exfeegadget_expandavatarbtn">'
                    + '</div>'
                    +(curEditable
                    ?('<div id="' + domId + '_exfeegadget_inputarea" '
                    +                 'class="exfeegadget_inputarea">'
                    +     '<div    id="' + domId + '_exfeegadget_inputbox_desc" '
                    +                        'class="exfeegadget_inputbox '
                    +                               'exfeegadget_inputbox_desc">'
                    +         this.strInputTips
                    +     '</div>'
                    +     '<input  id="' + domId + '_exfeegadget_inputbox" '
                    +                        'class="exfeegadget_inputbox_main '
                    +                               'exfeegadget_inputbox" type="text">'
                    +     '<button id="' + domId + '_exfeegadget_addbtn" '
                    +                        'class="exfeegadget_addbtn">'
                    +     '</button>'
                    +     '<div id="' + domId + '_exfeegadget_autocomplete" '
                    +                     'class="exfeegadget_autocomplete">'
                    +         '<ol></ol>'
                    +     '</div>'
                    + '</div>') : '')
                    + '<div id="' + domId + '_exfeegadget_listarea" '
                    +                 'class="exfeegadget_listarea">'
                    +     '<ol></ol>'
                    + '</div>';
        this.inputed[domId]       = '';
        this.editable[domId]      = curEditable;
        this.exfeeInput[domId]    = {};
        this.keyComplete[domId]   = '';
        this.curComplete[domId]   = [];
        this.exfeeSelected[domId] = {};
        this.completing[domId]    = false;
        this.diffCallback[domId]  = curDiffCallback;
        this.timerBaseInfo[domId] = {};
        $('#' + domId).html(strHtml);
        if (typeof localStorage !== 'undefined') {
            var curIdentity = myIdentity && typeof myIdentity.external_identity !== 'undefined'
                            ? myIdentity.external_identity.toLowerCase() : null;
            this.exfeeAvailable = localStorage.getItem(this.exfeeAvailableKey);
            if (localStorage.getItem(this.exfeeAvailableIdK) === curIdentity
             && this.exfeeAvailable) {
                try {
                    this.exfeeAvailable = JSON.parse(this.exfeeAvailable);
                } catch (err) {
                    this.exfeeAvailable = [];
                }
            } else {
                this.exfeeAvailable = [];
            }
        }
        // this.cacheExfee(curExfee);
        this.addExfee(domId, curExfee, true, true);
        if (this.diffCallback[domId] && !skipInitCallback) {
            this.diffCallback[domId]();
        }
        $('#' + domId + '_exfeegadget_avatararea > ol > li > .exfee_avatarblock').live(
            'mouseover mouseout', this.eventAvatar
        );
        $('#' + domId + '_exfeegadget_avatararea > ol > li > .exfee_avatarblock > .exfee_avatar').live(
            'click', this.eventAvatar
        );
        $('body').bind('click', this.cleanFloating);
        if (!curEditable) {
            return;
        }
        this.completimer[domId] = setInterval(
            "odof.exfee.gadget.chkInput('" + domId + "')", 50
        );
        $('#' + domId + '_exfeegadget_inputbox').bind(
            'keydown blur', this.eventInputbox
        );
        $('#' + domId + '_exfeegadget_addbtn').bind(
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
    };


    ns.eventInputbox = function(event) {
        var domId = event.target.id.split('_')[0];
        switch (event.type) {
            case 'keydown':
                switch (event.which) {
                    case 9:  // tab
                        odof.exfee.gadget.chkInput(domId, true);
                        break;
                    case 13: // enter
                        var objSelected = $('#' + domId + '_exfeegadget_autocomplete > ol > .autocomplete_selected'),
                            curItem     = objSelected.length ? objSelected.attr('identity') : null;
                        if (odof.exfee.gadget.completing[domId] && curItem) {
                            odof.exfee.gadget.addExfeeFromCache(domId, curItem);
                            odof.exfee.gadget.displayComplete(domId, false);
                            $('#' + domId + '_exfeegadget_inputbox').val('');
                        } else {
                            odof.exfee.gadget.chkInput(domId, true);
                        }
                        break;
                    case 27: // esc
                        if (odof.exfee.gadget.completing[domId]) {
                            odof.exfee.gadget.displayComplete(domId, false);
                        }
                        break;
                    case 38: // up
                    case 40: // down
                        var baseId     = '#' + domId + '_exfeegadget_autocomplete',
                            objCmpBox  = $(baseId),
                            cboxHeight = 207,
                            cellHeight = 51,
                            shrMargin  = 3,
                            curScroll  = objCmpBox.scrollTop();
                        if (!odof.exfee.gadget.completing[domId]) {
                            return;
                        }
                        var objSelected = $(baseId + ' > ol > .autocomplete_selected'),
                            curItem     = null,
                            idxItem     = null,
                            tarIdx      = null,
                            maxIdx      = odof.exfee.gadget.curComplete[domId].length - 1;
                        if (objSelected.length) {
                            curItem = objSelected.attr('identity');
                            for (var i in odof.exfee.gadget.curComplete[domId]) {
                                if (odof.exfee.gadget.curComplete[domId][i] === curItem) {
                                    idxItem = parseInt(i);
                                    break;
                                }
                            }
                        }
                        switch (event.which) {
                            case 38:
                                tarIdx = curItem
                                       ? (idxItem > 0 ? (idxItem - 1) : maxIdx)
                                       : maxIdx;
                                break;
                            case 40:
                                tarIdx = curItem
                                       ? (idxItem < maxIdx ? (idxItem + 1) : 0)
                                       : 0;
                        }
                        odof.exfee.gadget.selectCompleteResult(
                            domId,
                            odof.exfee.gadget.curComplete[domId][tarIdx]
                        );
                        var curCellTop = tarIdx * cellHeight,
                            curScrlTop = curCellTop - curScroll;
                        if (curScrlTop < 0) {
                            objCmpBox.scrollTop(curCellTop);
                        } else if (curScrlTop + cellHeight > cboxHeight) {
                            objCmpBox.scrollTop(curCellTop + cellHeight - cboxHeight + shrMargin);
                        }
                }
                break;
            case 'blur':
                odof.exfee.gadget.displayComplete(domId, false);
        }
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


    ns.displayIdentity = function(identity) {
        switch (identity ? identity.provider : '') {
            case 'email':
                return identity.external_identity;
                break;
            case 'twitter':
                return '@' + identity.external_username + '@twitter';
                break;
            default:
                return '';
        }
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
        for (var k = 0; k < 4; k++) {
            for (var i in exfees) {
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
                objExfee.avatar_file_name = objExfee.avatar_file_name ? objExfee.avatar_file_name : 'default.png';
                objExfee.host = typeof  exfees[i].host  === 'undefined'
                              ? false : exfees[i].host;
                objExfee.rsvp = typeof  exfees[i].rsvp  === 'undefined'
                              ? 0     : exfees[i].rsvp;
                objExfee.rsvp = typeof  exfees[i].state === 'undefined'
                              ? objExfee.rsvp : exfees[i].state;
                if ((k == 0 && objExfee.rsvp !== 1)
                 || (k == 1 && objExfee.rsvp !== 3)
                 || (k == 2 && objExfee.rsvp !== 0)
                 || (k == 3 && objExfee.rsvp !== 2)) {
                    continue;
                }
                var strClassRsvp = this.getClassRsvp(objExfee.rsvp),
                    removable    = this.editable[domId] && !objExfee.host
                                && objExfee.external_identity
                               !== (myIdentity ? myIdentity.external_identity : ''),
                    disIdentity  = this.displayIdentity(objExfee);
                $('#' + domId + '_exfeegadget_avatararea > ol').append(
                    '<li identity="' + objExfee.external_identity + '">'
                  +     '<div class="exfee_avatarblock">'
                  +        (objExfee.host
                  ?         '<div class="exfee_hostmark">H</div>' : '')
                  +         '<img src="' + odof.comm.func.getUserAvatar(
                            objExfee.avatar_file_name, 80, img_url)
                  +         '" class="exfee_avatar">'
                  +         '<div class="exfee_rsvpblock ' + strClassRsvp + '"></div>'
                  +     '</div>'
                  +     '<div class="exfee_baseinfo floating">'
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
                  +        (objExfee.external_identity === '_fake_host_' ? ''
                  :        ('<div class="exfee_extrainfo_mainid_area">'
                  +             '<span class="exfee_identity">'
                  +                 disIdentity
                  +             '</span>'
                  +            (removable
                  ?            ('<button class="exfee_main_identity_remove">'
                  +                 ' ⊖ '
                  +             '</button>') : '')
                  +         '</div>'))
                  +         '<div class="exfee_extrainfo_extraid_area">'
                  +         '</div>'
                  +     '</div>'
                  + '</li>'
                );
                $('#' + domId + '_exfeegadget_listarea > ol').append(
                    '<li identity="' + objExfee.external_identity + '">'
                  +     '<div class="exfee_rsvpblock ' + strClassRsvp + '"></div>'
                  +     '<div class="exfee_baseblock">'
                  +         '<span class="exfee_name">'
                  +             objExfee.name
                  +         '</span>'
                  +        (objExfee.external_identity === '_fake_host_' ? ''
                  :        ('<span class="exfee_identity">'
                  +             objExfee.external_identity
                  +         '</span>'))
                  +     '</div>'
                  +     '<div class="exfee_extrablock">'
                  +         '<img src="' + odof.comm.func.getUserAvatar(
                            objExfee.avatar_file_name, 80, img_url)
                  +         '" class="exfee_avatar">'
                  +     '</div>'
                  + '</li>'
                );
                if (objExfee.provider) {
                    this.exfeeInput[domId][objExfee.external_identity] = objExfee;
                }
            }
        }
        this.chkFakeHost(domId);
        this.updateExfeeSummary(domId);
        if (!noCallback && this.diffCallback[domId]) {
            this.diffCallback[domId]();
        }
        if (!noIdentity) {
            this.ajaxIdentity(exfees);
        }
    };


    ns.delExfee = function(domId, exfees) {
        if (exfees) {
            this.rawDelExfee(domId, exfees);
        } else {
            this.rawDelExfee(domId, this.exfeeSelected[domId]);
            this.exfeeSelected = [];
        }
        this.chkFakeHost(domId);
    };


    ns.rawDelExfee = function(domId, exfees) {
        for (var i in exfees) {
            $('#' + domId + '_exfeegadget_avatararea > ol > li[identity="'
                  + exfees[i] + '"]').remove();
            $('#' + domId + '_exfeegadget_listarea > ol > li[identity="'
                  + exfees[i] + '"]').remove();
            if (typeof this.exfeeInput[domId][exfees[i]] !== 'undefined') {
                delete this.exfeeInput[domId][exfees[i]];
            }
        }
        this.updateExfeeSummary(domId);
        if (this.diffCallback[domId]) {
            this.diffCallback[domId]();
        }
    };


    ns.chkFakeHost = function(domId) {
        if (domId !== 'gatherExfee') {
            return;
        }
        var fakeHost = $('#' + domId + '_exfeegadget_listarea > ol > li[identity="_fake_host_"]').length;
        if (odof.util.count(this.exfeeInput[domId]) && fakeHost) {
            this.rawDelExfee(domId, ['_fake_host_']);
        } else if (!odof.util.count(this.exfeeInput[domId]) && !fakeHost) {
            this.addExfee(
                domId,
                [{external_identity : '_fake_host_', name : 'me', provider : null, host : true, rsvp : 1}]
            );
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
            domId    = domLi.parentNode.parentNode.id.split('_')[0];
        switch (event.type) {
            case 'click':
                switch (odof.exfee.gadget.exfeeInput[domId][identity].rsvp) {
                    case 1:
                        odof.exfee.gadget.changeRsvp(
                            domId,
                            identity,
                            domId === 'gatherExfee' ? 0 : 2
                        );
                        break;
                    case 0:
                    case 2:
                    case 3:
                    default:
                        odof.exfee.gadget.changeRsvp(domId, identity, 1);
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
                var objRemove = $('#' + domItemId + '_exfeegadget_avatararea > ol > li .exfee_main_identity_remove');
                if (objRemove.length) {
                    objRemove.html(' ⊖ ');
                    objRemove.removeClass('ready');
                }
                objItem.children('.exfee_extrainfo').fadeIn(300);
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
        switch (objTarget.html()) {
            case ' ⊖ ':
                objTarget.html('Remove');
                objTarget.addClass('ready');
                break;
            case 'Remove':
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


    ns.chkInput = function(domId, force) {
        var objInput   = $('#' + domId + '_exfeegadget_inputbox'),
            strInput   = objInput.val(),
            arrInput   = strInput.split(/,|;|\r|\n|\t/),
            arrValid   = [],
            arrInValid = [];
        if (odof.exfee.gadget.inputed[domId] === strInput && !force) {
            return;
        } else {
            odof.exfee.gadget.inputed[domId]  =  strInput;
        }
        for (var i in arrInput) {
            arrInput[i] = odof.util.trim(arrInput[i]);
            if (!arrInput[i]) {
                delete arrInput[i];
            }
        }
        for (i in arrInput) {
            var item = odof.util.parseId(arrInput[i]);
            if (item.provider && (parseInt(i) < arrInput.length - 1 || force)) {
                arrValid.push(item);
            } else {
                arrInValid.push(odof.util.trim(arrInput[i]));
            }
        }
        var newInput = arrInValid.join('; ');
        if (newInput !== strInput) {
            objInput.val(newInput);
        }
        $('#' + domId + '_exfeegadget_inputbox_desc').html(
            objInput.val() === '' ? odof.exfee.gadget.strInputTips : ''
        );
        if (arrValid.length) {
            odof.exfee.gadget.addExfee(domId, arrValid);
        }
        odof.exfee.gadget.chkComplete(domId, arrInValid.pop());
    };


    ns.chkComplete = function(domId, key) {
        var arrCatched = [];
        key = odof.util.trim(key).toLowerCase();
        for (var i in this.exfeeAvailable) {
            var curIdentity = this.exfeeAvailable[i].external_identity.toLowerCase();
            if ((this.exfeeAvailable[i].name.toLowerCase().indexOf(key) !== -1
              || curIdentity.indexOf(key) !== -1)
              && curIdentity !== (myIdentity ? myIdentity.external_identity : '')
              && typeof this.exfeeInput[domId][curIdentity] === 'undefined') {
                arrCatched.push(odof.util.clone(this.exfeeAvailable[i]));
            }
        }
        this.showComplete(domId, key, arrCatched);
        this.ajaxComplete(domId, key);
    };


    ns.showComplete = function(domId, key, exfee) {
        var objAutoComplete = $('#' + domId + '_exfeegadget_autocomplete > ol'),
            strItems        = '';
        if (this.keyComplete[domId] !== key) {
            this.curComplete[domId] = [];
            objAutoComplete.html('');
        }
        this.keyComplete[domId] = key;
        if (exfee && exfee.length) {
            for (var i in exfee) {
                var curIdentity = '',
                    shown       = false;
                switch (exfee[i].provider) {
                    case 'email':
                        curIdentity = exfee[i].external_identity.toLowerCase();
                        break;
                    case 'twitter':
                        curIdentity = '@' + exfee[i].external_username.toLowerCase() + '@twitter';
                        break;
                    default:
                        continue;
                }
                for (var j in this.curComplete[domId]) {
                    if (this.curComplete[domId][j] === curIdentity) {
                        shown = true;
                        break;
                    }
                }
                if (shown) {
                    continue;
                }
                this.curComplete[domId].push(curIdentity);
                strItems += '<li identity="' + curIdentity + '" '
                          +     'class="autocomplete_item">'
                          +     '<img src="' + odof.comm.func.getUserAvatar(
                                exfee[i].avatar_file_name, 80, img_url)
                          +     '" class="exfee_avatar">'
                          +     '<span class="exfee_info">'
                          +         '<span class="exfee_name">'
                          +             exfee[i].name
                          +         '</span>'
                          +         '<span class="exfee_identity">'
                          +             this.displayIdentity(exfee[i])
                          +         '</span>'
                          +     '</span>'
                          + '</li>';
            }
        }
        if (strItems) {
            objAutoComplete.append(strItems)
        }
        this.displayComplete(domId, key && this.curComplete[domId].length);
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
                        if (odof.exfee.gadget.exfeeInput[j][curId] === 'undefined') {
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


    ns.cacheExfee = function(exfees, unshift) {
        for (var i in exfees) {
            var objExfee    = odof.util.clone(exfees[i]),
                curIdentity = objExfee.external_identity
                            = objExfee.external_identity.toLowerCase(),
                arrCleanKey = ['rsvp', 'host', 'plus'];
            for (var j in arrCleanKey) {
                if (typeof objExfee[arrCleanKey[j]] !== 'undefined') {
                    delete objExfee[arrCleanKey[j]];
                }
            }
            for (j in this.exfeeAvailable) {
                if (this.exfeeAvailable[j].external_identity.toLowerCase()
                === curIdentity) {
                    this.exfeeAvailable.splice(i, 1);
                }
            }
            if (unshift) {
                this.exfeeAvailable.unshift(objExfee);
            } else {
                this.exfeeAvailable.push(objExfee);
            }
            if (this.exfeeAvailable.length > 250) {
                this.exfeeAvailable.splice(250);
            }
            this.exfeeIdentified[curIdentity] = true;
        }
        if (typeof localStorage !== 'undefined') {
            var curIdentity = myIdentity && typeof myIdentity.external_identity !== 'undefined'
                            ? myIdentity.external_identity.toLowerCase() : '';
            localStorage.setItem(this.exfeeAvailableIdK, curIdentity);
            localStorage.setItem(this.exfeeAvailableKey,
                                 JSON.stringify(this.exfeeAvailable));
        }
    };


    ns.ajaxComplete = function(domId, key) {
        if (!key.length) {
            return;
        }
        for (var i in this.exfeeChecked) {
            if (!key.indexOf(this.exfeeChecked[i])) {
                return;
            }
        }
        this.exfeeChecked.push(key);
        $.ajax({
            type     : 'GET',
            url      : site_url + '/identity/complete',
            data     : {key : key},
            info     : {domId : domId, key : key},
            dataType : 'json',
            success  : function(data) {
                var gotExfee = [];
                for (var i in data) {
                    var curIdentity = {
                            identityid        : data[i].id,
                            name              : data[i].name,
                            avatar_file_name  : data[i].avatar_file_name
                                              ? data[i].avatar_file_name
                                              : 'default.png',
                            bio               : data[i].bio,
                            external_identity : data[i].external_identity,
                            external_username : data[i].external_username,
                            provider          : data[i].provider,
                            userid            : data[i].uid
                        },
                        curId = curIdentity.external_identity.toLowerCase(),
                        exist = false;
                    for (var j in odof.exfee.gadget.exfeeAvailable) {
                        if (odof.exfee.gadget.exfeeAvailable[j]
                                .external_identity.toLowerCase() === curId
                         || curId === myIdentity.external_identity
                         || typeof odof.exfee.gadget.exfeeInput[domId][curIdentity] !== 'undefined') {
                            exist = true;
                            break;
                        }
                    }
                    if (!exist) {
                        gotExfee.push(curIdentity);
                    }
                }
                odof.exfee.gadget.cacheExfee(gotExfee);
                if (this.info.key === odof.exfee.gadget.keyComplete[this.info.domId]) {
                    odof.exfee.gadget.showComplete(this.info.domId, this.info.key, gotExfee);
                }
            }
        });
    };


    ns.displayComplete = function(domId, display) {
        this.completing[domId] = display;
        var objCompleteBox = $('#' + domId + '_exfeegadget_autocomplete');
        if (display) {
            objCompleteBox.slideDown(50);
        } else {
            objCompleteBox.slideUp(50);
        }
    };

})(ns);
