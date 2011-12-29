/**
 * @Description: Exfee Editing Gadget
 * @Author:      Leask Huang <leask@exfe.com>
 * @createDate:  Dec 9, 2011
 * @CopyRights:  http://www.exfe.com
 */


var moduleNameSpace = 'odof.exfee.gadget';
var ns = odof.util.initNameSpace(moduleNameSpace);


(function(ns)
{

    ns.exfeeAvailableKey = 'exfee_available';

    ns.inputed           = {};

    ns.editable          = {};

    ns.exfeeInput        = {};

    ns.exfeeAvailable    = [];

    ns.completimer       = {};

    ns.keyComplete       = {};

    ns.curComplete       = {};

    ns.exfeeChecked      = [];

    ns.exfeeIdentified   = {};

    ns.exfeeSelected     = {};

    ns.completing        = {};


    ns.make = function(domId, curExfee, curEditable)
    {
        var strHtml = '<div id="' + domId + '_exfeegadget_infoarea">'
                    +     '<img id="' + domId + '_exfeegadget_info_label">'
                    +     '<div id="' + domId + '_exfeegadget_info">'
                    +         '<span id="' + domId + '_exfeegadget_num_accepted">'
                    +         '</span>'
                    +         '<span id="' + domId + '_exfeegadget_num_summary">'
                    +         '</span>'
                    +     '</div>'
                    + '</div>'
                    + '<div id="' + domId + '_exfeegadget_inputarea">'
                    +     '<input  id="' + domId + '_exfeegadget_inputbox" type="text">'
                    +     '<button id="' + domId + '_exfeegadget_addbtn">+</button>'
                    +     '<div id="' + domId + '_exfeegadget_autocomplete">'
                    +         '<ol></ol>'
                    +     '</div>'
                    + '</div>'
                    + '<div id="' + domId + '_exfeegadget_listarea" class="exfeegadget_listarea">'
                    +     '<ul></ul>'
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
    };


    ns.keydownInputbox = function(event)
    {
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


    ns.eventAddbutton = function(event)
    {
        var domId = event.target.id.split('_')[0];
        if (event.type === 'click'
        || (event.type === 'keydown' && event.which === 13)) {
            odof.exfee.gadget.chkInput(domId, true);
        }
    };


    ns.eventCompleteItem = function(event)
    {
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
                            .external_identity.toLowerCase() === identity) {
                        odof.exfee.gadget.addExfee(
                            domId, [odof.exfee.gadget.exfeeAvailable[i]]
                        );
                        odof.exfee.gadget.displayComplete(domId, false);
                        break;
                    }
                }
        }
    };


    ns.selectCompleteResult = function(domId, identity)
    {
        var strBaseId = '#' + domId + '_exfeegadget_autocomplete > ol > li',
            className = 'autocomplete_selected';
        $(strBaseId).removeClass(className);
        $(strBaseId + '[identity="' + identity + '"]').addClass(className);
    };


    ns.addExfee = function(domId, exfees)
    {
        for (var i in exfees) {
            var objExfee    = typeof exfees[i].external_identity === 'undefined'
                            ? {avatar_file_name  : 'default.png',
                               bio               : '',
                               external_identity : exfees[i].id,
                               name              : exfees[i].name}
                            : exfees[i],
                keyIdentity = objExfee.external_identity.toLowerCase();
            if (typeof this.exfeeInput[domId][keyIdentity] !== 'undefined') {
                continue;
            }
            for (var j in this.exfeeAvailable) {
                if (this.exfeeAvailable[j].external_identity.toLowerCase()
                === keyIdentity) {
                    objExfee = odof.util.clone(this.exfeeAvailable[j]);
                    break;
                }
            }
            $('#' + domId + '_exfeegadget_listarea > ul').append(
                '<li identity="' + keyIdentity + '">'
              +     '<img src="' + odof.comm.func.getUserAvatar(
                    objExfee.avatar_file_name, 80, img_url)
              +     '" class="exfee_avatar">'
              +     '<span class="exfee_name">'
              +         objExfee.name
              +     '</span>'
              +     '<span class="exfee_identity">'
              +         objExfee.external_identity
              +     '</span>'
              + '</li>'
            );
            this.exfeeInput[domId][keyIdentity] = objExfee;
        }
        this.ajaxIdentity(exfees);
    };


    ns.delExfee = function(domId)
    {
        for (var i in this.exfeeSelected[domId]) {
            var keyIdentity = this.exfeeSelected[domId][i].toLowerCase();
            if (typeof this.exfeeInput[domId][keyIdentity] === 'undefined') {
                continue;
            }
            $('#' + domId + '_exfeegadget_listarea > ul > li[identity="'
                  + keyIdentity + '"]').remove();
            delete this.exfeeInput[domId][keyIdentity];
        }
    };


    ns.chkInput = function(domId, force)
    {
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
            if (item.type !== 'unknow'
                && (parseInt(i) < arrInput.length - 1 || force)) {
                arrValid.push(item);
            } else {
                arrInValid.push(item.id);
            }
        }
        var newInput = arrInValid.join('; ');
        if (newInput !== strInput) {
            objInput.val(newInput);
        }
        odof.exfee.gadget.addExfee(domId, arrValid);
        odof.exfee.gadget.chkComplete(domId, arrInValid.pop());
    };


    ns.chkComplete = function(domId, key)
    {
        var arrCatched = [];
        key = odof.util.trim(key).toLowerCase();
        for (var i in this.exfeeAvailable) {
            if (this.exfeeAvailable[i].name.indexOf(key) !== -1
             || this.exfeeAvailable[i].external_identity.indexOf(key) !== -1) {
                arrCatched.push(odof.util.clone(this.exfeeAvailable[i]));
            }
        }
        this.showComplete(domId, key, arrCatched);
        this.ajaxComplete(domId, key);
    };


    ns.showComplete = function(domId, key, exfee)
    {
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
                                exfee[i].avatar_file_name, 80, img_url) + '">'
                          +     '<span class="exfee_name">'
                          +         exfee[i].name
                          +     '</span>'
                          +     '<span class="exfee_identity">'
                          +         exfee[i].external_identity
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


    ns.ajaxIdentity = function(identities)
    {
        for (var i in identities) {
            if (typeof identities[i].external_identity !== 'undefined') {
                identities[i] = {id   : identities[i].external_identity,
                                 name : identities[i].name,
                                 type : 'email'};
            }
            if (typeof this.exfeeIdentified[identities[i].id.toLowerCase()]
            !== 'undefined') {
                delete identities[i];
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
                    var curId    = data.response.identities[i]
                                       .external_identity.toLowerCase(),
                        objExfee = $(
                            '.exfeegadget_listarea > ul > li[identity="' + curId + '"]'
                        );
                    if (objExfee.length) {
                        objExfee.children('.exfee_avatar').attr(
                            'src', odof.comm.func.getUserAvatar(
                            data.response.identities[i].avatar_file_name,
                            80, img_url)
                        );
                        objExfee.children('.exfee_name').html(
                            data.response.identities[i].name
                        );
                        objExfee.children('.exfee_identity').html(
                            data.response.identities[i].external_identity
                        );
                    }
                }
                odof.exfee.gadget.cacheExfee(data.response.identities);
            }
        });
    };


    ns.cacheExfee = function(exfees, noIdentity) // @todo: temp noIdentity
    {
        for (var i in exfees) {
            var curIdentity = exfees[i].external_identity.toLowerCase();
            for (var j in this.exfeeAvailable) {
                if (this.exfeeAvailable[j].external_identity.toLowerCase()
                === curIdentity) {
                    this.exfeeAvailable.splice(i, 1);
                }
            }
            this.exfeeAvailable.unshift(odof.util.clone(exfees[i]));
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


    ns.ajaxComplete = function(domId, key)
    {
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
                                 name              : item.join(' ')},
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


    ns.displayComplete = function(domId, display)
    {
        this.completing[domId] = display;
        var objCompleteBox = $('#' + domId + '_exfeegadget_autocomplete');
        if (display) {
            objCompleteBox.slideDown(50);
        } else {
            objCompleteBox.slideUp(50);
        }
    };

})(ns);


$(document).ready(function() {
    odof.exfee.gadget.make('test', [], true);
});



































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
     * get exfee editing result
     * by Leask
     * */
    ns.getexfee = function() {
        var result = [];
        function collect(obj, exist)
        {
            var exfee_identity = $(obj).attr('identity'),
                element_id     = $(obj).attr('id'),
                item           = {exfee_name     : $(obj).attr('identityname'),
                                  exfee_identity : exfee_identity,
                                  confirmed      : parseInt($('#' + element_id + ' > .cs > em')[0].className.substr(1)),
                                  identity_type  : odof.util.parseId(exfee_identity).type};
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