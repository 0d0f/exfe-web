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

    ns.id;

    ns.exfee = {};

    ns.exfeeAvailable = {};

    ns.inputed = '';

    ns.completimer = null;


    ns.make = function(domId, curExfee, editable)
    {
        this.id     = domId;
        this.exfee  = odof.util.clone(curExfee);
        var strHtml = '<div id="' + this.id + '_exfeegadget_inputarea">'
                    +     '<input type="text">'
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

        //this.completimer = setInterval(odof.exfee.gadget.chkComplete, 50);
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


    ns.chkComplete = function(strKey)
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
    };


    ns.slideExfee = function(action)
    {
        switch (action) {
            case 'show':
                break;
            case 'hide':

        }
    };

})(ns);


// odof.exfee.gadget.make('test');
