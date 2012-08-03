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
                arrTime.push(parseInt(arrTime[3], 10) === 12 ? 'pm' : 'am');
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
        var title = arrPlace.shift();
        title = title ? title : '';
        return {
            title : title, description : arrPlace.join('\r'), lng : 0, lat : 0,
            provider : '', external_id : 0, id : Cross.place.id, type : 'Place'
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
             &&  ExfeeWidget.checkExistence(this.identities[i]) === false) {
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


    fetchIdentity : function(identity) {
        for (var i = 0; i < this.identities.length; i++) {
            if (ExfeeWidget.compareIdentity(identity, this.identities[i])) {
                return ExfeUtilities.clone(this.identities[i]);
            }
        }
        return null;
    }

};



ExfeeWidget = {

    dom_id           : '',

    rsvp_status      : ['Not responded', 'Accepted', 'Declined', 'Interested'],

    editable         : false,

    complete_timer   : 0,

    complete_key     : '',

    complete_exfee   : [],

    complete_request : 0,

    selected         : '',

    completing       : false,

    callback         : function() {},

    last_inputed     : {},

    base_info_timer  : 0,

    api_url          : '',


    make : function(dom_id, editable, callback) {
        this.dom_id   = dom_id;
        this.editable = editable;
        this.callback = callback;
        $('#' + this.dom_id + ' .invite').hide();
        $('#' + this.dom_id + ' .total').hide();
        $('#' + this.dom_id).bind(
            'mouseenter mouseleave',
            function(event) {
                switch (event.type) {
                    case 'mouseenter':
                        $('#' + dom_id + ' .invite').fadeIn(100);
                        $('#' + dom_id + ' .total').fadeIn(100);
                        break;
                    case 'mouseleave':
                        if ($('#' + dom_id + ' .exfee-input').val() === '') {
                            $('#' + dom_id + ' .invite').fadeOut(100);
                            $('#' + dom_id + ' .total').fadeOut(100);
                        }
                }
            }
        );
        $('#' + this.dom_id + ' .input-xlarge').bind(
            'keydown blur', this.inputEvent
        );
        $('#' + this.dom_id + ' .thumbnails > li.identity > .avatar').live(
            'mouseenter mouseleave mousedown',
            function(event) {
                switch (event.type) {
                    case 'mouseenter':
                        ExfeeWidget.showTip(this.parentNode);
                        break;
                    case 'mouseleave':
                        ExfeePanel.hideTip();
                        break;
                    case 'mousedown':
                        ExfeeWidget.showPanel(this.parentNode);
                }
            }
        );
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
            if (Exfee.invitations[i].rsvp_status !== 'REMOVED') {
                var intCell = Exfee.invitations[i].mates + 1;
                this.showOne(Exfee.invitations[i]);
                if (Exfee.invitations[i].rsvp_status === 'ACCEPTED') {
                    intAccepted += intCell;
                }
                intTotal += intCell;
            }
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
          +         '<span class="rt' + (invitation.host ? ' icon10-host-h' : '') + '"></span>'
          +         '<span class="lt">' + (invitation.mates ? invitation.mates : '') + '</span>'
       // +         '<span class="rb"><i class="icon-time"></i></span>'
          +     '</span>'
          +     '<div class="identity-name">' + invitation.identity.name + '</div>'
          + '</li>'
        );
    },


    showTip : function(target) {
        var objTarget         = $(target),
            objOffset         = objTarget.offset(),
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
        ExfeePanel.showTip(objInvitation, objOffset.left, objOffset.top + 50);
    },


    showPanel : function(target) {
        var objTarget         = $(target),
            objOffset         = objTarget.offset(),
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
        ExfeePanel.showPanel(objInvitation, objOffset.left + 5, objOffset.top + 5);
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
        if (identity) {
            var idx = this.checkExistence(identity);
            if (idx === false) {
                Exfee.invitations.push({
                    identity    : ExfeUtilities.clone(identity),
                    rsvp_status : rsvp ? rsvp : 'NORESPONSE',
                    host        : !!host,
                    mates       : 0
                });
            } else {
                Exfee.invitations[idx].rsvp_status = 'NORESPONSE';
            }
            this.callback();
        }
    },


    delExfee : function(identity) {
        this.rsvpExfee(identity, 'REMOVED');
    },


    rsvpExfee : function(identity, rsvp) {
        var idx = this.checkExistence(identity);
        if (idx !== false) {
            Exfee.invitations[idx].rsvp_status = rsvp;
            var refresh = false;
            if (rsvp === 'REMOVED' && curIdentity
             && ExfeeWidget.compareIdentity(Exfee.invitations[idx].identity, curIdentity)) {
                refresh = true;
            }
            this.callback(refresh);
        }
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
            this.callback();
        }
    },


    rsvpMe : function(rsvp) {
        this.rsvpExfee(curIdentity, rsvp);
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
        function getAvatarUrl(provider, external_username) {
            var avatar = ExfeeWidget.api_url + '/avatar/get?provider=' + provider + '&external_username=' + external_username;
            if (provider === 'email') {
                avatar = 'http://www.gravatar.com/avatar/' + external_username + '?d=' + encodeURIComponent(avatar);
            }
            return avatar;
        }
        string = ExfeUtilities.trim(string);
        var objIdentity = {
            id                : 0,
            name              : '',
            external_id       : '',
            external_username : '',
            provider          : '',
            type              : 'identity'
        }
        if (/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/.test(string)) {
            var iLt = string.indexOf('<'),
                iGt = string.indexOf('>');
            objIdentity.external_id       = ExfeUtilities.trim(string.substring(++iLt, iGt));
            objIdentity.external_username = objIdentity.external_id;
            objIdentity.name              = ExfeUtilities.trim(this.cutLongName(ExfeUtilities.trim(string.substring(0, iLt)).replace(/^"|^'|"$|'$/g, '')));
            objIdentity.provider          = 'email';
            objIdentity.avatar_filename   = getAvatarUrl('email', objIdentity.external_username);
        } else if (/^[a-zA-Z0-9!#$%&\'*+\\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/.test(string)) {
            objIdentity.external_id       = string;
            objIdentity.external_username = string;
            objIdentity.name              = ExfeUtilities.trim(this.cutLongName(string.split('@')[0]));
            objIdentity.provider          = 'email';
            objIdentity.avatar_filename   = getAvatarUrl('email', string);
        } else if (/^@[a-z0-9_]{1,15}$|^@[a-z0-9_]{1,15}@twitter$|^[a-z0-9_]{1,15}@twitter$/i.test(string)) {
            objIdentity.external_id       = '';
            objIdentity.external_username = string.replace(/^@|@twitter$/ig, '');
            objIdentity.name              = objIdentity.external_username;
            objIdentity.provider          = 'twitter';
            objIdentity.avatar_filename   = getAvatarUrl('twitter', objIdentity.external_username);
        } else if (/^[a-z0-9_]{1,15}@facebook$/i.test(string)) {
            objIdentity.external_id       = '';
            objIdentity.external_username = string.replace(/@facebook$/ig, '');
            objIdentity.name              = objIdentity.external_username;
            objIdentity.provider          = 'facebook';
            objIdentity.avatar_filename   = getAvatarUrl('facebook', objIdentity.external_username);
        } else {
            objIdentity = null;
        }
        return objIdentity;
    },


    checkComplete : function(objInput, key) {
        this.showCompleteItems(objInput, key, key ? ExfeeCache.search(key) : []);
        this.ajaxComplete(objInput, key);
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


    displayCompletePanel : function(objInput, display) {
        if ((this.completing = display)) {
            var ostInput = objInput.offset();
            $('.ids-popmenu').css({
                left :  ostInput.left + 'px',
                top  : (ostInput.top  + objInput.height() + 10) + 'px'
            }).slideDown(50);
        } else {
            $('.ids-popmenu').slideUp(50);
        }
    },


    showCompleteItems : function(objInput, key, identities) {
        // @todo: 使用 typeahead 替代这段代码
        var objCompleteList  = $('.ids-popmenu > ol'),
            strCompleteItems = '';
        key = key ? key.toLowerCase() : '';
        if (ExfeeWidget.complete_key !== key) {
            ExfeeWidget.complete_exfee = [];
            objCompleteList.html('');
        }
        ExfeeWidget.complete_key = key;
        for (var i = 0; i < identities.length; i++) {
            var shown = false;
            for (var j = 0; j < ExfeeWidget.complete_exfee.length; j++) {
                if (this.compareIdentity(ExfeeWidget.complete_exfee[j], identities[i])) {
                    shown = true;
                    break;
                }
            }
            if (shown) {
                continue;
            }
            var index = ExfeeWidget.complete_exfee.push(ExfeUtilities.clone(identities[i])) - 1;
            strCompleteItems += '<li' + (index ? '' : ' class="active"') + '>'
                              +   '<span class="pull-left avatar">'
                              +     '<img src="' + identities[i].avatar_filename + '" alt="" width="40" height="40">'
                              +     '<span class="rb"><i class="icon16-identity-' + identities[i].provider + '"></i></span>'
                              +   '</span>'
                              +   '<div class="identity">'
                              +     '<div class="name">' + identities[i].name + '</div>'
                              +     '<div>'
                              +       '<span class="externalid">' + this.displayIdentity(identities[i]) + '</span>'
                              +     '</div>'
                              +   '</div>'
                              + '</li>';
        }
        objCompleteList.append(strCompleteItems);
        this.displayCompletePanel(
            objInput,
            key && ExfeeWidget.complete_exfee.length
        );
    },


    isMyIdentity : function(identity) {
        return curIdentity
            && (this.compareIdentity(identity, curIdentity)
             || identity.connected_user_id === curIdentity.connected_user_id);
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
        return curIdentity ? this.getInvitationByIdentity(curIdentity) : null;
    },


    ajaxComplete : function(objInput, key) {
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
                     &&  ExfeeWidget.checkExistence(data.identities[i]) === false) {
                        caughtIdentities.push(data.identities[i]);
                    }
                    ExfeeCache.cacheIdentities(caughtIdentities);
                    ExfeeCache.tried_key[key] = true;
                    if (ExfeeWidget.complete_key === key) {
                        ExfeeWidget.showCompleteItems(objInput, key, caughtIdentities);
                    }
                }
            }
        );
    },


    ajaxIdentity : function(identities) {
        if (identities && identities.length) {
            Api.request(
                'getIdentity',
                {type      : 'POST',
                 data      : {identities : JSON.stringify(identities)}},
                function(data) {
                    var caughtIdentities = [];
                    for (var i = 0; i < data.identities.length; i++) {
                        if (data.identities[i].id) {
                            var idx = ExfeeWidget.checkExistence(data.identities[i]);
                            if (idx !== false) {
                                Exfee.invitations[idx].identity = data.identities[i];
                            }
                            caughtIdentities.push(data.identities[i]);
                        }
                    }
                    if (caughtIdentities.length) {
                        ExfeeCache.cacheIdentities(caughtIdentities);
                        window.GatherExfeeWidget.showAll();
                        window.CrossExfeeWidget.showAll();
                    }
                }
            );
        }
    },


    checkInput : function(objInput, force) {
        if (!objInput || !objInput.length) {
            return;
        }
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
        this.ajaxIdentity(arrValid);
        this.checkComplete(objInput, arrInvalid.pop());
    },


    selectCompleteItem : function(index) {
        var className = 'active';
        $('.ids-popmenu > ol > li').removeClass(className).eq(index).addClass(className);
    },


    useCompleteItem : function(index) {
        var identity = ExfeeCache.fetchIdentity(this.complete_exfee[index]);
        if (identity) {
            this.complete_exfee.splice(index, 1);
            this.addExfee(identity);
            ExfeeCache.cacheIdentities(identity);
        }
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
                        var objSelected = $('.ids-popmenu > ol > .active'),
                            curItem     = objSelected.length ? ~~objSelected.index() : null;
                        if (ExfeeWidget.completing && curItem !== null) {
                            ExfeeWidget.useCompleteItem(curItem);
                            ExfeeWidget.displayCompletePanel(objInput, false);
                            objInput.val('');
                        } else {
                            ExfeeWidget.checkInput(objInput, true);
                        }
                        break;
                    case 27: // esc
                        if (ExfeeWidget.completing) {
                            ExfeeWidget.displayCompletePanel(objInput, false);
                        }
                        break;
                    case 38: // up
                    case 40: // down
                        event.preventDefault();
                        var objCmpBox  = $('.ids-popmenu > ol'),
                            cboxHeight = 207,
                            cellHeight = 51,
                            shrMargin  = 3,
                            curScroll  = objCmpBox.scrollTop();
                        if (!ExfeeWidget.completing) {
                            return;
                        }
                        var objSelected = objCmpBox.find('.active'),
                            curItem     = ~~objSelected.index(),
                            maxIdx      = ExfeeWidget.complete_exfee.length - 1;
                        switch (event.which) {
                            case 38: // up
                                if (--curItem < 0) {
                                    curItem = maxIdx;
                                }
                                break;
                            case 40: // down
                                if (++curItem > maxIdx) {
                                    curItem = 0;
                                }
                        }
                        ExfeeWidget.selectCompleteItem(curItem);
                        var curCellTop = curItem * cellHeight,
                            curScrlTop = curCellTop - curScroll;
                        if (curScrlTop < 0) {
                            objCmpBox.scrollTop(curCellTop);
                        } else if (curScrlTop + cellHeight > cboxHeight) {
                            objCmpBox.scrollTop(curCellTop + cellHeight - cboxHeight + shrMargin);
                        }
                }
                break;
            case 'blur':
                ExfeeWidget.displayCompletePanel(objInput, false);
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

        mates_edit : false,

        pre_delete : false,


        newId : function(invitation) {
            return 'id_'                + invitation.identity.id
                 + 'provider_'          + invitation.identity.provider
                 + 'external_id_'       + invitation.identity.external_id
                 + 'external_username_' + invitation.identity.external_username;
        },


        showTip : function(invitation, x, y) {
            var strTipId = this.newId(invitation),
                strPanel = '<div class="exfeetip exfee_pop_up" style="left: ' + x + 'px; top: ' + y + 'px; display: none; z-index: 11">'
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
                strPanel = '<div class="exfeepanel exfee_pop_up" style="left: ' + x + 'px; top: ' + y + 'px; z-index: 10">'
                         +   '<div class="inner">'
                         +     '<div class="avatar-name">'
                         +       '<span class="pull-left avatar">'
                         +         '<img src="' + invitation.identity.avatar_filename + '" alt="" width="60" height="60" />'
                         +         '<span class="rb"><i class="icon-plus-sign"></i></span>'
                         +       '</span>'
                         +       '<h4>' + invitation.identity.name + '</h4>'
                         +     '</div>'
                         +     '<div class="rsvp-actions">'
                         +       '<div class="rsvp-area">'
                         +         '<div class="rsvp-info">'
                         +           '<div class="rsvp"></div>'
                         +         '</div>'
                         +         '<div class="rsvp-edit">'
                         +           '<div class="rsvp"></div>'
                         +           '<span class="by">by <strong></strong></span>'
                         +           '<button class="btn rsvp-btn"></button>'
                         +         '</div>'
                         +       '</div>'
                         +       '<div class="pull-right invited mates-area">'
                         +         '<span class="mates-info">'
                         +           '<span class="num"></span>'
                         +           '<i class="pull-left mates-add icon-plus-blue"></i>'
                         +         '</span>'
                         +         '<span class="mates-edit">Together with'
                         +           '<i class="pull-left mates-minus icon14-mates-minus"></i>'
                         +           '<span class="pull-left num"></span>'
                         +           '<i class="pull-left mates-add"></i>'
                         +         '</span>'
                         +       '</div>'
                         +     '</div>'
                         +     '<div class="identities">'
                         +       '<ul class="identities-list">'
                         +         '<li>'
                         +           '<i class="pull-left icon-envelope"></i>'
                         +           '<span class="identity">' + invitation.identity.external_username + '</span>'
                         +           '<div class="identity-btn delete">'
                         +               '<i class="icon-minus-sign">-</i>'
                         +               '<button class="btn-leave">Leave</button>'
                         +           '</div>'
                         +         '</li>'
                         +       '</ul>'
                         +       '<div class="identity-actions">'
                         +         '<p>'
                         +           '<span class="xalert-error">Remove yourself?</span>'
                         +           '<br />'
                         +           '<span class="xalter-info">You will <strong>NOT</strong> be able to access any information in this <span class="x-sign">X</span>. Confirm leaving?</span>'
                         +           '<button class="pull-right btn-cancel">Cancel</button>'
                         +         '</p>'
                         +       '</div>'
                         +     '</div>'
                         +     '<!--i class="expand nomore"></i-->'
                         +   '</div>'
                         + '</div>';
            this.invitation = ExfeUtilities.clone(invitation);
            if (this.panelId   !== strTipId || !$('.exfeepanel').length) {
                this.panelId    =  strTipId;
                this.mates_edit =  false;
                this.pre_delete =  false;
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
            var by_identity = this.invitation.by_identity
                            ? this.invitation.by_identity : curIdentity,
                next_rsvp   = '';
            switch (this.invitation.rsvp_status) {
                case 'NORESPONSE':
                    next_rsvp = 'ACCEPTED';
                    break;
                case 'ACCEPTED':
                    next_rsvp = 'DECLINED';
                    break;
                case 'DECLINED':
                    next_rsvp = 'NORESPONSE';
                    break;
                default:
                    return;
            }
            $('.exfee_pop_up .rsvp-info .rsvp').html(
                this.arrRsvp[this.invitation.rsvp_status][0]
            );
            $('.exfee_pop_up .rsvp-edit .rsvp').html(
                this.arrRsvp[this.invitation.rsvp_status][0]
            );
            if (by_identity) {
                if (this.invitation.identity.id === by_identity.id) {
                    $('.exfee_pop_up .rsvp-edit .by').hide();
                } else {
                    $('.exfee_pop_up .rsvp-edit .by strong').html(by_identity.name);
                    $('.exfee_pop_up .rsvp-edit .by').show();
                }
            }
            if (this.invitation.rsvp_status === 'ACCEPTED') {
                $('.exfee_pop_up .mates-area').show();
                if (this.mates_edit) {
                    $('.exfee_pop_up .rsvp-area').hide();
                    $('.exfee_pop_up .mates-info').hide();
                    $('.exfee_pop_up .mates-edit').show();
                } else {
                    $('.exfee_pop_up .rsvp-area').show();
                    $('.exfee_pop_up .mates-info').show();
                    $('.exfee_pop_up .mates-edit').hide();
                }
                if (this.invitation.mates) {
                    $('.exfee_pop_up .mates-info .num').html('+' + this.invitation.mates);
                    $('.exfee_pop_up .mates-info .mates-add').hide();
                    $('.exfee_pop_up .mates-edit .num').html(this.invitation.mates);
                    $('.exfee_pop_up .mates-edit .mates-minus').show();
                    $('.exfee_pop_up .mates-edit .mates-add').toggleClass('icon14-mates-add',  true).toggleClass('icon-plus-blue', false);
                } else {
                    $('.exfee_pop_up .mates-info .num').html('');
                    $('.exfee_pop_up .mates-info .mates-add').show();
                    $('.exfee_pop_up .mates-edit .num').html('');
                    $('.exfee_pop_up .mates-edit .mates-minus').hide();
                    $('.exfee_pop_up .mates-edit .mates-add').toggleClass('icon14-mates-add', false).toggleClass('icon-plus-blue',  true);
                }
            } else {
                $('.exfee_pop_up .mates-area').hide();
            }
            $('.exfee_pop_up .rsvp-edit .rsvp-btn').html(next_rsvp).attr('rsvp', next_rsvp);
            if (this.invitation.host) {
                $('.exfee_pop_up .identities-list .delete i').hide();
                $('.exfee_pop_up .identities-list .delete button').hide();
                $('.exfee_pop_up .identity-actions').hide();
            } else {
                if (this.pre_delete) {
                    $('.exfee_pop_up .identities-list .delete i').hide();
                    $('.exfee_pop_up .identities-list .delete button').show();
                    if (curIdentity && this.invitation.identity.id === curIdentity.id) {
                        $('.exfee_pop_up .identity-actions').show();
                        $('.exfee_pop_up .identities-list .btn-leave').html('Leave');
                    } else {
                        $('.exfee_pop_up .identity-actions').hide();
                        $('.exfee_pop_up .identities-list .btn-leave').html('Remove');
                    }
                } else {
                    $('.exfee_pop_up .identities-list .delete i').show();
                    $('.exfee_pop_up .identities-list .delete button').hide();
                    $('.exfee_pop_up .identity-actions').hide();
                }
            }
        },


        bindEvents : function() {
            $('.exfee_pop_up .mates-edit .mates-add').bind('click',    this.matesAdd);
            $('.exfee_pop_up .mates-edit .mates-minus').bind('click',  this.matesMinus);
            $('.exfee_pop_up .rsvp-edit .rsvp-btn').bind('click',      this.rsvp);
            $('.exfee_pop_up .mates-area').bind('mouseenter mouseout', function(event) {
                switch (event.type) {
                    case 'mouseenter':
                        ExfeePanel.mates_edit = true;
                        break;
                    case 'mouseout':
                        ExfeePanel.mates_edit = false;
                }
                ExfeePanel.showRsvp();
            });
            $('.exfee_pop_up .identities-list .delete i').bind('click',      function(event) {
                event.stopPropagation();
                ExfeePanel.pre_delete = true;
                ExfeePanel.showRsvp();
            });
            $('.exfee_pop_up .identities-list .delete button').bind('click', function(event) {
                ExfeeWidget.delExfee(ExfeePanel.invitation.identity);
                ExfeePanel.hidePanel();
            });
            $('.exfee_pop_up .identity-actions .btn-cancel').bind('click',   function(event) {
                ExfeePanel.pre_delete = false;
                ExfeePanel.showRsvp();
            });
            $('.exfee_pop_up').bind('click', function() {
                ExfeePanel.pre_delete = false;
                ExfeePanel.showRsvp();
            });
        },


        matesAdd : function() {
            if (ExfeePanel.invitation.mates < 9) {
                ExfeeWidget.changeMates(
                    ExfeePanel.invitation.identity,
                  ++ExfeePanel.invitation.mates
                );
                ExfeePanel.showRsvp();
            }
        },


        matesMinus : function() {
            if (ExfeePanel.invitation.mates > 0) {
                ExfeeWidget.changeMates(
                    ExfeePanel.invitation.identity,
                  --ExfeePanel.invitation.mates
                );
                ExfeePanel.showRsvp();
            }
        },


        rsvp : function() {
            var rsvp = $(this).attr('rsvp');
            if (rsvp) {
                ExfeePanel.invitation.rsvp_status = rsvp;
                ExfeeWidget.rsvpExfee(ExfeePanel.invitation.identity, rsvp);
                ExfeePanel.showRsvp();
            }
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
            widget : [{image : '', widget_id : 0, id : 0, type : 'Background'}],
            relative : {id : 0, relation : ''}, type : 'Cross'
        },
        rawExfee = {id : 0, type : 'Exfee', invitations : []};


    var SaveExfee = function(refresh) {
        if (Cross.id) {
            Api.request(
                'editExfee',
                {type      : 'POST',
                 resources : {exfee_id : Exfee.id},
                 data      : {by_identity_id : curIdentity.id,
                              exfee          : JSON.stringify(Exfee)}},
                function(data) {
                    if (refresh) {
                        window.location.reload();
                    }
                },
                function(data) {
                    console.log('Field');
                }
            );
        }
    };


    var ExfeeCallback = function(refresh) {
        ShowExfee();
        SaveExfee(refresh);
    };


    var ExfeeWidgestInit = function() {
        ExfeeCache.init();
        ExfeeWidget.api_url = require('config').api_url;
        window.GatherExfeeWidget = ExfeeWidget.make(
            'gather-exfee', true, ExfeeCallback
        );
        window.CrossExfeeWidget  = ExfeeWidget.make(
            'cross-exfee',  true, ExfeeCallback
        );
    };


    var ButtonsInit = function() {
        $('#cross-form-discard').bind('click', function() {
            window.location = '/';
        });
        $('#cross-form-gather').bind('click', function() {
            if (curIdentity) {
                Gather();
            } else {
                alert('Require Signin!');
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
                                by_identity_id : curIdentity.id,
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
        // @todo by @Leask: 暂时确保在 Cross 页
        if (!$('.cross-container').length || readOnly) {
            return;
        }
        var domWidget  = event ? event.target : null,
            editArea   = $(domWidget).attr('editarea'),
            editMethod = {
            title : [
                function() {
                    $('.cross-title .show').show();
                    $('.cross-title .edit').hide();
                    ChangeTitle($('.cross-title .edit').val(), 'cross');
                    AutoSaveCross();
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
                    AutoSaveCross();
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
                    AutoSaveCross();
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
                    AutoSaveCross();
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
        $('.ids-popmenu > ol > li').live(
            'mouseenter mousedown',
            function(event) {
                switch (event.type) {
                    case 'mouseenter':
                        ExfeeWidget.selectCompleteItem($(this).index());
                        break;
                    case 'mousedown':
                        ExfeeWidget.useCompleteItem($(this).index());
                }
            }
        );
        // $('.cross-edit').bind('click', SaveCross);
    };


    var fixTitle = function() {
        if (!Cross.title.length) {
            Cross.title = curIdentity ? 'Meet ' + curIdentity.name : 'Gather a X';
        }
    };


    var fixTime = function() {
        var strDate = moment().format('YYYY-MM-DD');
        Cross.time  = {
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
        var backgrounds = require('config').backgrounds;
        for (var i = 0; i < Cross.widget.length; i++) {
            if (Cross.widget[i].type === 'Background') {
                break;
            }
        }
        var strBgImg = Cross.widget[i].image;
        do {
            Cross.widget[i].image = backgrounds[
                parseInt(Math.random() * backgrounds.length)
            ];
        } while (strBgImg === Cross.widget[i].image);
        ShowBackground();
    };


    var fixExfee = function() {
        ExfeeWidget.addExfee(curIdentity, true, 'ACCEPTED');
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


    var ShowHost = function() {
        if (curIdentity) {
            $('.choose-identity').html(
                '<img src="' + curIdentity.avatar_filename + '">'
            );
        }
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
        function getTimezoneOffset(timezone) {
            if ((timezone = ExfeUtilities.trim(timezone))) {
                var arrTimezone = timezone.split(':');
                if (arrTimezone.length === 2) {
                    var intHour = parseInt(arrTimezone[0], 10) * 60 * 60,
                        intMin  = parseInt(arrTimezone[1], 10) * 60;
                    return intHour + (intHour > 0 ? intMin : -intMin);
                }
            }
            return null;
        }
        var crossOffset = getTimezoneOffset(Cross.time.begin_at.timezone),
            timeOffset  = getTimezoneOffset(ExfeUtilities.getTimezone()),
            timevalid   = crossOffset === timeOffset && require('config').timevalid,
            strAbsTime  = '', strRelTime = '', format = 'YYYY-MM-DD';
        if (Cross.time.origin) {
            if (Cross.time.outputformat) {
                strAbsTime  = 'No specified time, yet.';
                strRelTime  = Cross.time.origin;
            } else if (Cross.time.begin_at.time) {
                var objMon  = moment((moment.utc(
                    Cross.time.begin_at.date + ' '
                  + Cross.time.begin_at.time, format + ' HH:mm:ss'
                ).unix()    + (timevalid ? 0 : (crossOffset - timeOffset))) * 1000);
                strAbsTime  = objMon.format('h:mmA on ddd, MMM D')
                            + (timevalid ? '' : (' ' + Cross.time.begin_at.timezone));;
                strRelTime  = objMon.fromNow();
                strRelTime  = strRelTime.indexOf('a few seconds') !== -1
                            ? 'Now'   : strRelTime;
            } else {
                strAbsTime  = Cross.time.begin_at.date
                            + (timevalid ? '' : (' ' + Cross.time.begin_at.timezone));
                strRelTime  = Cross.time.begin_at.date === moment().format(format)
                            ? 'Today' : moment(strAbsTime, format).fromNow();
            }
        } else {
            strAbsTime = 'No specified time, yet.';
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
            Cross.place.description
          ? Cross.place.description.replace(/\r\n|\r|\n/g, '<br>')
          : 'Add some place details.'
        );
    };


    var ShowExfee = function() {
        window.GatherExfeeWidget.showAll();
        window.CrossExfeeWidget.showAll();
    };


    var ShowBackground = function() {
        for (var i = 0; i < Cross.widget.length; i++) {
            if (Cross.widget[i].type === 'Background') {
                break;
            }
        }
        if (!Cross.widget[i].image) {
            fixBackground();
        }
        $('.cross-background').css(
            'background-image',
            'url(/static/img/xbg/' + Cross.widget[i].image + ')'
        );
    };


    var ShowTimeline = function(timeline) {
        $('#conversation-form span.avatar img').attr(
            'src', curIdentity.avatar_filename
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
                            ? myInvitation.by_identity : curIdentity,
                byMe        = myInvitation.identity.id === by_identity.id;
            if (myInvitation.rsvp_status === 'NORESPONSE' || buttons) {
                $('.cross-rsvp .edit .by').html(
                    byMe
                  ? '&nbsp;'
                  : ('Invitation from ' + myInvitation.by_identity.name)
                );
                $('.cross-rsvp .show').hide();
                $('.cross-rsvp .edit').fadeIn(233);
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
                                +   '<img height="20" width="20" alt="" src="'
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
                $('.cross-rsvp .show').fadeIn(233);
                $('.cross-rsvp .edit').hide();
                return;
            }
        }
        $('.cross-rsvp .show').hide();
        $('.cross-rsvp .edit').hide();
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


    var UpdateCross = function(objCross, read_only) {
        Cross.id          = objCross.id;
        Cross.title       = objCross.title;
        Cross.description = objCross.description;
        Cross.time        = objCross.time;
        Cross.place       = objCross.place;
        Cross.widget      = objCross.widget;
        Cross.exfee_id    = objCross.exfee.id;
        Exfee             = objCross.exfee;
        readOnly          = read_only;
        savedCross        = summaryCross();
        $('.cross-date  .edit').val(Cross.time.origin);
        $('.cross-place .edit').val(
            Cross.place.title + (Cross.place.description ? ('\n' + Cross.place.description) : '')
        );
        ShowCross();
        GetTimeline();
    };


    var GetCross = function(cross_id) {
        Api.request(
            'getCross',
            {resources : {cross_id : cross_id}},
            function(data) {
                for (var i = 0; i < data.cross.exfee.invitations.length; i++) {
                    if (ExfeeWidget.isMyIdentity(data.cross.exfee.invitations[i].identity)) {
                        curIdentity = ExfeUtilities.clone(data.cross.exfee.invitations[i].identity);
                        break;
                    }
                }
                UpdateCross(data.cross, false);
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
        readOnly = false;
        if (!NoReset) {
            ResetCross();
        }
        ShowCross();
        ShowGatherForm();
    };


    var Gather = function() {
        var objCross   = ExfeUtilities.clone(Cross);
        objCross.exfee = ExfeUtilities.clone(Exfee);
        objCross.by_identity = {id : curIdentity.id};
        Api.request(
            'gather',
            {type : 'POST', data : JSON.stringify(objCross)},
            function(data) {
                document.location = '/#!' + data.cross.id;
            },
            function(data) {
                console.log(data);
            }
        );
    };


    var SaveCross = function() {
        var objCross   = ExfeUtilities.clone(Cross);
        objCross.by_identity = {id : curIdentity.id};
        Api.request(
            'editCross',
            {type      : 'POST',
             resources : {cross_id : Cross.id},
             data      : JSON.stringify(objCross)},
            function(data) {
                console.log('Saved');
            },
            function(data) {
                console.log('Field');
            }
        );
    };


    var summaryCross = function() {
        return JSON.stringify({
            id          : Cross.id,
            title       : Cross.title,
            description : Cross.description,
            time        : Cross.time.origin,
            place       : {title       : Cross.place.title,
                           description : Cross.place.description},
            background  : Cross.widget[0].image
        });
    };


    var AutoSaveCross = function() {
        if (Cross.id) {
            var curCross = summaryCross();
            if (savedCross !== curCross) {
                SaveCross();
                savedCross = curCross;
            }
        }
    };


    var ShowGatherForm = function(hide) {
        if (hide) {
            $('.cross-form').slideUp(233);
            $('.cross-edit').show(233);
        } else {
            ShowHost();
            $('.cross-form').slideDown(233);
            $('.cross-edit').hide(233);
            $('#gather-title').select();
            $('#gather-title').focus();
        }
    };


    // init api
    window.Store = require('store');
    window.Api   = require('api');


    // init participated identity
    window.curIdentity = null;
    // init read only flag
    window.readOnly    = false;
    // init bus
    var bus = require('bus');
    // init auto cross saving
    var savedCross = '';
    // init event: main
    bus.on('xapp:cross:main', function() {
        // get current user
        var Signin  = Store.get('signin');
        window.User = Signin ? Store.get('user') : null;
        if (User) {
            Api.setToken(Signin.token);
            curIdentity = ExfeUtilities.clone(User.default_identity);
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
        window.ExfeePanel    = require('exfeepanel');
        // init showtime
        window.showtimeTimer = setInterval(ShowTime, 50);
    });
    // init event
    bus.on('xapp:cross', function(Cross_id, browsingIdentity, cross, read_only, invitation_token) {
        // get cross
        if (Cross_id > 0) {
            GetCross(Cross_id);
        } else if (Cross_id === null) {
            curIdentity = browsingIdentity;
            UpdateCross(cross, read_only);
            Api.setToken(invitation_token);
        } else {
            NewCross(true);
        }
    });
    // init event: signin
    bus.on('xapp:usersignin', function() {
        if (!window.Cross) {
            // get current user
            var Signin  = Store.get('signin');
            window.User = Signin ? Store.get('user') : null;
            if (User) {
                Api.setToken(Signin.token);
                curIdentity = ExfeUtilities.clone(User.default_identity);
                ShowHost();
                fixExfee();
            }
        }
    });
    // init event: end
    bus.on('xapp:cross:end', function() {
        clearTimeout(window.showtimeTimer);
    });

});
