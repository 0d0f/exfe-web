ExfeUtilities = {

    trim : function(string) {
        return string ? string.replace(/^\s+|\s+$/g, '') : '';
    },


    clone : function(variable) {
        switch (Object.prototype.toString.call(variable)) {
            case '[object Object]':       // Object instanceof Object
                var variableNew = {};
                for (var i in variable) {
                    variableNew[i] = this.clone(variable[i]);
                }
                break;
            case '[object Array]':        // Object instanceof Array
                variableNew = [];
                for (i in variable) {
                    variableNew.push(this.clone(variable[i]));
                }
                break;
            default:                      // typeof Object === 'function' || etc
                variableNew = variable;
        }
        return variableNew;
    },


    getTimezone : function() {
        // W3C : 'Fri Jul 06 2012 12:28:23 GMT+0800 (CST)'
        // IE  : 'Fri Jul 6 12:28:23 UTC+0800 2012'
        var rawTimeStr  = Date().toString(),
            numTimezone = rawTimeStr.replace(/^.+([+-]\d{2})(\d{2}).+$/i, '$1:$2'),
            strTimezone = rawTimeStr.replace(/^.*\(([a-z]*)\).*$/i, '$1');
        return numTimezone + (strTimezone === rawTimeStr ? '' : (' ' + strTimezone));
    },


    parseTimestring : function(strTime) {
        var sptTime = strTime.split(/-|\ +|:/),
            arrTime = [],
            fmtFrom = 'YYYY MM DD hh mm ss a',
            rawTime = null,
            efeTime = {
                begin_at : {
                    date_word : '', date : '',
                    time_word : '', time : '',
                    timezone  : this.getTimezone(), id : 0, type : 'EFTime'
                },
                origin : strTime, outputformat : 1, id : 0, type : 'CrossTime'
            },
            db      = function (num) {
                return num < 10 ? ('0' + num.toString()) : num.toString();
            };
        for (var i = 0; i < sptTime.length; i++) {
            if (sptTime[i]) {
                arrTime.push(sptTime[i]);
            }
        }
        switch (arrTime.length) {
            case 0:
            case 1:
            case 2:
                break;
            case 3:
                if (rawTime = moment(arrTime.join(' '), fmtFrom)) {
                    efeTime.begin_at.date = rawTime.format('YYYY-MM-DD');
                    efeTime.outputformat  = 0;
                }
                break;
            case 4:
            case 5:
                arrTime.push('am');
            default:
                if (rawTime = moment(arrTime.join(' '), fmtFrom)) {
                    var objTime = rawTime.toDate();
                    efeTime.begin_at.date =    objTime.getUTCFullYear()   + '-'
                                          + db(objTime.getUTCMonth() + 1) + '-'
                                          + db(objTime.getUTCDate());
                    efeTime.begin_at.time = db(objTime.getUTCHours())     + ':'
                                          + db(objTime.getUTCMinutes())   + ':'
                                          + db(objTime.getUTCSeconds());
                    efeTime.outputformat  = 0;
                }
                break;
        }
        return efeTime;
    },


    parsePlacestring : function(strPlace) {
        var rawPlace = strPlace.split(/\r\n|\r|\n/),
            arrPlace = [];
        for (var i = 0; i < rawPlace.length; i++) {
            if (rawPlace[i]) {
                arrPlace.push(rawPlace[i]);
            }
        }
        return {
            title : arrPlace.shift(), description : arrPlace.join('\r'), lng : 0,
            lat   : 0, provider : '', external_id : 0, id : 0, type : 'Place'
        };
    }

};



ExfeeCache = {

    identities       : [],

    tried_key        : {},

    updated_identity : [],


    init : function() {
        var cached_user_id = Store.get('exfee_cache_user_id');
        this.identities    = Store.get('exfee_cache_identities');
        if (!User || !User.id || !cached_user_id
         || User.id !== cached_user_id || !this.identities) {
            ExfeeCache.identities = [];
        }
    },


    saveCache : function() {
        if (User && User.id) {
            Store.set('exfee_cache_user_id',    User.id);
            Store.set('exfee_cache_identities', this.identities);
        }
    },


    search : function(key) {
        var matchString   = function(key, subject) {
            return subject
                 ? subject.toLowerCase().indexOf(key) !== -1
                 : false;
        };
        var matchIdentity = function(key, identity) {
            return matchString(key, identity.external_id)
                || matchString(key, identity.external_username)
                || matchString(key, identity.name);
        };
        var arrCatched = [];
        key = key.toLowerCase();
        for (var i = 0; i < this.identities.length; i++) {
            if (matchIdentity(key, this.identities[i])
             && !ExfeeWidget.isMyIdentity(this.identities[i])
             && !ExfeeWidget.checkExistence(this.identities[i])) {
                arrCatched.push(ExfeUtilities.clone(this.identities[i]));
            }
        }
        return arrCatched;
    },


    cacheIdentities : function(identities, unshift) {
        identities = ExfeUtilities.clone(identities);
        for (var i = 0; i < identities.length; i++) {
            for (var j = 0; j < this.identities.length; j++) {
                if (ExfeeWidget.compareIdentity(identities[i], this.identities[j])) {
                    this.identities.splice(j, 1);
                }
            }
            if (unshift) {
                this.identities.unshift(identities[i]);
            } else {
                this.identities.push(identities[i]);
            }
            if (this.identities.length > 233) {
                this.identities.splice(233);
            }
            this.updated_identity.push(identities[i]);
        }
        this.saveCache();
    },

};



