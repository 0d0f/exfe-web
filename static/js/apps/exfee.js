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

    ns.exfeeSelected     = {};

    ns.completing        = false;


    ns.make = function(domId, curExfee, editable)
    {
        this.id     = domId;
        var strHtml = '<div id="' + this.id + '_exfeegadget_inputarea">'
                    +     '<input id="' + this.id + '_exfeegadget_inputbox" type="text">'
                    +     '<button>+</button>'
                    +     '<div id="' + this.id + '_exfeegadget_autocomplete">'
                    +         '<ol></ol>'
                    +     '</div>'
                    + '</div>'
                    + '<div id="' + this.id + '_exfeegadget_listarea">'
                    +     '<ul></ul>'
                    + '</div>';
        $('#' + this.id).html(strHtml);
        this.addExfee(curExfee);
        if (!editable) {
            return;
        }
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
        this.completimer = setInterval(odof.exfee.gadget.chkInput, 50);
        $('#' + this.id + '_exfeegadget_inputbox').bind(
            'keydown', this.keydownInputbox
        );
        $('#' + this.id + '_exfeegadget_autocomplete > ol > li').live(
            'mousemove click', this.eventCompleteItem
        );
    };


    ns.keydownInputbox = function(event)
    {
        switch (event.which) {
            case 40:
                if (!odof.exfee.gadget.completing) {
                    return;
                }
                odof.exfee.gadget.selectCompleteResult(
                    $('#' + odof.exfee.gadget.id
                    + '_exfeegadget_autocomplete > ol > li:first').attr('identity')
                );
        }

        //this.
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


    ns.addExfee = function(exfee)
    {
        for (var i in exfee) {
            var objExfee    = typeof exfee[i].external_identity === 'undefined'
                            ? {avatar_file_name  : 'default.png',
                               bio               : '',
                               external_identity : exfee[i].id,
                               name              : exfee[i].name}
                            : exfee[i],
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
              +     '/80_80_' + objExfee.avatar_file_name + '">'
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
    };


    ns.delExfee = function()
    {
        for (var i in this.exfeeSelected) {
            var keyIdentity = exfee[i].toLowerCase();
            if (typeof this.exfeeInput[keyIdentity] === 'undefined') {
                continue;
            }
            $('#' + this.id + '_exfeegadget_listarea > ul > li[identity="' + keyIdentity + '"]').remove();
            delete this.exfeeInput[keyIdentity];
        }
    };


    ns.complete = function()
    {

    };


    ns.input = function()
    {
        ffff
    };


    ns.chkInput = function()
    {
        var objInput   = $('#' + odof.exfee.gadget.id + '_exfeegadget_inputbox'),
            strInput   = objInput.val(),
            arrInput   = strInput.split(/,|;|\r|\n|\t/),
            arrValid   = [],
            arrInValid = [];
        if (odof.exfee.gadget.inputed === strInput) {
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
            if (item.type !== 'unknow' && parseInt(i) < arrInput.length - 1) {
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
        $.ajax({
            type     : 'GET',
            url      : site_url + '/identity/get?identities=' + JSON.stringify(identities),
            dataType : 'json',
            success  : function(data) {
                console.log(data.response.identities);
            }
        });
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
            url      : site_url + '/identity/complete?key=' + key,
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
                        exist = false;
                    for (var j in odof.exfee.gadget.exfeeAvailable) {
                        if (odof.exfee.gadget.exfeeAvailable[j].external_identity
                        === user.external_identity) {
                            exist = true;
                            break;
                        }
                    }
                    if (!exist) {
                        odof.exfee.gadget.exfeeAvailable.unshift(user);
                        gotExfee.push(user);
                    }
                }
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
