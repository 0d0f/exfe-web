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

//    ns.cross_id = cross_id;
//    ns.btn_val = null;
//    ns.token = token;
//    ns.location_uri = location_uri;

    ns.exfeeAvailableKey = 'exfee_available';

    ns.id                = '';

    ns.inputed           = '';

    ns.exfeeInput        = {};

    ns.exfeeAvailable    = [];

    ns.completimer       = null;

    ns.keyComplete       = '';

    ns.curComplete       = {};

    ns.exfeeChecked      = {};


    ns.make = function(domId, curExfee, editable)
    {
        this.id     = domId;
        this.exfee  = odof.util.clone(curExfee);
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
    };





    ns.addExfee = function()
    {

    };


    ns.delExfee = function()
    {

    };


    ns.drawExfee = function(exfee)
    {

    };


    ns.complete = function()
    {

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
            var item = odof.util.parseId(arrInput[i]);
            if (item.type !== 'unknow' && parseInt(i) < arrInput.length - 1) {
                arrValid.push(item);
            } else {
                arrInValid.push(item.id);
            }
        }
        objInput.val(arrInValid.join('; '));
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
        var idAutoComplete  = this.id + '_exfeegadget_autocomplete',
            objAutoComplete = $('#' + idAutoComplete),
            strItems = objAutoComplete.html();
        if (this.keyComplete !== key) {
            strItems = '';
            this.curComplete = {};
        }
        this.keyComplete = key;
        if (exfee && exfee.length) {
            for (var i in exfee) {
                var curIdentity = exfee[i].external_identity.toLowerCase();
                if (typeof this.curComplete[curIdentity] !== 'undefined') {
                    console.log('mm');
                    continue;
                }
                this.curComplete[curIdentity] = true;
                strItems += '<li identity="' + exfee[i].external_identity + '">'
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
        objAutoComplete.html(strItems);
        this.displayComplete(key && strItems);
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
        if (typeof this.exfeeChecked[key] !== 'undefined' || !key.length) {
            return;
        }
        this.exfeeChecked[key] = true;
        $.ajax({
            type     : 'GET',
            url      : site_url + '/identity/complete?key=' + key,
            key      : key,
            dataType : 'json',
            success  : function(data) {
                var gotExfee = [];
                for (var i in data) {
                    var item  = odof.util.trim(data[i]).split(' '),
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
