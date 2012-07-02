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
    }
};


ExfeeCache = {

    identities       : [],

    tried_key        : {},

    updated_identity : {},

    init             : function() {
        var Store          = require('store');
        var cached_user_id = Store.get('exfee_cache_user_id');
        if (User && User.id && cached_user_id && User.id === cached_user_id) {
            try {
                ExfeeCache.identities = JSON.parse(
                    Store.get('exfee_cache_identities')
                );
            } catch (err) {
                ExfeeCache.identities = [];
            }
        } else {
            ExfeeCache.identities = [];
        }
    }

};


ExfeeWidget = {

    dom_id           : '',

    rsvp_status      : ['Not responded', 'Accepted', 'Declined', 'Interested'],

    editable         : false,

    completimer      : 0,

    complete_key     : '',

    complete_request : '',

    selected         : '',

    completing       : false,

    callback         : function() {},

    base_info_timer  : 0,


    make             : function(dom_id, editable, callback) {
        this.dom_id   = dom_id;
        this.editable = editable;
        this.callback = callback;
        return ExfeUtilities.clone(this);
    },


    show_all         : function() {
        for (var i = 0; i < Exfee.invitations.length; i++) {
            this.show_one(Exfee.invitations[i]);
        }
    },


    show_one         : function(invitation) {
        var strExfeeKey = 'provider_'    + invitation.identity.provider + '_'
                        + 'external_id_' + invitation.identity.external_id;
        $('#' + this.dom_id + ' .thumbnails').append(
            '<li class="identity" exfee="' + strExfeeKey + '">'
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


    compareIdentity  : function(identity_a, identity_b) {
        if (identity_a.id && identity_b.id && identity_a.id === identity_b.id) {
            return true;
        }
        if (ExfeUtilities.trim(identity_a.provider).toLowerCase()
        === ExfeUtilities.trim(identity_b.provider).toLowerCase()
         && ExfeUtilities.trim(identity_a.external_id).toLowerCase()
        === ExfeUtilities.trim(identity_b.external_id).toLowerCase()) {
            return true;
        }
        return false;
    },


    add_exfee        : function(identity) {
        for (var i = 0; i < Cross.invitations; i++) {
            if (compareIdentity(Cross.invitations[i].identity, identity)) {
                return;
            }
        }
        Cross.invitations.push({
            identity    : identity,
            rsvp_status : 'NORESPONSE',
            host        : false;
            mates       : 0
        });
    },

};















define(function (require, exports, module) {

    var $     = require('jquery');
    var Store = require('store');
    var Api   = require('api');


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
            outputformat : '',
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
        window.GatherExfeeWidget = ExfeeWidget.make(
            'gather-exfee', true, function() {

            }
        );
        window.CrossExfeeWidget  = ExfeeWidget.make(
            'cross-exfee', true, function() {

            }
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
    };


    var InputFormInit = function() {
        var objGatherTitle = $('#gather-title');
        objGatherTitle.bind('focus blur keyup', function() {
            ChangeTitle(objGatherTitle.val());
        });
    }


    var ChangeTitle = function(title) {
        Cross.title = ExfeUtilities.trim(title);
        ShowTitle();
    }


    var ShowTitle = function() {
        $('.cross-title > h1').html(Cross.title);
    };


    var ShowDescription = function() {
        $('.cross-description').html(Cross.description);
    };


    var ShowTime = function() {
        $('.cross-time').html(Cross.time.begin_at.date_word);
    };


    var ShowPlace = function() {
        $('.cross-dp.cross-place > h2').html(Cross.place.title);
        $('.cross-dp.cross-place > address').html(Cross.place.description);
    };


    var ShowExfee = function() {
        window.GatherExfeeWidget.show_all();
        window.CrossExfeeWidget.show_all();
    };


    var ShowBackground = function() {

    };


    var ShowCross = function() {
        ShowTitle();
        ShowDescription();
        ShowTime();
        ShowPlace();
        ShowExfee();
        ShowBackground();
    };


    var UpdateCross = function(objCross) {
        Cross.title       = objCross.title;
        Cross.description = objCross.description;
        Cross.time        = objCross.time;
        Cross.place       = objCross.place;
        Cross.exfee_id    = objCross.exfee.id;
        Cross.background  = objCross.background;
        Exfee             = objCross.exfee;
        ShowCross();
    };


    var Gather = function() {
        var strCross = JSON.stringify(Cross);
        Api.request(
            'gather',
            {type : 'POST', data : strCross},
            function(data) {
                console.log(data);
            },
            function(data) {
                // failed
                console.log(data);
            }
        );
    };


    // init exfee widgets
    ExfeeWidgestInit();


    // init buttons
    ButtonsInit();


    // init input form
    InputFormInit();


    // get current user
    var Signin  = Store.get('signin');
    window.User = Signin ? Store.get('user') : null;
    if (User) {
        Api.setToken(Signin.token);
        Cross.by_identity.id = User.default_identity.id;
    }

    // get cross
    var Cross_id = 100134;
    if (Cross_id) {
        Api.request(
            'getCross',
            {resources : {cross_id : Cross_id}},
            function(data) {
                UpdateCross(data.cross);
            },
            function(data) {
                // failed
                console.log(data);
            }
        );
    }



});

























ns = {};

ns.make = function(domId, curExfee, curEditable, curDiffCallback, skipInitCallback) {
    this.idsBuilt[domId] = true;
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
    $('#' + domId + '_exfeegadget_inputbox').live(
        'keydown blur', this.eventInputbox
    );
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
        case 'facebook':
            return identity.external_username + '@facebook';
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
            $('#' + domId + '_exfeegadget_avatararea > ol').append(
                '<li identity="' + objExfee.external_identity + '">'
              +     '<div class="exfee_avatarblock" '
              +         'unselectable="on" onselectstart="return false;">'
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
              +         '</div>'))
              +         '<div class="exfee_extrainfo_extraid_area">'
              +         '</div>'
              +     '</div>'
              + '</li>'
            );
            $('#' + domId + '_exfeegadget_listarea > ol').append(
                '<li identity="' + objExfee.external_identity + '">'
              //+     '<div class="exfee_rsvpblock ' + strClassRsvp + '"></div>'
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
        //}
    }
    this.chkFakeHost(domId);
    this.updateExfeeSummary(domId);
    if (!noCallback && this.diffCallback[domId]) {
        this.diffCallback[domId]();
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
    this.chkFakeHost(domId);
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
        var item = odof.comm.func.parseId(arrInput[i]);
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
    if (!key.length || typeof this.exfeeChecked[key] !== 'undefined') {
        return;
    }
    if (this.completeRequest) {
        this.completeRequest.abort();
    }
    this.completeRequest = $.ajax({
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
            odof.exfee.gadget.exfeeChecked[this.info.key] = true;
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
