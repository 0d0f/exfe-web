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

    ns.id                = '';

    ns.inputed           = '';

    ns.exfeeInput        = {};

    ns.exfeeAvailable    = [];

    ns.completimer       = null;

    ns.keyComplete       = '';

    ns.curComplete       = {};

    ns.exfeeChecked      = [];

    ns.exfeeIdentified   = {};

    ns.exfeeSelected     = {};

    ns.completing        = false;


    ns.make = function(domId, curExfee, editable)
    {
        this.id     = domId;
        var strHtml = '<div id="' + this.id + '_exfeegadget_inputarea">'
                    +     '<input  id="' + this.id + '_exfeegadget_inputbox" type="text">'
                    +     '<button id="' + this.id + '_exfeegadget_addbtn">+</button>'
                    +     '<div id="' + this.id + '_exfeegadget_autocomplete">'
                    +         '<ol></ol>'
                    +     '</div>'
                    + '</div>'
                    + '<div id="' + this.id + '_exfeegadget_listarea">'
                    +     '<ul></ul>'
                    + '</div>';
        $('#' + this.id).html(strHtml);
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
        this.addExfee(curExfee);
        if (!editable) {
            return;
        }
        this.completimer = setInterval(odof.exfee.gadget.chkInput, 50);
        $('#' + this.id + '_exfeegadget_inputbox').bind(
            'keydown', this.keydownInputbox
        );
        $('#' + this.id + '_exfeegadget_addbtn').bind(
            'keydown click', this.eventAddbutton
        );
        $('#' + this.id + '_exfeegadget_autocomplete > ol > li').live(
            'mousemove click', this.eventCompleteItem
        );
    };


    ns.keydownInputbox = function(event)
    {
        switch (event.which) {
            case 9:  // tab
            case 13: // enter
                odof.exfee.gadget.chkInput(true);
                break;
            case 40: // down
                if (!odof.exfee.gadget.completing) {
                    return;
                }
                odof.exfee.gadget.selectCompleteResult(
                    $('#' + odof.exfee.gadget.id
                    + '_exfeegadget_autocomplete > ol > li:first').attr('identity')
                );
        }
    };


    ns.eventAddbutton = function(event)
    {
        if (event.type === 'click'
        || (event.type === 'keydown' && event.which === 13)) {
            odof.exfee.gadget.chkInput(true);
        }
    };


    ns.eventCompleteItem = function(event)
    {
        var objEvent = event.target;
        while (!$(objEvent).hasClass('autocomplete_item')) {
            objEvent = objEvent.parentNode;
        }
        var identity = $(objEvent).attr('identity');
        if (!identity) {
            return;
        }
        switch (event.type) {
            case 'mousemove':
                odof.exfee.gadget.selectCompleteResult(identity);
                break;
            case 'click':
                for (var i in odof.exfee.gadget.exfeeAvailable) {
                    if (odof.exfee.gadget.exfeeAvailable[i]
                            .external_identity.toLowerCase() === identity) {
                        odof.exfee.gadget.addExfee([
                            odof.exfee.gadget.exfeeAvailable[i]
                        ]);
                        odof.exfee.gadget.displayComplete(false);
                        break;
                    }
                }
        }
    };


    ns.selectCompleteResult = function(identity)
    {
        var strBaseId = '#' + this.id + '_exfeegadget_autocomplete > ol > li',
            className = 'autocomplete_selected';
        $(strBaseId).removeClass(className);
        $(strBaseId + '[identity="' + identity + '"]').addClass(className);
    };


    ns.addExfee = function(exfees)
    {
        for (var i in exfees) {
            var objExfee    = typeof exfees[i].external_identity === 'undefined'
                            ? {avatar_file_name  : 'default.png',
                               bio               : '',
                               external_identity : exfees[i].id,
                               name              : exfees[i].name}
                            : exfees[i],
                keyIdentity = objExfee.external_identity.toLowerCase();
            if (typeof this.exfeeInput[keyIdentity] !== 'undefined') {
                continue;
            }
            for (var j in this.exfeeAvailable) {
                if (this.exfeeAvailable[j].external_identity.toLowerCase()
                === keyIdentity) {
                    objExfee = odof.util.clone(this.exfeeAvailable[j]);
                    break;
                }
            }
            $('#' + this.id + '_exfeegadget_listarea > ul').append(
                '<li identity="' + keyIdentity + '">'
              +     '<img src="' + odof.comm.func.getHashFilePath(
                    img_url,    objExfee.avatar_file_name)
              +     '/80_80_' + objExfee.avatar_file_name
              +     '" class="exfee_avatar">'
              +     '<span class="exfee_name">'
              +         objExfee.name
              +     '</span>'
              +     '<span class="exfee_identity">'
              +         objExfee.external_identity
              +     '</span>'
              + '</li>'
            );
            this.exfeeInput[keyIdentity] = objExfee;
        }
        this.ajaxIdentity(exfees);
    };


    ns.delExfee = function()
    {
        for (var i in this.exfeeSelected) {
            var keyIdentity = exfee[i].toLowerCase();
            if (typeof this.exfeeInput[keyIdentity] === 'undefined') {
                continue;
            }
            $('#' + this.id + '_exfeegadget_listarea > ul > li[identity="'
                  + keyIdentity + '"]').remove();
            delete this.exfeeInput[keyIdentity];
        }
    };


    ns.chkInput = function(force)
    {
        var objInput   = $('#' + odof.exfee.gadget.id + '_exfeegadget_inputbox'),
            strInput   = objInput.val(),
            arrInput   = strInput.split(/,|;|\r|\n|\t/),
            arrValid   = [],
            arrInValid = [];
        if (odof.exfee.gadget.inputed === strInput && !force) {
            return;
        } else {
            odof.exfee.gadget.inputed  =  strInput;
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
        odof.exfee.gadget.addExfee(arrValid);
        odof.exfee.gadget.chkComplete(arrInValid.pop());
    };


    ns.chkComplete = function(key)
    {
        var arrCatched = [];
        key = odof.util.trim(key).toLowerCase();
        for (var i in this.exfeeAvailable) {
            if (this.exfeeAvailable[i].name.indexOf(key) !== -1
             || this.exfeeAvailable[i].external_identity.indexOf(key) !== -1) {
                arrCatched.push(odof.util.clone(this.exfeeAvailable[i]));
            }
        }
        this.showComplete(key, arrCatched);
        this.ajaxComplete(key);
    };


    ns.showComplete = function(key, exfee)
    {
        var baseId          = '#' + this.id + '_exfeegadget_autocomplete > ol',
            objAutoComplete = $(baseId),
            strItems        = '';
        if (this.keyComplete !== key) {
            this.curComplete = {};
            objAutoComplete.html('');
        }
        this.keyComplete = key;
        if (exfee && exfee.length) {
            for (var i in exfee) {
                var curIdentity = exfee[i].external_identity.toLowerCase();
                if (typeof this.curComplete[curIdentity] !== 'undefined') {
                    continue;
                }
                this.curComplete[curIdentity] = true;
                strItems += '<li identity="' + curIdentity + '" '
                          +     'class="autocomplete_item">'
                          +     '<img src="' + odof.comm.func.getHashFilePath(
                                img_url,    exfee[i].avatar_file_name)
                          +     '/80_80_' + exfee[i].avatar_file_name + '">'
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
            this.selectCompleteResult($(baseId + ' > li:first').attr('identity'));
        }
        this.displayComplete(key && odof.util.count(this.curComplete));
    };


    ns.ajaxIdentity = function(identities)
    {
        for (var i in identities) {
            if (typeof this.exfeeIdentified[identities[i].id.toLowerCase()]
            !== 'undefined') {
                delete identities[i];
            }
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
                            '#' + this.id
                                + '_exfeegadget_listarea > ul > li[identity="'
                                + curId + '"]'
                        );
                    if (objExfee.length) {
                        objExfee.child('.exfee_avatar').attr(
                            'src', odof.comm.func.getHashFilePath(
                                img_url,
                                data.response.identities[i].avatar_file_name
                            ) + '/80_80_'
                              + data.response.identities[i].avatar_file_name
                        );
                        objExfee.child('.exfee_name').html(
                            data.response.identities[i].name
                        );
                        objExfee.child('.exfee_identity').html(
                            data.response.identities[i].external_identity
                        );
                    }
                }
                odof.exfee.gadget.cacheExfee(data.response.identities);
            }
        });
    };


    ns.cacheExfee = function(exfees)
    {
        for (var i in exfees) {
            var curIdentity = exfees[i].external_identity.toLowerCase();
            for (var j in this.exfeeAvailable) {
                if (this.exfeeAvailable[j].external_identity.toLowerCase()
                === curIdentity) {
                    delete this.exfeeAvailable[j];
                }
            }
            this.exfeeAvailable.unshift(odof.util.clone(exfees[i]));
            this.exfeeIdentified[curIdentity] = true;
        }
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem(this.exfeeAvailableKey,
                                 JSON.stringify(this.exfeeAvailable));
        }
    };


    ns.ajaxComplete = function(key)
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
            key      : key,
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
                odof.exfee.gadget.cacheExfee(gotExfee);
                if (this.key === odof.exfee.gadget.keyComplete) {
                    odof.exfee.gadget.showComplete(this.key, gotExfee);
                }
            }
        });
    };


    ns.displayComplete = function(display)
    {
        this.completing = display;
        var objCompleteBox = $('#' + this.id + '_exfeegadget_autocomplete');
        if (display) {
            objCompleteBox.slideDown(50);
        } else {
            objCompleteBox.slideUp(50);
        }
    };

})(ns);


$(document).ready(function() {
    odof.exfee.gadget.make('test', {}, true);
});
