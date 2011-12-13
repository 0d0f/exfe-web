/*
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

    ns.exfee = {};

    ns.inputed = '';

    ns.completimer = null;


    ns.make = function(id, editable)
    {
        var strHtml = '<div id="' + id + '_exfeegadget_inputarea">'
                    +     '<input type="text">'
                    +     '<button>+</button>'
                    +     '<div id="' + id + '_exfeegadget_autocomplete">'
                    +         '<ol></ol>'
                    +     '</div>'
                    + '</div>'
                    + '<div id="' + id + '_exfeegadget_listarea">'
                    +     '<ul></ul>'
                    + '</div>';
        $('#' + id).html(strHtml);
        this.completimer = setInterval(odof.exfee.gadget.chkComplete, 50);
    };


    ns.addExfee = function()
    {

    };


    ns.delExfee = function()
    {

    };


    ns.complete = function()
    {

    };


    ns.chkComplete = function(strKey)
    {

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
