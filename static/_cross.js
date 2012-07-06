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
            '<li class="identity" provider="' + invitation.identity.provider.toLowerCase()
          +                 '" external_id="' + invitation.identity.external_id.toLowerCase()
          +           '" external_username="' + invitation.identity.external_username.toLowerCase() + '">'
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
        for (var i = 0; i < Exfee.invitations; i++) {
            if (compareIdentity(Exfee.invitations[i].identity, identity)) {
                return true;
            }
        }
        return false;
    },


    addExfee : function(identity) {
        if (!this.checkExistence(identity)) {
            Exfee.invitations.push({
                identity    : ExfeUtilities.clone(identity),
                rsvp_status : 'NORESPONSE',
                host        : false,
                mates       : 0
            });
        }
        this.callback();
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


define(function (require, exports, module) {

    var $        = require('jquery');

    var Timeline = [];


    window.Cross = {
        title       : '',
        description : '',
        by_identity : {id : 0},
        time           : {
            begin_at : {
                date_word : '',
                date      : '',
                time_word : '',
                time      : '',
                timezone  : '',
                id        : 0,
                type      : 'EFTime'
            },
            origin       : '',
            outputformat : 1,
            id           : 0,
            type         : 'CrossTime'
        },
        place       : {
            title       : '',
            description : '',
            lng         : 0,
            lat         : 0,
            provider    : '',
            external_id : 0,
            id          : 0,
            type        : 'Place'
        },
        attribute   : {state : 'published'},
        exfee_id    : 0,
        widget      : {
            background : {
                image     : '',
                widget_id : 0,
                id        : 0,
                type      : 'Background'
            }
        },
        relative    : {id : 0, relation : ''},
        type        : 'Cross'
    };


    window.Exfee = {
        id          : 0,
        type        : 'Exfee',
        invitations : []
    };


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

                },
                function() {

                }
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
        $('.cross-date .edit').bind('focus keydown keyup blur', function(event) {
            ChangeTime($(event.target).val());
        });
        $('.cross-description .show').bind('click', EditCross);
        $('.shuffle-background').bind('click', fixBackground);
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
        $('.cross-dp.cross-place > h2').html(Cross.place.title);
        $('.cross-dp.cross-place > address').html(Cross.place.description);
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
        $('.cross-conversation').show();
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


    var ShowCross = function() {
        ShowTitle(true);
        ShowDescription();
        ShowPlace();
        ShowExfee();
        ShowBackground();
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
        Cross.title       = objCross.title;
        Cross.description = objCross.description;
        Cross.time        = objCross.time;
        Cross.place       = objCross.place;
        Cross.background  = objCross.background;
        Cross.exfee_id    = objCross.exfee.id;
        Exfee             = objCross.exfee;
        ShowCross();
        GetTimeline();
        ChangeTime($('.cross-date .edit').val(Cross.time.origin));
    };


    var Gather = function() {
        var strCross = JSON.stringify(Cross);
        Api.request(
            'gather',
            {type : 'POST', data : strCross},
            function(data) {
                console.log(data);
                $('.cross-form').slideUp(300);
            },
            function(data) {
                // failed
                console.log(data);
            }
        );
    };


    // init api
    window.Store = require('store');
    window.Api   = require('api');


    // get current user
    var Signin  = Store.get('signin');
    window.User = Signin ? Store.get('user') : null;
    if (User) {
        Api.setToken(Signin.token);
    }


    // init exfee widgets
    ExfeeWidgestInit();
    // init buttons
    ButtonsInit();
    // init gather form
    GatherFormInit();
    // init edit area
    Editable();
    // init moment
    require('moment');
    // init marked
    Marked = require('marked');
    // init showtime
    var showtimeTimer = setInterval(ShowTime, 50);


    // get cross
    var Cross_id = 100134;
    if (0) {
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
    } else {
        fixTitle();
        fixTime();
        ShowCross();
        $('.cross-form').show();
        if (User) {
            Cross.by_identity.id = User.default_identity.id;
        }
    }

});















































ns = {};

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