ExfeeWidget = {

    dom_id           : '',

    rsvp_status      : ['Not responded', 'Accepted', 'Declined', 'Interested'],

    editable         : false,

    complete_timer   : 0,

    complete_key     : {},

    complete_exfee   : {},

    complete_request : 0,

    selected         : '',

    completing       : false,

    callback         : function() {},

    last_inputed     : {},

    base_info_timer  : 0,


    make : function(dom_id, editable, callback) {
        this.dom_id   = dom_id;
        this.editable = editable;
        this.callback = callback;
        $('#' + this.dom_id + ' .input-xlarge').bind(
            'keydown blur', this.inputEvent
        );
        $('#' + this.dom_id + ' .thumbnails > li.identity').live('mouseover mouseout mousedown', function(event) {
            var domEvent = event.target;
            while (domEvent
                && !$(domEvent).hasClass('identity')
                && domEvent.tagName !== 'BODY') {
                domEvent = domEvent.parentNode;
            }
            switch (event.type) {
                case 'mouseover':
                    ExfeeWidget.showTip(domEvent);
                    break;
                case 'mouseout':
                    ExfeePanel.hideTip();
                    break;
                case 'mousedown':
                    ExfeeWidget.showPanel(domEvent);
            }
        });
        this.complete_timer = setInterval(
            "ExfeeWidget.checkInput($('#" + this.dom_id + " .input-xlarge'))",
            50
        );
        return ExfeUtilities.clone(this);
    },


    showAll : function() {
        var intAccepted = 0, intTotal = 0;
        $('#' + this.dom_id + ' .thumbnails').html('');
        for (var i = 0; i < Exfee.invitations.length; i++) {
            var intCell = Exfee.invitations[i].mates + 1;
            this.showOne(Exfee.invitations[i]);
            if (Exfee.invitations[i].rsvp === 'ACCEPTED') {
                intAccepted += intCell;
            }
            intTotal += intCell;
        }
        $('#' + this.dom_id + ' .attended').html(intAccepted);
        $('#' + this.dom_id + ' .total').html('of ' + intTotal);
    },


    showOne : function(invitation) {
        $('#' + this.dom_id + ' .thumbnails').append(
            '<li class="identity" id="' + invitation.identity.id
          +              '" provider="' + invitation.identity.provider.toLowerCase()
          +           '" external_id="' + invitation.identity.external_id.toLowerCase()
          +     '" external_username="' + invitation.identity.external_username.toLowerCase() + '">'
          +     '<span class="avatar">'
          +         '<img src="' + invitation.identity.avatar_filename + '" alt="" width="50" height="50" />'
          +         '<span class="rt">' + (invitation.host ? 'H' : '') + '</span>'
          +         '<span class="lt">' + (invitation.mates ? invitation.mates : '') + '</span>'
          +         '<span class="rb"><i class="icon-time"></i></span>'
          +     '</span>'
          +     '<div class="identity-name">' + invitation.identity.name + '</div>'
          + '</li>'
        );
    },


    showTip : function(target) {
        var objTarget         = $(target),
            objIdentity       = {},
            id                = objTarget.attr('id'),
            provider          = objTarget.attr('provider'),
            external_id       = objTarget.attr('external_id'),
            external_username = objTarget.attr('external_username');
        if (id = ~~id) {
            objIdentity.id                = id;
        }
        if (provider) {
            objIdentity.provider          = provider;
        }
        if (external_id) {
            objIdentity.external_id       = external_id;
        }
        if (external_username) {
            objIdentity.external_username = external_username;
        }
        var objInvitation = this.getInvitationByIdentity(objIdentity);
        ExfeePanel.showTip(objInvitation);
    },


    showPanel : function(target) {
        var objTarget         = $(target),
            objIdentity       = {},
            id                = objTarget.attr('id'),
            provider          = objTarget.attr('provider'),
            external_id       = objTarget.attr('external_id'),
            external_username = objTarget.attr('external_username');
        if (id = ~~id) {
            objIdentity.id                = id;
        }
        if (provider) {
            objIdentity.provider          = provider;
        }
        if (external_id) {
            objIdentity.external_id       = external_id;
        }
        if (external_username) {
            objIdentity.external_username = external_username;
        }
        var objInvitation = this.getInvitationByIdentity(objIdentity);
        ExfeePanel.showPanel(objInvitation);
    },


    compareIdentity : function(identity_a, identity_b) {
        if (identity_a.id && identity_b.id && identity_a.id === identity_b.id) {
            return true;
        }
        if (ExfeUtilities.trim(identity_a.provider).toLowerCase()
        === ExfeUtilities.trim(identity_b.provider).toLowerCase()) {
            if (identity_a.external_id && identity_b.external_id
             && ExfeUtilities.trim(identity_a.external_id).toLowerCase()
            === ExfeUtilities.trim(identity_b.external_id).toLowerCase()) {
                return true;
            }
            if (identity_a.external_username && identity_b.external_username
             && ExfeUtilities.trim(identity_a.external_username).toLowerCase()
            === ExfeUtilities.trim(identity_b.external_username).toLowerCase()) {
                return true;
            }
        }
        return false;
    },


    checkExistence : function(identity) {
        for (var i = 0; i < Exfee.invitations.length; i++) {
            if (this.compareIdentity(Exfee.invitations[i].identity, identity)) {
                return i;
            }
        }
        return false;
    },


    addExfee : function(identity, host, rsvp) {
        if (!this.checkExistence(identity)) {
            Exfee.invitations.push({
                identity    : ExfeUtilities.clone(identity),
                rsvp_status : rsvp ? rsvp : 'NORESPONSE',
                host        : !!host,
                mates       : 0
            });
        }
        this.callback();
    },


    rsvpExfee : function(identity, rsvp) {
        var idx = this.checkExistence(identity);
        if (idx !== false) {
            Exfee.invitations[idx].rsvp_status = rsvp;
        }
        this.callback();
    },


    changeMates: function(identity, mates) {
        var idx = this.checkExistence(identity);
        if (idx !== false) {
            if (mates > 9) {
                mates = 9;
            }
            if (mates < 0) {
                mates = 0;
            }
            Exfee.invitations[idx].mates = mates;
        }
        this.callback();
    },


    rsvpMe : function(rsvp) {
        this.rsvpExfee(User.default_identity, rsvp);
    },


    summary : function() {
        var rtnResult = {accepted : 0, total : 0, accepted_invitations : []};
        for (var i = 0; i < Exfee.invitations.length; i++) {
            if (Exfee.invitations[i].rsvp_status === 'REMOVED'
             || Exfee.invitations[i].rsvp_status === 'NOTIFICATION') {
                continue;
            }
            var num = 1 + Exfee.invitations[i].mates;
            rtnResult.total += num;
            if (Exfee.invitations[i].rsvp_status === 'ACCEPTED') {
                rtnResult.accepted += num;
                rtnResult.accepted_invitations.push(Exfee.invitations[i]);
            }
        }
        return rtnResult;
    },


    getUTF8Length : function(string) {
        var length = 0;
        if (string) {
            for (var i = 0; i < string.length; i++) {
                charCode = string.charCodeAt(i);
                if (charCode < 0x007f) {
                    length += 1;
                } else if ((0x0080 <= charCode) && (charCode <= 0x07ff)) {
                    length += 2;
                } else if ((0x0800 <= charCode) && (charCode <= 0xffff)) {
                    length += 3;
                }
            }
        }
        return length;
    },


    cutLongName : function(string) {
        string = string ? string.replace(/[^0-9a-zA-Z_\u4e00-\u9fa5\ \'\.]+/g, ' ') : '';
        while (this.getUTF8Length(string) > 30) {
            string = string.substring(0, string.length - 1);
        }
        return string;
    },


    parseAttendeeInfo : function(string) {
        string = ExfeUtilities.trim(string);
        var objIdentity = {
            name              : '',
            external_id       : '',
            external_username : '',
            provider          : ''
        }
        if (/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/.test(string)) {
            var iLt = string.indexOf('<'),
                iGt = string.indexOf('>');
            objIdentity.external_id       = ExfeUtilities.trim(string.substring(++iLt, iGt));
            objIdentity.external_username = objIdentity.external_id;
            objIdentity.name              = ExfeUtilities.trim(this.cutLongName(ExfeUtilities.trim(string.substring(0, iLt)).replace(/^"|^'|"$|'$/g, '')));
            objIdentity.provider          = 'email';
        } else if (/^[a-zA-Z0-9!#$%&\'*+\\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/.test(string)) {
            objIdentity.external_id       = string;
            objIdentity.external_username = string;
            objIdentity.name              = ExfeUtilities.trim(this.cutLongName(string.split('@')[0]));
            objIdentity.provider          = 'email';
        } else if (/^@[a-z0-9_]{1,15}$|^@[a-z0-9_]{1,15}@twitter$|^[a-z0-9_]{1,15}@twitter$/i.test(string)) {
            objIdentity.external_id       = '';
            objIdentity.external_username = string.replace(/^@|@twitter$/ig, '');
            objIdentity.name              = objIdentity.external_username;
            objIdentity.provider          = 'twitter';
        } else if (/^[a-z0-9_]{1,15}@facebook$/i.test(string)) {
            objIdentity.external_id       = '';
            objIdentity.external_username = string.replace(/@facebook$/ig, '');
            objIdentity.name              = objIdentity.external_username;
            objIdentity.provider          = 'facebook';
        } else {
            objIdentity = null;
        }
        return objIdentity;
    },


    checkComplete : function(objInput, key) {
        var objPanel = $(objInput[0].parentNode.parentNode).find('.autocomplete');
        this.showCompleteItems(objPanel, key, key ? ExfeeCache.search(key) : []);
        this.ajaxComplete(objPanel, key);
    },


    displayIdentity : function(identity) {
        switch (identity ? identity.provider : '') {
            case 'email':
                return identity.external_id;
            case 'twitter':
                return '@' + identity.external_username + '@twitter';
            case 'facebook':
                return identity.external_username + '@facebook';
            default:
                return '';
        }
    },


    displayCompletePanel : function(objPanel, display) {
        if (display) {
            objPanel.slideDown(50);
        } else {
            objPanel.slideUp(50);
        }
    },


    showCompleteItems : function(objPanel, key, identities) {
        // @todo: 使用 typeahead 替代这段代码
        var exfeeWidgetId    = objPanel[0].parentNode.id,
            objCompleteList  = $(objPanel).find('ol'),
            strCompleteItems = '';
        key = key ? key.toLowerCase() : '';
        if (ExfeeWidget.complete_key[exfeeWidgetId] !== key) {
            ExfeeWidget.complete_exfee[exfeeWidgetId] = [];
            objCompleteList.html('');
        }
        ExfeeWidget.complete_key[exfeeWidgetId] = key;
        for (var i = 0; i < identities.length; i++) {
            var shown = false;
            for (var j = 0; j < ExfeeWidget.complete_exfee[exfeeWidgetId].length; j++) {
                if (this.compareIdentity(ExfeeWidget.complete_exfee[exfeeWidgetId][j], identities[i])) {
                    shown = true;
                    break;
                }
            }
            if (shown) {
                continue;
            }
            var index = ExfeeWidget.complete_exfee[exfeeWidgetId].push(ExfeUtilities.clone(identities[i]));
            strCompleteItems += '<li complete_index="' + index + '">'
                              +     '<img src="' + identities[i].avatar_filename + '" class="exfee-avatar">'
                              +     '<span class="exfee_info">'
                              +         '<span class="exfee_name">'
                              +             identities[i].name
                              +         '</span>'
                              +         '<span class="exfee_identity">'
                              +             this.displayIdentity(identities[i])
                              +         '</span>'
                              +     '</span>'
                              + '</li>';
        }
        objCompleteList.append(strCompleteItems);
        this.displayCompletePanel(
            objPanel,
            key && ExfeeWidget.complete_exfee[exfeeWidgetId].length
        );
    },


    isMyIdentity : function(identity) {
        return identity.connected_user_id && User && User.id
            && identity.connected_user_id === User.id;
    },


    getInvitationByIdentity : function(identity) {
        for (var i = 0; i < Exfee.invitations.length; i++) {
            if (this.compareIdentity(Exfee.invitations[i].identity, identity)) {
                return Exfee.invitations[i];
            }
        }
        return null;
    },


    getMyInvitation : function() {
        return User
             ? this.getInvitationByIdentity(User.default_identity)
             : null;
    },


    ajaxComplete : function(objPanel, key) {
        if (!User || !key || typeof ExfeeCache.tried_key[key] !== 'undefined') {
            return;
        }
        if (this.complete_request) {
            this.complete_request.abort();
        }
        this.complete_request = Api.request(
            'complete',
            {type : 'get', data : {key : key}},
            function(data) {
                var caughtIdentities = [];
                for (var i = 0; i < data.identities.length; i++) {
                    if (!ExfeeWidget.isMyIdentity(data.identities[i])
                     && !ExfeeWidget.checkExistence(data.identities[i])) {
                        caughtIdentities.push(data.identities[i]);
                    }
                    ExfeeCache.cacheIdentities(caughtIdentities);
                    ExfeeCache.tried_key[key] = true;
                    if (ExfeeWidget.complete_key[objPanel[0].parentNode.id] === key) {
                        ExfeeWidget.showCompleteItems(objPanel, key, caughtIdentities);
                    }
                }
            }
        );
    },


    checkInput : function(objInput, force) {
        var strInput   = objInput.val(),
            arrInput   = strInput.split(/,|;|\r|\n|\t/),
            arrValid   = [],
            arrInvalid = [];
        if (ExfeeWidget.last_inputed[objInput[0].id] === strInput && !force) {
            return;
        } else {
            ExfeeWidget.last_inputed[objInput[0].id]  =  strInput;
        }
        for (var i = 0; i < arrInput.length; i++) {
            if (!(arrInput[i] = ExfeUtilities.trim(arrInput[i]))) {
                delete arrInput[i];
            }
        }
        for (i = 0; i < arrInput.length; i++) {
            var item = ExfeeWidget.parseAttendeeInfo(arrInput[i]);
            if (item && (parseInt(i) < arrInput.length - 1 || force)) {
                arrValid.push(item);
            } else {
                arrInvalid.push(ExfeUtilities.trim(arrInput[i]));
            }
        }
        var newInput = arrInvalid.join('; ');
        if (newInput !== strInput) {
            objInput.val(newInput);
        }
        for (i = 0; i < arrValid.length; i++) {
            this.addExfee(arrValid[i]);
        }
        this.checkComplete(objInput, arrInvalid.pop());
    },


    inputEvent : function(event) {
        var objInput = $(event.target);
        switch (event.type) {
            case 'keydown':
                switch (event.which) {
                    case 9:  // tab
                        ExfeeWidget.checkInput(objInput, true);
                        break;
                    case 13: // enter
                        // var objSelected = $('#' + domId + '_exfeegadget_autocomplete > ol > .autocomplete_selected'),
                        //     curItem     = objSelected.length ? objSelected.attr('identity') : null;
                        // if (odof.exfee.gadget.completing[domId] && curItem) {
                        //     odof.exfee.gadget.addExfeeFromCache(domId, curItem);
                        //     odof.exfee.gadget.displayComplete(domId, false);
                        //     $('#' + domId + '_exfeegadget_inputbox').val('');
                        // } else {
                            ExfeeWidget.checkInput(objInput, true);
                        // }
                        break;
                    case 27: // esc
                        if (ExfeeWidget.completing) {
                            ExfeeWidget.displayComplete(
                                $(objInput[0].parentNode.parentNode)[0].id,
                                false
                            );
                        }
                        break;
                    case 38: // up
                    case 40: // down
                        // var baseId     = '#' + domId + '_exfeegadget_autocomplete',
                        //     objCmpBox  = $(baseId),
                        //     cboxHeight = 207,
                        //     cellHeight = 51,
                        //     shrMargin  = 3,
                        //     curScroll  = objCmpBox.scrollTop();
                        // if (!odof.exfee.gadget.completing[domId]) {
                        //     return;
                        // }
                        // var objSelected = $(baseId + ' > ol > .autocomplete_selected'),
                        //     curItem     = null,
                        //     idxItem     = null,
                        //     tarIdx      = null,
                        //     maxIdx      = odof.exfee.gadget.curComplete[domId].length - 1;
                        // if (objSelected.length) {
                        //     curItem = objSelected.attr('identity');
                        //     for (var i in odof.exfee.gadget.curComplete[domId]) {
                        //         if (odof.exfee.gadget.curComplete[domId][i] === curItem) {
                        //             idxItem = parseInt(i);
                        //             break;
                        //         }
                        //     }
                        // }
                        // switch (event.which) {
                        //     case 38:
                        //         tarIdx = curItem
                        //                ? (idxItem > 0 ? (idxItem - 1) : maxIdx)
                        //                : maxIdx;
                        //         break;
                        //     case 40:
                        //         tarIdx = curItem
                        //                ? (idxItem < maxIdx ? (idxItem + 1) : 0)
                        //                : 0;
                        // }
                        // odof.exfee.gadget.selectCompleteResult(
                        //     domId,
                        //     odof.exfee.gadget.curComplete[domId][tarIdx]
                        // );
                        // var curCellTop = tarIdx * cellHeight,
                        //     curScrlTop = curCellTop - curScroll;
                        // if (curScrlTop < 0) {
                        //     objCmpBox.scrollTop(curCellTop);
                        // } else if (curScrlTop + cellHeight > cboxHeight) {
                        //     objCmpBox.scrollTop(curCellTop + cellHeight - cboxHeight + shrMargin);
                        // }
                }
                break;
            case 'blur':
                var objPanel = $(objInput[0].parentNode.parentNode).find('.autocomplete');
                ExfeeWidget.displayCompletePanel(objPanel, false);
        }
    },

};



define('exfeepanel', [], function (require, exports, module) {

    var objBody = $('body');

    objBody.bind('click', function(event) {
        var domEvent = event.target;
        while (domEvent
            && !$(domEvent).hasClass('exfee_pop_up')
            && !$(domEvent).hasClass('exfee_pop_up_save')
            && domEvent.tagName !== 'BODY') {
            domEvent = domEvent.parentNode;
        }
        if (!$(domEvent).hasClass('exfee_pop_up')
         && !$(domEvent).hasClass('exfee_pop_up_save')) {
            $('.exfee_pop_up').hide().remove();
        }
    });

    return {

        objBody    : objBody,

        tipId      : '',

        panelId    : '',

        arrRsvp    : {NORESPONSE : ['Not responded'],
                      ACCEPTED   : ['Accepted'],
                      INTERESTED : ['Interested'],
                      DECLINED   : ['Declined']},

        invitation : {},


        newId : function(invitation) {
            return 'id_'                + invitation.identity.id
                 + 'provider_'          + invitation.identity.provider
                 + 'external_id_'       + invitation.identity.external_id
                 + 'external_username_' + invitation.identity.external_username;
        },


        showTip : function(invitation, x, y) {
            var strTipId = this.newId(invitation),
                strPanel = '<div class="exfeetip exfee_pop_up" style="top: 785px; right: 285px; display: none;">'
                         +   '<div class="inner">'
                         +     '<h5>' + invitation.identity.name + '</h5>'
                         +     '<div>'
                         +       '<i class="icon-user"></i><span>' + invitation.identity.external_username + '</span>'
                         +     '</div>'
                         +   '</div>'
                         + '</div>';
            if (this.tipId !== strTipId || !$('.exfeetip').length) {
                this.tipId  =  strTipId;
                this.hideTip();
                this.objBody.append(strPanel);
                $('.exfeetip').show();
            }
        },


        showPanel : function(invitation, x, y) {
            var strTipId = this.newId(invitation),
                strPanel = '<div class="exfeepanel exfee_pop_up" style="right: 600px; top: 500px">'
                         +   '<div class="inner">'
                         +     '<div class="avatar-name">'
                         +       '<span class="pull-left avatar">'
                         +         '<img src="' + invitation.identity.avatar_filename + '" alt="" width="60" height="60" />'
                         +         '<span class="rb"><i class="icon-plus-sign"></i></span>'
                         +       '</span>'
                         +       '<h4>' + invitation.identity.name + '</h4>'
                         +     '</div>'
                         +     '<div class="rsvp-actions">'
                         +       '<div class="rsvp-info">'
                         +         '<div class="rsvp"></div>'
                         +       '</div>'
                         +       '<div class="rsvp-edit">'
                         +         '<div class="rsvp"></div>'
                         +         '<span>by <strong>dm</strong></span>'
                         +         '<button class="btn rsvp-btn">Change</button>'
                         +       '</div>'
                         +       '<div class="pull-right invited">'
                         +         '<span class="mates">'
                         +          '<i class="pull-left mates-minus icon14-mates-minus"></i>'
                         +          '<span class="pull-left num">2</span>'
                         +          '<i class="pull-left mates-add"></i>'
                         +        '</span>'
                         +       '</div>'
                         +     '</div>'
                         +     '<div class="identities">'
                         +       '<ul class="identities-list">'
                         +         '<li>'
                         +           '<i class="pull-left icon-envelope"></i>'
                         +           '<span class="identity">steve_longaddress@0d0f.com</span>'
                         +           '<div class="identity-btn">'
                         +               '<i class="icon-minus-sign"></i>'
                         +               '<button class="btn-leave">Leave</button>'
                         +           '</div>'
                         +         '</li>'
                         +       '</ul>'
                         +       '<div class="identity-actions">'
                         +         '<p>'
                         +           '<span class="xalert-error">Remove yourself?</span>'
                         +           '<br />'
                         +           'You will <strong>NOT</strong> be able to access any information in this <span class="x-sign">X</span>. Confirm leaving?'
                         +           '<button class="pull-right btn-cancel">Cancel</button>'
                         +         '</p>'
                         +       '</div>'
                         +     '</div>'
                         +     '<!--i class="expand nomore"></i-->'
                         +   '</div>'
                         + '</div>';
            this.invitation = ExfeUtilities.clone(invitation);
            if (this.panelId !== strTipId || !$('.exfeepanel').length) {
                this.panelId  =  strTipId;
                this.hideTip();
                this.hidePanel();
                this.objBody.append(strPanel);
                this.bindEvents();
                $('.exfeepanel').show();
            }
            this.showRsvp();
        },


        hideTip : function() {
            $('.exfeetip').hide().remove();
        },


        hidePanel : function() {
            $('.exfeepanel').hide().remove();
        },


        showRsvp : function() {
            $('.exfee_pop_up .rsvp-info .rsvp').html(
                this.arrRsvp[this.invitation.rsvp_status][0]
            );
            $('.exfee_pop_up .rsvp-edit .rsvp').html(
                this.arrRsvp[this.invitation.rsvp_status][0]
            );
            if (this.invitation.mates) {
                $('.exfee_pop_up .mates .num').html(this.invitation.mates).show();
                $('.exfee_pop_up .mates .mates-minus').show();
                $('.exfee_pop_up .mates .mates-add').toggleClass('icon14-mates-add',  true);
                $('.exfee_pop_up .mates .mates-add').removeClass('icon-plus-blue',   false);
            } else {
                $('.exfee_pop_up .mates .num').hide();
                $('.exfee_pop_up .mates .mates-minus').hide();
                $('.exfee_pop_up .mates .mates-add').toggleClass('icon14-mates-add', false);
                $('.exfee_pop_up .mates .mates-add').toggleClass('icon-plus-blue',    true);
            }
        },


        bindEvents : function() {
            $('.exfee_pop_up .mates .mates-add').bind('click',   this.matesAdd);
            $('.exfee_pop_up .mates .mates-minus').bind('click', this.matesMinus);
        },


        matesAdd : function() {
            ExfeePanel.invitation.mates
        = ++ExfeePanel.invitation.mates > 9 ? 9
        :   ExfeePanel.invitation.mates;
            ExfeeWidget.changeMates(
                ExfeePanel.invitation.identity,
                ExfeePanel.invitation.mates
            );
            ExfeePanel.showRsvp();
        },


        matesMinus : function() {
            ExfeePanel.invitation.mates
        = --ExfeePanel.invitation.mates < 0 ? 0
        :   ExfeePanel.invitation.mates;
            ExfeeWidget.changeMates(
                ExfeePanel.invitation.identity,
                ExfeePanel.invitation.mates
            );
            ExfeePanel.showRsvp();
        },


    };

});



define(function (require, exports, module) {

    var $        = require('jquery'),
        Timeline = [],
        rawCross = {
            title : '', description : '', by_identity : {id : 0},
            time  : {
                begin_at : {
                    date_word : '', date : '',
                    time_word : '', time : '',
                    timezone  : '', id   : 0, type : 'EFTime'
                },
                origin : '', outputformat : 1, id : 0, type : 'CrossTime'
            },
            place : {
                title    : '', description : '', lng : 0, lat  : 0,
                provider : '', external_id : 0,  id  : 0, type : 'Place'
            },
            attribute : {state : 'published'},
            exfee_id  : 0,
            widget    : {
                background : {
                    image     : '', widget_id : 0,
                    id        : 0,  type      : 'Background'
                }
            },
            relative : {id : 0, relation : ''}, type : 'Cross'
        },
        rawExfee = {id : 0, type : 'Exfee', invitations : []};


    var ExfeeWidgestInit = function() {
        ExfeeCache.init();
        window.GatherExfeeWidget = ExfeeWidget.make(
            'gather-exfee', true, ShowExfee
        );
        window.CrossExfeeWidget  = ExfeeWidget.make(
            'cross-exfee',  true, ShowExfee
        );
    };


    var ButtonsInit = function() {
        $('#cross-form-discard').bind('click', function() {
            window.location = '/';
        });
        $('#cross-form-gather').bind('click', function() {
            if (Cross.by_identity.id) {
                Gather();
            } else {
                // 需要登录
            }
        });
        $('.cross-conversation .comment-form textarea').bind(
            'keydown',
            function(event) {
                switch (event.which) {
                    case 13: // enter
                        var objInput = $(event.target),
                            message  = objInput.val();
                        if (!event.shiftKey && message.length) {
                            event.preventDefault();
                            objInput.val('');
                            var post = {
                                by_identity_id : User.default_identity.id,
                                content        : message.substr(0, 233),
                                id             : 0,
                                relative       : [],
                                type           : 'Post',
                                via            : 'exfe.com'
                            };
                            Api.request(
                                'addConversation',
                                {
                                    resources : {exfee_id : Exfee.id},
                                    type      : 'POST',
                                    data      : JSON.stringify(post)
                                },
                                function(data) {
                                    ShowMessage(data.post);
                                },
                                function(data) {
                                    console.log(data);
                                }
                            );
                        }
                        break;
                }
            }
        )
    };


    var GatherFormInit = function() {
        var objGatherTitle = $('#gather-title');
        objGatherTitle.bind('focus keydown keyup blur', function(event) {
            ChangeTitle(objGatherTitle.val(), 'gather');
        });
    };


    var EditCross = function(event) {
        var domWidget  = event ? event.target : null,
            editArea   = $(domWidget).attr('editarea'),
            editMethod = {
            title : [
                function() {
                    $('.cross-title .show').show();
                    $('.cross-title .edit').hide();
                    ChangeTitle($('.cross-title .edit').val(), 'cross');
                },
                function() {
                    $('.cross-title .show').hide();
                    $('.cross-title .edit').show().focus();
                }
            ],
            description : [
                function() {
                    $('.cross-description .show').show();
                    $('.cross-description .edit').hide();
                    ChangeDescription($('.cross-description .edit').val());
                },
                function() {
                    $('.cross-description .show').hide();
                    $('.cross-description .edit').show().focus();
                }
            ],
            time : [
                function() {
                    $('.cross-date .show').show();
                    $('.cross-date .edit').hide();
                    ChangeTime($('.cross-date .edit').val());
                },
                function() {
                    $('.cross-date .show').hide();
                    $('.cross-date .edit').show().focus();
                }
            ],
            place : [
                function() {
                    $('.cross-place .show').show();
                    $('.cross-place .edit').hide();
                    ChangePlace($('.cross-place .edit').val());
                },
                function() {
                    $('.cross-place .show').hide();
                    $('.cross-place .edit').show().focus();
                },
            ],
            rsvp : [
                function() {
                    ShowRsvp();
                },
                function() {
                    ShowRsvp(true);
                },
            ]
        };
        if (event) {
            event.stopPropagation();
        }
        while (domWidget && !editArea && domWidget.tagName !== 'BODY') {
            domWidget = domWidget.parentNode;
            editArea  = $(domWidget).attr('editarea');
        }
        for (var i in editMethod) {
            editMethod[i][~~(i === editArea)]();
        }
    };


    var Editable = function() {
        $('body').bind('click', EditCross);
        $('.cross-title .show').bind('click', EditCross);
        $('.cross-title .edit').bind('focus keydown keyup blur', function(event) {
            if (event.type === 'keydown') {
                switch (event.which) {
                    case 13:
                        if (!event.shiftKey) {
                            event.preventDefault();
                            EditCross();
                        }
                        break;
                }
            }
            ChangeTitle($(event.target).val(), 'cross');
        });
        $('.cross-description .show').bind('click', EditCross);
        $('.shuffle-background').bind('click', fixBackground);
        $('.cross-rsvp .show .change').bind('click', EditCross);
        $('.cross-rsvp .edit .accept').bind('click', function() {
            ExfeeWidget.rsvpMe('ACCEPTED');
            ShowRsvp();
        });
        $('.cross-rsvp .edit .decline').bind('click', function() {
            ExfeeWidget.rsvpMe('DECLINED');
            ShowRsvp();
        });
        $('.cross-rsvp .edit .interested').bind('click', function() {
            ExfeeWidget.rsvpMe('INTERESTED');
            ShowRsvp();
        });
        $('.cross-date .edit').bind('focus keydown keyup blur', function(event) {
            ChangeTime($(event.target).val());
        });
        $('.cross-place .edit').bind('keydown', function(event) {
            if (event.shiftKey && event.which === 13) {
                //event.preventDefault();
                event.which = 4;
            }
        });
        $('.cross-edit').bind('click', SaveCross);
    };


    var fixTitle = function() {
        if (!Cross.title.length) {
            Cross.title = User ? 'Meet ' + User.default_identity.name : 'Gather a X';
        }
    };


    var fixTime = function() {
        var strDate = moment().format('YYYY-MM-DD');
        Cross.time = {
            begin_at : {
                date_word : '', date : strDate,
                time_word : '', time : '',
                timezone  : ExfeUtilities.getTimezone(),
                id        : 0,  type : 'EFTime'
            },
            origin : strDate, outputformat : 0, id : 0, type : 'CrossTime'
        };
        $('.cross-date .edit').val(strDate);
    };


    var fixBackground = function() {
        var strBgImg = Cross.widget.background.image;
        do {
            Cross.widget.background.image = AvailableBackgrounds[
                parseInt(Math.random() * AvailableBackgrounds.length)
            ];
        } while (strBgImg === Cross.widget.background.image);
        ShowBackground();
    };


    var fixExfee = function() {
        ExfeeWidget.addExfee(User.default_identity, true, 'ACCEPTED');
    };


    var ChangeTitle = function(title, from) {
        Cross.title = ExfeUtilities.trim(title);
        ShowTitle(from);
    };


    var ChangeDescription = function(description) {
        Cross.description = ExfeUtilities.trim(description);
        ShowDescription();
    };


    var ChangeTime = function(time) {
        Cross.time = ExfeUtilities.parseTimestring(time);
    };


    var ChangePlace = function(place) {
        Cross.place = ExfeUtilities.parsePlacestring(place);
        ShowPlace();
    };


    var ShowTitle = function(from) {
        var title = Cross.title.length ? Cross.title : 'Enter intent';
        $('.cross-title .show').html(title);
        document.title = 'EXFE - ' + title;
        // @todo 不同长度的 title 使用不同的样式
        switch (from) {
            case 'gather':
                $('.cross-title .edit').val(Cross.title);
                break;
            case 'cross':
                $('#gather-title').val(Cross.title);
                break;
            default:
                $('#gather-title').val(Cross.title);
                $('.cross-title .edit').val(Cross.title);
        }
    };


    var ShowDescription = function() {
        $('.cross-description .show').html(
            Cross.description
          ? Marked.parse(Cross.description)
          : 'Click here to describe something about this X.'
        );
        $('.cross-description .edit').html(Cross.description);
    };


    var ShowTime = function() {
        var strAbsTime = '', strRelTime = '', format = 'YYYY-MM-DD';
        if (Cross.time.origin) {
            if (Cross.time.outputformat) {
                strAbsTime = Cross.time.origin;
                strRelTime = '&nbsp;';
            } else if (Cross.time.begin_at.time) {
                var rawUtc = moment.utc(
                    Cross.time.begin_at.date + ' '
                  + Cross.time.begin_at.time,
                    format + ' HH:mm:ss'
                ).local();
                strAbsTime = rawUtc.format('h:mmA on ddd, MMM D');
                strRelTime = rawUtc.fromNow();
                strRelTime = strRelTime.indexOf('a few seconds') !== -1
                           ? 'Now'   : strRelTime;
            } else {
                strAbsTime = Cross.time.begin_at.date;
                strRelTime = strAbsTime === moment().format(format)
                           ? 'Today' : moment(strAbsTime, format).fromNow();
            }
        } else {
            strAbsTime = 'Click here to set time.';
            strRelTime = 'Sometime';
        }
        $('.cross-date h2').html(strRelTime);
        $('.cross-time').html(strAbsTime);
    };


    var ShowPlace = function() {
        $('.cross-dp.cross-place > h2').html(
            Cross.place.title
          ? Cross.place.title
          : 'Somewhere'
        );
        $('.cross-dp.cross-place > address').html(
            Cross.place.description || Cross.place.title
          ? Cross.place.description.replace(/\r\n|\r|\n/g, '<br>')
          : 'Click here to set place.'
        );
    };


    var ShowExfee = function() {
        window.GatherExfeeWidget.showAll();
        window.CrossExfeeWidget.showAll();
    };


    var ShowBackground = function() {
        if (!Cross.widget.background.image) {
            fixBackground();
        }
        $('.cross-background').css(
            'background-image',
            'url(/static/img/xbg/' + Cross.widget.background.image + ')'
        );
    };


    var ShowTimeline = function(timeline) {
        $('#conversation-form span.avatar img').attr(
            'src', User.default_identity.avatar_filename
        );
        $('.cross-conversation').slideDown(233);
        Timeline = timeline;
        for (var i = Timeline.length - 1; i >= 0; i--) {
            ShowMessage(Timeline[i]);
        }
    };


    var ShowMessage = function(message) {
        var strContent = ExfeUtilities.trim(message.content).replace(/\r\n|\n\r|\r|\n/g, '<br>'),
            strMessage = '<div class="avatar-comment">'
                       +   '<span class="pull-left avatar">'
                       +     '<img alt="" src="' + message.by_identity.avatar_filename + '" width="40" height="40" />'
                       +   '</span>'
                       +   '<div class="comment">'
                       +     '<p>'
                       +       '<span class="author"><strong>DM.</strong>:&nbsp;</span>'
                       +          strContent
                       +       '<span class="pull-right date">'
                       +         '<time>' + moment(message.created_at, 'YYYY-MM-DD HH:mm:ss Z').fromNow() + '</time>'
                       +       '</span>'
                       +     '</p>'
                       +   '</div>'
                       + '</div>';
        $('.conversation-timeline').prepend(strMessage);
    };


    var ShowRsvp = function(buttons) {
        var myInvitation = ExfeeWidget.getMyInvitation();
        if (myInvitation) {
            var by_identity = myInvitation.by_identity
                            ? myInvitation.by_identity
                            : User.default_identity,
                byMe        = myInvitation.identity.id === by_identity.id;
            if (myInvitation.rsvp_status === 'NORESPONSE' || buttons) {
                $('.cross-rsvp .edit .by').html(
                    byMe
                  ? '&nbsp;'
                  : ('Invitation from ' + myInvitation.by_identity.name)
                );
                $('.cross-rsvp .show').slideUp(233);
                $('.cross-rsvp .edit').slideDown(233);
                return;
            } else if (myInvitation.rsvp_status === 'ACCEPTED'
                    || myInvitation.rsvp_status === 'INTERESTED'
                    || myInvitation.rsvp_status === 'DECLINED') {
                var attendance = '', by = '';
                switch(myInvitation.rsvp_status) {
                    case 'ACCEPTED':
                        attendance = 'Accepted';
                        by         = 'Confirmed by ';
                        break;
                    case 'DECLINED':
                        attendance = 'Declined';
                        by         = 'Declined by ';
                        break;
                    case 'INTERESTED':
                        attendance = 'Interested';
                }
                by = byMe || myInvitation.rsvp_status === 'INTERESTED'
                   ? '&nbsp;' : (by + myInvitation.by_identity.name);
                var objSummary = ExfeeWidget.summary(),
                    strSummary = '';
                for (var i = 0; i < objSummary.accepted_invitations.length; i++) {
                    strSummary += '<span>'
                                +   '<img src="'
                                +      objSummary.accepted_invitations[i].identity.avatar_filename
                                +   '">'
                                +   '<span>'
                                +     (objSummary.accepted_invitations[i].mates
                                     ? objSummary.accepted_invitations[i].mates
                                     : '')
                                +   '</span>'
                                + '</span>';
                }
                strSummary += objSummary.accepted
                            ? (objSummary.accepted + ' accepted.') : '';
                var objAccepted = $('.cross-rsvp .show .accepted');
                if (objAccepted.html() !== strSummary) {
                    objAccepted.html(strSummary);
                }
                $('.cross-rsvp .show .attendance').html(attendance);
                $('.cross-rsvp .show .by').html(by);
                $('.cross-rsvp .show').slideDown(233);
                $('.cross-rsvp .edit').slideUp(233);
                return;
            }
        }
        $('.cross-rsvp .show').slideUp(233);
        $('.cross-rsvp .edit').slideUp(233);
    };


    var ShowCross = function() {
        ShowTitle();
        ShowDescription();
        ShowPlace();
        ShowExfee();
        ShowBackground();
        ShowRsvp();
    };


    var GetTimeline = function() {
        Api.request(
            'conversation',
            {resources : {exfee_id : Exfee.id}},
            function(data) {
                ShowTimeline(data.conversation);
            },
            function(data) {
                console.log(data);
            }
        );
    };


    var UpdateCross = function(objCross) {
        Cross.id          = objCross.id;
        Cross.title       = objCross.title;
        Cross.description = objCross.description;
        Cross.time        = objCross.time;
        Cross.place       = objCross.place;
        Cross.background  = objCross.background;
        Cross.exfee_id    = objCross.exfee.id;
        Exfee             = objCross.exfee;
        $('.cross-date .edit').val(Cross.time.origin);
        ShowCross();
        GetTimeline();
    };


    var GetCross = function(cross_id) {
        Api.request(
            'getCross',
            {resources : {cross_id : Cross_id}},
            function(data) {
                UpdateCross(data.cross);
            },
            function(data) {
                console.log(data);
            }
        );
    };


    var ResetCross = function() {
        window.Cross = ExfeUtilities.clone(rawCross);
        window.Exfee = ExfeUtilities.clone(rawExfee);
        fixTitle();
        fixTime();
        fixExfee();
    };


    var NewCross = function(NoReset) {
        if (!NoReset) {
            ResetCross();
        }
        ShowCross();
        ShowGatherForm();
    };


    var Gather = function() {
        var objCross   = ExfeUtilities.clone(Cross);
        objCross.exfee = ExfeUtilities.clone(Exfee);
        Api.request(
            'gather',
            {type : 'POST', data : JSON.stringify(objCross)},
            function(data) {
                ShowGatherForm(true);
                UpdateCross(data.cross);
            },
            function(data) {
                console.log(data);
            }
        );
    };


    var SaveCross = function() {
        var objCross   = ExfeUtilities.clone(Cross);
        objCross.exfee = ExfeUtilities.clone(Exfee);
        Api.request(
            'editCross',
            {type      : 'POST',
             resources : {cross_id : Cross.id},
             data      : JSON.stringify(objCross)},
            function(data) {
                UpdateCross(data.cross);
            },
            function(data) {
                console.log(data);
            }
        );
    };


    var ShowGatherForm = function(hide) {
        if (hide) {
            $('.cross-form').slideUp(233);
            $('.cross-edit').show(233);
        } else {
            $('.cross-form').slideDown(233);
            $('.cross-edit').hide(233);
            $('#gather-title').select();
            $('#gather-title').focus();
        }
    }


    // init api
    window.Store = require('store');
    window.Api   = require('api');


    // get current user
    var Signin  = Store.get('signin');
    window.User = Signin ? Store.get('user') : null;
    if (User) {
        Api.setToken(Signin.token);
    }


    // init moment
    require('moment');
    // init cross step 1
    ResetCross();
    // init exfee widgets
    ExfeeWidgestInit();
    // init buttons
    ButtonsInit();
    // init gather form
    GatherFormInit();
    // init edit area
    Editable();
    // init marked
    Marked = require('marked');
    // init exfee panel
    window.ExfeePanel = require('exfeepanel');
    // init showtime
    var showtimeTimer = setInterval(ShowTime, 50);


    // get cross
    var Cross_id = 0; // 100134;
    if (Cross_id) {
        GetCross(Cross_id);
    } else {
        NewCross(true);
        if (User) {
            Cross.by_identity.id = User.default_identity.id;
        }
    }

});
