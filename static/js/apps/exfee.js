/**
 * @Description: Exfee Editing Gadget
 * @Author:      Leask Huang <leask@exfe.com>
 * @createDate:  Dec 9, 2011
 * @CopyRights:  http://www.exfe.com
 */


var moduleNameSpace = 'odof.exfee.gadget';
var ns = odof.util.initNameSpace(moduleNameSpace);


(function(ns) {

    ns.exfeeAvailableKey = 'exfee_available';

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


    ns.make = function(domId, curExfee, curEditable, curDiffCallback) {
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
                    +     '<input  id="' + domId + '_exfeegadget_inputbox" '
                    +                        'class="exfeegadget_inputbox" type="text">'
                    +     '<button id="' + domId + '_exfeegadget_addbtn">+</button>'
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
        this.curComplete[domId]   = {};
        this.exfeeSelected[domId] = {};
        this.completing[domId]    = false;
        this.diffCallback[domId]  = curDiffCallback;
        this.timerBaseInfo[domId] = {};
        $('#' + domId).html(strHtml);
        if (typeof localStorage !== 'undefined') {
            this.exfeeAvailable = localStorage.getItem(this.exfeeAvailableKey);
            if (this.exfeeAvailable) {
                try {
                    this.exfeeAvailable = JSON.parse(this.exfeeAvailable);
                } catch (err) {
                    this.exfeeAvailable = [];
                }
            } else {
                this.exfeeAvailable = [];
            }
        }
        this.cacheExfee(curExfee);
        this.addExfee(domId, curExfee, true, true);
        if (this.diffCallback[domId]) {
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
            'keydown', this.keydownInputbox
        );
        $('#' + domId + '_exfeegadget_addbtn').bind(
            'keydown click', this.eventAddbutton
        );
        $('#' + domId + '_exfeegadget_autocomplete > ol > li').live(
            'mousemove click', this.eventCompleteItem
        );
        $('#' + domId + '_exfeegadget_avatararea > ol > li .exfee_rsvpblock').live(
            'click', this.eventAvatarRsvp
        );
        $('#' + domId + '_exfeegadget_avatararea > ol > li .exfee_main_identity_remove').live(
            'click', this.removeMainIdentity
        );
    };


    ns.keydownInputbox = function(event) {
        var domId = event.target.id.split('_')[0];
        switch (event.which) {
            case 9:  // tab
            case 13: // enter
                odof.exfee.gadget.chkInput(domId, true);
                break;
            case 40: // down!!!
                if (!odof.exfee.gadget.completing[domId]) {
                    return;
                }
                odof.exfee.gadget.selectCompleteResult(
                    domId,
                    $('#' + domId + '_exfeegadget_autocomplete > ol > li:first').attr('identity')
                );
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
            case 'click':
                for (var i in odof.exfee.gadget.exfeeAvailable) {
                    if (odof.exfee.gadget.exfeeAvailable[i]
                            .external_identity === identity) {
                        odof.exfee.gadget.addExfee(
                            domId, [odof.exfee.gadget.exfeeAvailable[i]], true
                        );
                        break;
                    }
                }
                odof.exfee.gadget.displayComplete(domId, false);
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


    ns.addExfee = function(domId, exfees, noIdentity, noCallback) {
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
            var strClassRsvp = this.getClassRsvp(objExfee.rsvp),
                removable    = this.editable[domId] && !objExfee.host
                            && objExfee.external_identity
                           !== myIdentity.external_identity;
            $('#' + domId + '_exfeegadget_avatararea > ol').append(
                '<li identity="' + objExfee.external_identity + '">'
              +     '<div class="exfee_avatarblock">'
              +         '<img src="' + odof.comm.func.getUserAvatar(
                        objExfee.avatar_file_name, 80, img_url)
              +         '" class="exfee_avatar">'
              +         '<div class="exfee_rsvpblock ' + strClassRsvp + '"></div>'
              +     '</div>'
              +     '<div class="exfee_baseinfo floating">'
              +         '<span class="exfee_baseinfo_name">'
              +             objExfee.name
              +         '</span>'
              +        (exfees[i].provider
              ?        ('<span class="exfee_baseinfo_identity">'
              +             objExfee.external_identity
              +         '</span>') : '')
              +     '</div>'
              +     '<div class="exfee_extrainfo floating">'
              +         '<div class="exfee_extrainfo_avatar_area">'
              +             '<img src="' + odof.comm.func.getUserAvatar(
                            objExfee.avatar_file_name, 80, img_url)
              +             '" class="exfee_avatar">'
              +             '<img src="/static/images/exfee_extrainfo_avatar_mask.png" class="exfee_avatar_mask">'
              +         '</div>'
              +         '<div class="exfee_extrainfo_name_area">'
              +             objExfee.name
              +         '</div>'
              +         '<div class="exfee_extrainfo_rsvp_area">'
              +             this.arrStrRsvp[objExfee.rsvp]
              +         '</div>'
              +         '<div class="exfee_extrainfo_mainid_area">'
              +             objExfee.external_identity
              +            (removable
              ?            ('<button class="exfee_main_identity_remove">'
              +                 ' ⊖ '
              +             '</button>') : '')
              +         '</div>'
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
                    exfee_identity : this.exfeeInput[domId][i].external_identity,
                    exfee_name     : this.exfeeInput[domId][i].name,
                    confirmed      : this.exfeeInput[domId][i].rsvp,
                    identity_type  : this.exfeeInput[domId][i].provider,
                    isHost         : this.exfeeInput[domId][i].host
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
                arrInValid.push(item.external_identity);
            }
        }
        var newInput = arrInValid.join('; ');
        if (newInput !== strInput) {
            objInput.val(newInput);
        }
        if (arrValid.length) {
            odof.exfee.gadget.addExfee(domId, arrValid);
        }
        odof.exfee.gadget.chkComplete(domId, arrInValid.pop());
    };


    ns.chkComplete = function(domId, key) {
        var arrCatched = [];
        key = odof.util.trim(key).toLowerCase();
        for (var i in this.exfeeAvailable) {
            if (this.exfeeAvailable[i].name.toLowerCase().indexOf(key) !== -1
             || this.exfeeAvailable[i].external_identity.toLowerCase().indexOf(key) !== -1) {
                arrCatched.push(odof.util.clone(this.exfeeAvailable[i]));
            }
        }
        this.showComplete(domId, key, arrCatched);
        this.ajaxComplete(domId, key);
    };


    ns.showComplete = function(domId, key, exfee) {
        var baseId          = '#' + domId + '_exfeegadget_autocomplete > ol',
            objAutoComplete = $(baseId),
            strItems        = '';
        if (this.keyComplete[domId] !== key) {
            this.curComplete[domId] = {};
            objAutoComplete.html('');
        }
        this.keyComplete[domId] = key;
        if (exfee && exfee.length) {
            for (var i in exfee) {
                var curIdentity = exfee[i].external_identity.toLowerCase();
                if (typeof this.curComplete[domId][curIdentity] !== 'undefined') {
                    continue;
                }
                this.curComplete[domId][curIdentity] = true;
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
                          +             exfee[i].external_identity
                          +         '</span>'
                          +     '</span>'
                          + '</li>';
            }
        }
        if (strItems) {
            objAutoComplete.append(strItems)
        }
        if (!$(baseId + ' > .autocomplete_selected').length) {
            this.selectCompleteResult(domId, $(baseId + ' > li:first').attr('identity'));
        }
        this.displayComplete(domId, key && odof.util.count(this.curComplete[domId]));
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
                for (var i in data.response.identities) {
                    var arrCatch = ['avatar_file_name', 'external_identity',
                                    'name', 'identityid', 'bio', 'provider'],
                        objExfee = {};
                    for (var j in arrCatch) {
                        objExfee[arrCatch[j]] = data.response.identities[i][arrCatch[j]];
                    }
                    objExfee.identityid = parseInt(objExfee.identityid)
                    var curId    = objExfee.external_identity.toLowerCase(),
                        domExfee = $(
                            '.exfeegadget_listarea > ol > li[identity="' + curId + '"]'
                        );
                    for (j in odof.exfee.gadget.exfeeInput) {
                        if (odof.exfee.gadget.exfeeInput[j][curId] === 'undefined') {
                            continue;
                        }
                        for (var k in arrCatch) {
                            odof.exfee.gadget.exfeeInput[j][curId][arrCatch[k]]
                          = objExfee[arrCatch[k]];
                        }
                    }
                    if (objExfee.length) {
                        objExfee.children('.exfee_avatar').attr(
                            'src', odof.comm.func.getUserAvatar(
                            objExfee.avatar_file_name,
                            80, img_url)
                        );
                        objExfee.children('.exfee_name').html(objExfee.name);
                        objExfee.children('.exfee_identity').html(objExfee.external_identity);
                    }
                }
                odof.exfee.gadget.cacheExfee(objExfee);
            }
        });
    };


    ns.cacheExfee = function(exfees, noIdentity) { // @todo: temp noIdentity
        for (var i in exfees) {
            var objExfee    = odof.util.clone(exfees[i]),
                curIdentity = objExfee.external_identity.toLowerCase(),
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
            this.exfeeAvailable.unshift(objExfee);
            if (noIdentity) {
                continue;
            }
            this.exfeeIdentified[curIdentity] = true;
        }
        if (typeof localStorage !== 'undefined') {
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
                            provider          : 'email',
                            userid            : data[i].uid
                        },
                        curId = curIdentity.external_identity.toLowerCase(),
                        exist = false;
                    for (var j in odof.exfee.gadget.exfeeAvailable) {
                        if (odof.exfee.gadget.exfeeAvailable[j]
                                .external_identity.toLowerCase() === curId) {
                            exist = true;
                            break;
                        }
                    }
                    if (!exist) {
                        gotExfee.push(curIdentity);
                    }
                }
                odof.exfee.gadget.cacheExfee(gotExfee, true);
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
