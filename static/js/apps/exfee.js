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
    
    ns.arrClassRsvp      = ['noresponse', 'accepted', 'declined', 'interested'];

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


    ns.make = function(domId, curExfee, curEditable) {
        var strHtml = '<div id="' + domId + '_exfeegadget_infoarea" class="exfeegadget_infoarea">'
                    +     '<div id="' + domId + '_exfeegadget_info_labelarea" class="exfeegadget_info_labelarea">'
                    +         '<img id="' + domId + '_exfeegadget_info_label">'
                    +         'Exfee'
                    +     '</div>'
                    +     '<div id="' + domId + '_exfeegadget_info" class="exfeegadget_info">'
                    +         '<span id="' + domId + '_exfeegadget_num_accepted" class="exfeegadget_num_accepted">'
                    +         '</span>'
                    +         '<span class="exfeegadget_num_of"> of '
                    +             '<span id="' + domId + '_exfeegadget_num_summary" class="exfeegadget_num_summary"></span>'
                    +         '</span>'
                    +         '<span class="exfeegadget_num_confirmed">confirmed</span>'
                    +     '</div>'
                    + '</div>'
                    + '<div id="' + domId + '_exfeegadget_avatararea" class="exfeegadget_avatararea">'
                    +     '<ol></ol>'
                    +     '<button id="' + domId + '_exfeegadget_expandavatarbtn">'
                    + '</div>'
                    + '<div id="' + domId + '_exfeegadget_inputarea" class="exfeegadget_inputarea">'
                    +     '<input  id="' + domId + '_exfeegadget_inputbox" class="exfeegadget_inputbox" type="text">'
                    +     '<button id="' + domId + '_exfeegadget_addbtn">+</button>'
                    +     '<div id="' + domId + '_exfeegadget_autocomplete" class="exfeegadget_autocomplete">'
                    +         '<ol></ol>'
                    +     '</div>'
                    + '</div>'
                    + '<div id="' + domId + '_exfeegadget_listarea" class="exfeegadget_listarea">'
                    +     '<ol></ol>'
                    + '</div>';
        this.inputed[domId]       = '';
        this.editable[domId]      = curEditable;
        this.exfeeInput[domId]    = {};
        this.keyComplete[domId]   = '';
        this.curComplete[domId]   = {};
        this.exfeeSelected[domId] = {};
        this.completing[domId]    = false;
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
        this.addExfee(domId, curExfee);
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
        return 'exfee_rsvp_' + this.arrClassRsvp[rsvp];
    };


    ns.addExfee = function(domId, exfees, noIdentity) {
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
            objExfee.host = typeof exfees[i].host === 'undefined' ? false : exfees[i].host;
            objExfee.rsvp = typeof exfees[i].rsvp === 'undefined' ? 0     : exfees[i].rsvp;
            var strClassRsvp = this.getClassRsvp(objExfee.rsvp);
            $('#' + domId + '_exfeegadget_avatararea > ol').append(
                '<li identity="' + objExfee.external_identity + '">'
              +     '<div class="exfee_avatarblock">'
              +         '<img src="' + odof.comm.func.getUserAvatar(
                        objExfee.avatar_file_name, 80, img_url)
              +         '" class="exfee_avatar">'
              +         '<div class="exfee_rsvpblock ' + strClassRsvp + '"></div>'
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
        if (!noIdentity) {
            this.ajaxIdentity(exfees);
        }
    };


    ns.delExfee = function(domId) {
        this.rawDelExfee(domId, this.exfeeSelected[domId]);
        this.chkFakeHost(domId);
        this.exfeeSelected = [];
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
    };


    ns.chkFakeHost = function(domId) {
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
                        odof.exfee.gadget.changeRsvp(domId, identity, 0);
                        break;
                    case 0:
                    case 2:
                    case 3:
                    default:
                        odof.exfee.gadget.changeRsvp(domId, identity, 1);
                }
        }
    };
    
    
    ns.changeRsvp = function(domId, identity, rsvp) {
        if (typeof this.exfeeInput[domId][identity] === 'undefined') {
            return;
        }
        this.exfeeInput[domId][identity].rsvp = rsvp;
        var strCatchKey   = ' > ol > li[identity="' + identity + '"] .exfee_rsvpblock';
        for (var i in this.arrClassRsvp) {
            var intRsvp = parseInt(i),
                strRsvp = this.getClassRsvp(intRsvp);
            if (intRsvp === rsvp) {
                $('#' + domId + '_exfeegadget_avatararea' + strCatchKey).addClass(strRsvp);
                $('#' + domId + '_exfeegadget_listarea'   + strCatchKey).addClass(strRsvp);
            } else {
                $('#' + domId + '_exfeegadget_avatararea' + strCatchKey).removeClass(strRsvp);
                $('#' + domId + '_exfeegadget_listarea'   + strCatchKey).removeClass(strRsvp);
            }
        }
        this.updateExfeeSummary(domId);
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
        odof.exfee.gadget.addExfee(domId, arrValid);
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
            if (key.indexOf(this.exfeeChecked[i]) !== -1) {
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
                    var item  = odof.util.trim(i).split(' '),
                        strId = item.pop(),
                        user  = {avatar_file_name  : 'default.png',
                                 bio               : '',
                                 external_identity : strId,
                                 name              : item.join(' '),
                                 provider          : 'email'},
                        curId = user.external_identity.toLowerCase(),
                        exist = false;
                    for (var j in odof.exfee.gadget.exfeeAvailable) {
                        if (odof.exfee.gadget.exfeeAvailable[j]
                                .external_identity.toLowerCase() === curId) {
                            exist = true;
                            break;
                        }
                    }
                    if (!exist) {
                        gotExfee.push(user);
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



































////////////////////////////////////////////////////////////////////////////////
//////////////////// OLD exfee editing code from x page ////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
if (0) {
    /**
     * update "check all" status
     * by Leask
     * */
    ns.updateCheckAll = function() {
        if ($('.cs > .c1').length < $('.samlcommentlist > li').length) {
            $('#check_all > span').html('Check all');
            $('#check_all > em').attr('class', 'c1');
        } else {
            $('#check_all > span').html('Uncheck all');
            $('#check_all > em').attr('class', 'c0');
        }
        // submit
        $.ajax({
            url  : location.href.split('?').shift() + '/crossEdit',
            type : 'POST',
            dataType : 'json',
            data : {ctitle     : $('#cross_titles_textarea').val(),
                    exfee_only : true,
                    exfee      : JSON.stringify(ns.getexfee())},
            success:function(data){
                if (!data) {
                    return;
                }
                if (!data.success) {
                    switch (data.error) {
                        case 'token_expired':
                            odof.cross.index.setreadonly();
                    }
                }
            }
        });
    };

    /**
     * do "check all" or "uncheck all"
     * by Leask
     * */
    ns.checkAll = function() {
        switch ($('#check_all > em')[0].className) {
            case 'c0':
                $('.cs > em').attr('class', 'c0');
                break;
            case 'c1':
                $('.cs > em').attr('class', 'c1');
        }
        ns.updateCheckAll();
    };

    /**
     * show external identity
     * by Leask
     * */
    ns.showExternalIdentity = function(event) {
        var target = $(event.target);
        while (!target.hasClass('exfee_item')) {
            target = $(target[0].parentNode);
        }
        var id     = target[0].id;
        if (!id) {
            return;
        }
        switch (event.type) {
            case 'mouseenter':
                ns.rollingExfee = id;
                $('#' + id + ' > .smcomment > div > .ex_identity').fadeIn(100);
                break;
            case 'mouseleave':
                ns.rollingExfee = null;
                $('#' + id + ' > .smcomment > div > .ex_identity').fadeOut(100);
                var rollE = $('#' + id + ' > .smcomment > div');
                rollE.animate({
                    marginLeft : '+=' + (0 - parseInt(rollE.css('margin-left')))},
                    700
                );
        }
    };

    /**
     * roll the exfee that with long name
     * by Leask
     * */
    ns.rollExfee = function() {
        var maxWidth = 200;
        if (!ns.rollingExfee) {
            return;
        }
        var rollE    = $('#' + ns.rollingExfee + ' > .smcomment > div'),
            orlWidth = rollE.width(),
            curLeft  = parseInt(rollE.css('margin-left')) - 1;
        if (orlWidth <= maxWidth) {
            return;
        }
        curLeft = curLeft <= (0 - orlWidth) ? maxWidth : curLeft;
        rollE.css('margin-left', curLeft + 'px');
    };

    /**
     * get auto complete infos of exfees from server
     * by Leask
     */
    ns.chkComplete = function(strKey) {
        $.ajax({
            type     : 'GET',
            url      : site_url + '/identity/complete?key=' + strKey,
            dataType : 'json',
            success  : function(data) {
                var strFound = '';
                for (var item in data) {
                    var spdItem = odof.util.trim(item).split(' '),
                        strId   = spdItem.pop(),
                        strName = spdItem.length ? (spdItem.join(' ') + ' &lt;' + strId + '&gt;') : strId;
                    if (!strFound) {
                        odof.cross.edit.strExfeeCompleteDefault = strId;
                    }
                    strFound += '<option value="' + strId + '"' + (strFound ? '' : ' selected') + '>' + strName + '</option>';
                }
                if (strFound && odof.cross.edit.completeTimer && $('#exfee_input').val().length) {
                    $('#exfee_complete').html(strFound);
                    $('#exfee_complete').slideDown(50);
                } else {
                    $('#exfee_complete').slideUp(50);
                }
                clearTimeout(odof.cross.edit.completeTimer);
                odof.cross.edit.completeTimer = null;
            }
        });
    };

    /**
     * check exfee format
     * by Leask
     */
    ns.chkExfeeFormat = function() {
        ns.arrIdentitySub = [];
        var strExfees = $('#exfee_input').val().replace(/\r|\n|\t/, '');
        $('#exfee_input').val(strExfees);
        var arrIdentityOri = strExfees.split(/,|;/);
        for (var i in arrIdentityOri) {
            if ((arrIdentityOri[i] = odof.util.trim(arrIdentityOri[i]))) {
                var exfee_item = odof.util.parseId(arrIdentityOri[i]);
                if (exfee_item.type !== 'email') {
                    return false;
                }
                ns.arrIdentitySub.push(exfee_item);
            }
        }
        return ns.arrIdentitySub.length > 0;
    };

    /**
     * auto complete for exfees
     * by Leask
     */
    ns.complete = function() {
        var strValue = $('#exfee_complete').val();
        if (strValue === '') {
            return;
        }
        var arrInput = $('#exfee_input').val().split(/,|;|\r|\n|\t/);
        arrInput.pop();
        $('#exfee_input').val(arrInput.join('; ') + (arrInput.length ? '; ' : '') + strValue);
        clearTimeout(odof.cross.edit.completeTimer);
        odof.cross.edit.completeTimer = null;
        $('#exfee_complete').slideUp(50);
        ns.identityExfee();
        $('#exfee_input').focus();
    };

    /**
     * summary exfee
     * by Leask
     * */
    ns.summaryExfee = function() {
        $('.bignb').html($('.cs > .c1').length);
        $('.malnb').html($('.samlcommentlist > li').length);
    };

    /**
     * identity exfee from server
     * by Leask
     * */
    ns.identityExfee = function() {
        if (!ns.chkExfeeFormat()) {
            return;
        }

        $.ajax({
            type     : 'GET',
            url      : site_url + '/identity/get?identities=' + JSON.stringify(ns.arrIdentitySub),
            dataType : 'json',
            success  : function(data) {
                var exfee_pv     = '',
                    identifiable = {},
                    id           = '',
                    identity     = '',
                    name         = '';
                for (var i in data.response.identities) {
                    id           = data.response.identities[i].id;
                    identity     = data.response.identities[i].external_identity;
                    name         = data.response.identities[i].name
                                 ? data.response.identities[i].name
                                 : data.response.identities[i].external_identity;
                    var avatar_file_name = data.response.identities[i].avatar_file_name;
                    if ($('#exfee_' + id).attr('id') == null) {
                        exfee_pv += '<li id="exfee_'   + id + '" '
                                  +     'identity="'   + identity + '" '
                                  +     'identityid="' + id + '" '
                                  +     'identityname="' + (name === identity ? '' : name) + '" '
                                  +     'class="exfee_exist exfee_item" '
                                  +     'invited="false">'
                                  +     '<button type="button" class="exfee_del"></button>'
                                  +     '<p class="pic20">'
                                  +         '<img src="'+odof.comm.func.getUserAvatar(avatar_file_name, 80, img_url)+'" alt="">'
                                  +     '</p>'
                                  +     '<div class="smcomment">'
                                  +         '<div>'
                                  +             '<span class="ex_name' + (name === identity ? ' external_identity' : '') + '">'
                                  +                 name
                                  +             '</span>'
                                  +             '<span class="ex_identity external_identity">'
                                  +                 (identity === name ? '' : identity)
                                  +             '</span>'
                                  +         '</div>'
                                  +     '</div>'
                                  +     '<p class="cs">'
                                  +         '<em class="c0"></em>'
                                  +     '</p>'
                                  + '</li>';
                    }
                    identifiable[identity.toLowerCase()] = true;
                }
                for (i in ns.arrIdentitySub) {
                    var idUsed = false;
                    $('.exfee_new').each(function() {
                        if ($(this).attr('identity').toLowerCase() === ns.arrIdentitySub[i].id.toLowerCase()) {
                            idUsed = true;
                        }
                    });
                    if (!identifiable[ns.arrIdentitySub[i].id.toLowerCase()] && !idUsed) {
                        identity = ns.arrIdentitySub[i].id;
                        name     = ns.arrIdentitySub[i].name
                                 ? ns.arrIdentitySub[i].name
                                 : ns.arrIdentitySub[i].id;
                        ns.numNewIdentity++;
                        exfee_pv += '<li id="newexfee_' + ns.numNewIdentity + '" '
                                  +     'identity="'    + identity + '" '
                                  +     'identityname="' + (name === identity ? '' : name) + '" '
                                  +     'class="exfee_new exfee_item" '
                                  +     'invited="false">'
                                  +     '<button type="button" class="exfee_del"></button>'
                                  +     '<p class="pic20">'
                                  +         '<img src="'+img_url+'/web/80_80_default.png" alt="">'
                                  +     '</p>'
                                  +     '<div class="smcomment">'
                                  +         '<div>'
                                  +             '<span class="ex_name' + (name === identity ? ' external_identity' : '') + '">'
                                  +                 name
                                  +             '</span>'
                                  +             '<span class="ex_identity external_identity">'
                                  +                 (identity === name ? '' : identity)
                                  +             '</span>'
                                  +         '</div>'
                                  +     '</div>'
                                  +     '<p class="cs">'
                                  +         '<em class="c0"></em>'
                                  +     '</p>'
                                  + '</li>';
                    }
                }
                $('#exfee_area > .samlcommentlist').html($('#exfee_area > .samlcommentlist').html() + exfee_pv);
                switch (ns.exfeeEditStatus) {
                    case 'edit':
                        $('.exfee_del').hide();
                        break;
                    case 'remove':
                        $('.exfee_del').show();
                }
                ns.summaryExfee();
                $('.ex_identity').hide();
                $('#exfee_input').val('');
            }
        });
    };

    /**
     * change exfee editing mode
     * by Leask
     * */
    ns.exfeeEdit = function(status){
        ns.exfeeEditStatus = status;
        switch (status) {
            case 'edit':
                if (!$('.editing').length) {
                    $('#exfee_area').toggleClass('editing');
                }
                $('#exfee_edit_box').fadeIn();
                $('#exfee_remove').fadeIn();
                $('#exfee_edit').hide();
                $('#exfee_remove').attr('disabled', false);
                $('#exfee_area').bind('clickoutside', function(event) {
                    if ($(event.target).hasClass('exfee_del')) {
                        return;
                    }
                    odof.cross.edit.exfeeEdit();
                });
                $('.exfee_del').hide();
                ns.exfees = $('#exfee_area > .samlcommentlist').html();
                break;
            case 'remove':
                $('#exfee_remove').attr('disabled', true);
                $('#exfee_area').bind('click', function(event) {
                    if (event.target.id === 'exfee_remove' || event.target.className === 'exfee_del') {
                        return;
                    }
                    odof.cross.edit.exfeeEdit('edit');
                });
                $('.exfee_del').show();
                break;
            default:
                $('#exfee_area').toggleClass('editing', false);
                $('#exfee_edit_box').fadeOut();
                $('#exfee_remove').hide();
                $('#exfee_edit').fadeIn();
                $('#exfee_edit_box').unbind('clickoutside');
                $('.exfee_del').hide();
                $('#exfee_input').val(odof.cross.edit.exfeeInputTips);
        }
        if (status !== 'remove') {
            $('#exfee_area').unbind('click');
        }
    };

    /**
     * revert exfee
     * by Leask
     * */
    ns.revertExfee = function() {
        $('#exfee_area > .samlcommentlist').html(ns.exfees);
        $('#exfee_input').val(odof.cross.edit.exfeeInputTips);
    };



}
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
///////////////// OLD exfee editing code from gather page //////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
// exfee
    var gExfeeDefaultText = $('#gather_exfee_bg').html();
    $('#post_submit').css('background', 'url("/static/images/enter_gray.png")');
    $('#exfee').keyup(function(e) {
        clearTimeout(completeTimer);
        completeTimer = null;
        switch (e.keyCode ? e.keyCode : e.which) {
            case 13:
                identity();
                e.preventDefault();
                break;
            case 27:
                $('#exfee_complete').slideUp(50);
                return;
        }
        var strExfee = $(this).val();
        if (strExfee) {
            $('#gather_exfee_bg').html('');
            var strKey = odof.util.trim(strExfee.split(/,|;|\r|\n|\t/).pop());
            if (strKey) {
                completeTimer = setTimeout("chkComplete('" + strKey + "')", 500);
            } else {
                $('#exfee_complete').slideUp(50);
            }
        } else {
            $('#gather_exfee_bg').html(gExfeeDefaultText);
            $('#exfee_complete').slideUp(50);
        }
    });
    $('#exfee').keydown(function(e) {
        switch (e.keyCode ? e.keyCode : e.which) {
            case 9:
            case 40:
                $('#exfee_complete').focus();
                e.preventDefault();
                break;
            case 13:
                e.preventDefault();
                break;
            default:
                $('#post_submit').css('background', 'url("/static/images/enter' + (chkExfeeFormat() ? '' : '_gray') + '.png")');
        }
    });
    $('#exfee').focus(function() {
        $('#gather_exfee_bg').addClass('gather_focus').removeClass('gather_blur');
    });
    $('#exfee').blur(function() {
        $('#gather_exfee_bg').addClass('gather_blur').removeClass('gather_focus')
                             .html($(this).val() ? '' : gExfeeDefaultText);
    });
    $('#exfee_complete').hide();
    $('#exfee_complete').bind('click keydown', function(e) {
        var intKey = e.keyCode ? e.keyCode : e.which;
        switch (e.type) {
            case 'click':
                complete();
                break;
            case 'keydown':
                switch (intKey) {
                    case 9:
                        if (e.shiftKey) {
                            $('#exfee').focus();
                            e.preventDefault();
                        }
                        break;
                    case 13:
                        complete();
                        break;
                    case 27:
                        clearTimeout(completeTimer);
                        completeTimer = null;
                        $('#exfee_complete').slideUp(50);
                    case 8:
                        $('#exfee').focus();
                        e.preventDefault();
                        break;
                    case 38:
                        if ($('#exfee_complete').val() === strExfeeCompleteDefault) {
                            $('#exfee').focus();
                            e.preventDefault();
                        }
                        break;
                    default:
                        if ((intKey > 64 && intKey < 91) || (intKey > 47 && intKey < 58)) {
                            $('#exfee').focus();
                        }
                }
        }
    });
    $('#exfee_complete').bind('clickoutside', function() {
        clearTimeout(completeTimer);
        completeTimer = null;
        $('#exfee_complete').slideUp(50);
    });

    $('.addjn').mousemove(function() {
        hide_exfeedel($(this));
    });

    $('.addjn').mouseout(function() {
        show_exfeedel($(this));
    });
    
function showExternalIdentity(event)
{
    var target = $(event.target);
    while (!target.hasClass('exfee_item')) {
        target = $(target[0].parentNode);
    }
    var id     = target[0].id;
    if (!id) {
        return;
    }
    switch (event.type) {
        case 'mouseenter':
            rollingExfee = id;
            $('#' + id + ' > .smcomment > div > .ex_identity').fadeIn(100);
            break;
        case 'mouseleave':
            rollingExfee = null;
            $('#' + id + ' > .smcomment > div > .ex_identity').fadeOut(100);
            var rollE = $('#' + id + ' > .smcomment > div');
            rollE.animate({
                marginLeft : '+=' + (0 - parseInt(rollE.css('margin-left')))},
                700
            );
    }
}


function rollExfee()
{
    var maxWidth = 200;
    if (!rollingExfee) {
        return;
    }
    var rollE    = $('#' + rollingExfee + ' > .smcomment > div'),
        orlWidth = rollE.width(),
        curLeft  = parseInt(rollE.css('margin-left')) - 1;
    if (orlWidth <= maxWidth) {
        return;
    }
    curLeft = curLeft <= (0 - orlWidth) ? maxWidth : curLeft;
    rollE.css('margin-left', curLeft + 'px');
}


function chkComplete(strKey)
{
    $.ajax({
        type     : 'GET',
        url      : site_url + '/identity/complete?key=' + strKey,
        dataType : 'json',
        success  : function(data) {
            var strFound = '';
            for (var item in data) {
                var spdItem = odof.util.trim(item).split(' '),
                    strId   = spdItem.pop(),
                    strName = spdItem.length ? (spdItem.join(' ') + ' &lt;' + strId + '&gt;') : strId;
                if (!strFound) {
                    window.strExfeeCompleteDefault = strId;
                }
                strFound += '<option value="' + strId + '"' + (strFound ? '' : ' selected') + '>' + strName + '</option>';
            }
            if (strFound && completeTimer && $('#exfee').val().length) {
                $('#exfee_complete').html(strFound);
                $('#exfee_complete').slideDown(50);
            } else {
                $('#exfee_complete').slideUp(50);
            }
            clearTimeout(completeTimer);
            completeTimer = null;
        }
    });
}


function chkExfeeFormat()
{
    window.arrIdentitySub = [];
    var strExfees = $('#exfee').val().replace(/\r|\n|\t/, '');
    $('#exfee').val(strExfees);
    var arrIdentityOri = strExfees.split(/,|;/);
    for (var i in arrIdentityOri) {
        if ((arrIdentityOri[i] = odof.util.trim(arrIdentityOri[i]))) {
            var exfee_item = odof.util.parseId(arrIdentityOri[i]);
            if (exfee_item.type !== 'email') {
                return false;
            }
            arrIdentitySub.push(exfee_item);
        }
    }
    return arrIdentitySub.length > 0;
}


function complete()
{
    var strValue = $('#exfee_complete').val();
    if (strValue === '') {
        return;
    }
    var arrInput = $('#exfee').val().split(/,|;|\r|\n|\t/);
    arrInput.pop();
    $('#exfee').val(arrInput.join('; ') + (arrInput.length ? '; ' : '') + strValue);
    clearTimeout(completeTimer);
    completeTimer = null;
    $('#exfee_complete').slideUp(50);
    identity();
    $('#exfee').focus();
}


function identity()
{
    if (!chkExfeeFormat()) {
        return;
    }

    $('#identity_ajax').show();

    $.ajax({
        type     : 'GET',
        url      : site_url + '/identity/get?identities=' + JSON.stringify(arrIdentitySub),
        dataType : 'json',
        success  : function(data) {
            $('#identity_ajax').hide();
            var exfee_pv     = [],
                name         = '',
                identifiable = {};
            for (var i in data.response.identities) {
                var identity         = data.response.identities[i].external_identity,
                    id               = data.response.identities[i].id,
                    avatar_file_name = data.response.identities[i].avatar_file_name;
                    name             = data.response.identities[i].name;
                if (!$('#exfee_' + id).length) {
                    name = name ? name : identity.split('@')[0].replace(/[^0-9a-zA-Z_\u4e00-\u9fa5\ \'\.]+/g, ' ');
                    while (odof.comm.func.getUTF8Length(name) > 30) {
                        name = name.substring(0, name.length - 1);
                    }
                    exfee_pv.push(
                        '<li id="exfee_' + id + '" class="addjn" onmousemove="javascript:hide_exfeedel($(this))" onmouseout="javascript:show_exfeedel($(this))">'
                      +     '<p class="pic20"><img src="'+odof.comm.func.getUserAvatar(avatar_file_name, 80, img_url)+'" alt="" /></p>'
                      +     '<p class="smcomment">'
                      +         '<span class="exfee_exist" id="exfee_' + id + '" identityid="' + id + '" value="' + identity + '" avatar="' + avatar_file_name + '">'
                      +             name
                      +         '</span>'
                      +         '<input id="confirmed_exfee_' + id + '" class="confirmed_box" type="checkbox"/>'
                      +     '</p>'
                      +     '<button class="exfee_del" onclick="javascript:exfee_del($(\'#exfee_' + id + '\'))" type="button"></button>'
                      + '</li>'
                    );
                }
                identifiable[identity.toLowerCase()] = true;
            }
            for (i in arrIdentitySub) {
                var idUsed = false;
                $('.exfee_new').each(function() {
                    if ($(this).attr('value').toLowerCase() === arrIdentitySub[i].id.toLowerCase()) {
                        idUsed = true;
                    }
                });
                if (!identifiable[arrIdentitySub[i].id.toLowerCase()] && !idUsed) {
                    switch (arrIdentitySub[i].type) {
                        case 'email':
                            name =  arrIdentitySub[i].name ? arrIdentitySub[i].name : arrIdentitySub[i].id;
                            break;
                        default:
                            name =  arrIdentitySub[i].id;
                    }
                    new_identity_id++;
                    exfee_pv.push(
                        '<li id="newexfee_' + new_identity_id + '" class="addjn" onmousemove="javascript:hide_exfeedel($(this))" onmouseout="javascript:show_exfeedel($(this))">'
                      +     '<p class="pic20"><img src="'+img_url+'/web/80_80_default.png" alt="" /></p>'
                      +     '<p class="smcomment">'
                      +         '<span class="exfee_new" id="newexfee_' + new_identity_id + '" value="' + arrIdentitySub[i].id + '">'
                      +             name
                      +         '</span>'
                      +         '<input id="confirmed_newexfee_' + new_identity_id + '" class="confirmed_box" type="checkbox"/>'
                      +     '</p>'
                      +     '<button class="exfee_del" onclick="javascript:exfee_del($(\'#newexfee_' + new_identity_id + '\'))" type="button"></button>'
                      + '</li>'
                    );
                }
            }

            while (exfee_pv.length) {
                var inserted = false;
                $('#exfee_pv > ul').each(function(intIndex) {
                    var li = $(this).children('li');
                    if (li.length < 4) {
                        // @todo: remove this in next version
                        if (($('.exfee_exist').length + $('.exfee_new').length) < 12) {
                            $(this).append(exfee_pv.shift());
                        } else {
                            exfee_pv.shift();
                            $('#exfee_warning').show();
                        }
                        inserted = true;
                    }
                });
                if (!inserted) {
                    // @todo: remove this in next version
                    if (($('.exfee_exist').length + $('.exfee_new').length) < 12) {
                        $('#exfee_pv').append('<ul class="exfeelist">' + exfee_pv.shift() + '</ul>');
                    } else {
                        exfee_pv.shift();
                        $('#exfee_warning').show();
                    }
                }
            }
            $('#exfee_pv').css('width', 300 * $('#exfee_pv > ul').length + 'px');
            updateExfeeList();
        },
        error: function() {
            $('#identity_ajax').hide();
        }
    });
}

function updateExfeeList()
{
    var exfees        = getexfee(),
        htmExfeeList  = '',
        numConfirmed  = 0,
        numSummary    = 0;
    for (var i in exfees) {
        numConfirmed += exfees[i].confirmed;
        numSummary++;
        var avatarFile = exfees[i].avatar ? exfees[i].avatar : 'default.png';
        htmExfeeList += '<li id="exfee_list_item_' + numSummary + '" class="exfee_item">'
                      +     '<p class="pic20"><img alt="" src="'+odof.comm.func.getUserAvatar(avatarFile, 80, img_url)+'"></p>'
                      +     '<div class="smcomment">'
                      +         '<div>'
                      +             '<span class="ex_name' + (exfees[i].exfee_name === exfees[i].exfee_identity ? ' external_identity' : '') + '">'
                      +                 exfees[i].exfee_name
                      +             '</span>'
                      +             (exfees[i].isHost ? '<span class="lb">host</span>' : '')
                      +             '<span class="ex_identity external_identity"> '
                      +                 (exfees[i].exfee_name === exfees[i].exfee_identity ? '' : exfees[i].exfee_identity)
                      +             '</span>'
                      +         '</div>'
                      +     '</div>'
                      +     '<p class="cs">'
                      +         '<em class="c' + (exfees[i].confirmed ? 1 : 2) + '"></em>'
                      +     '</p>'
                      + '</li>';
    }
    $('#exfeelist').html(htmExfeeList);
    $('#exfee_confirmed').html(numConfirmed);
    $('#exfee_summary').html(numSummary);
    $('#exfee_count').html(numSummary);
    $('#exfee').val('');
    $('.ex_identity').hide();
}
$('#confirmed_all').click(function(e) {
        var check = false;
        if ($(this).attr('check') === 'false') {
            $(this).attr('check', 'true');
            check=true;
        } else {
            $(this).attr('check', 'false');
        }

        $('.exfee_exist').each(function(e) {
            var element_id = $(this).attr('id');
            $('#confirmed_' + element_id).attr('checked',check);
        });
        $('.exfee_new').each(function(e) {
            var element_id = $(this).attr('id');
            $('#confirmed_' + element_id).attr('checked',check);
        });
    });
function hide_exfeedel(e)
{
    e.addClass('bgrond');
    $('.bgrond .exfee_del').show();
}

function show_exfeedel(e)
{
    e.removeClass('bgrond');
    $('.exfee_del').hide();
}

function exfee_del(e)
{
    e.remove();
    updateExfeeList();
}

function getexfee()
{
    var result = [];
    function collect(obj, exist)
    {
        var exfee_identity = $(obj).attr('value'),
            element_id     = $(obj).attr('id'),
            spanHost       = $(obj).parent().children('.lb'),
            item           = {exfee_name     : $(obj).html(),
                              exfee_identity : exfee_identity,
                              confirmed      : $('#confirmed_' + element_id)[0].checked  == true ? 1 : 0,
                              identity_type  : odof.util.parseId(exfee_identity).type,
                              isHost         : spanHost && spanHost.html() === 'host',
                              avatar         : $(obj).attr('avatar')};
        if (exist) {
            item.exfee_id  = $(obj).attr('identityid');
        }
        result.push(item);
    }
    $('.exfee_exist').each(function() {
        collect(this, true);
    });
    $('.exfee_new').each(function() {
        collect(this);
    });
    return result;
}
// exfee
    $('.ex_identity').hide();
    $('.exfee_item').live('mouseenter mouseleave', function(event) {
        showExternalIdentity(event);
    });
    window.rollingExfee = null;
    window.exfeeRollingTimer = setInterval(rollExfee, 50);

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
