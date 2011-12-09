/*
 * @Description: Exfee Editing Gadget
 * @Author:      Leask Huang <leask@exfe.com>
 * @createDate:  Dec 9, 2011
 * @CopyRights:  http://www.exfe.com
 */


var moduleNameSpace = "odof.exfee.gadget";
var ns = odof.util.initNameSpace(moduleNameSpace);


(function(ns) {

//    ns.cross_id = cross_id;
//    ns.btn_val = null;
//    ns.token = token;
//    ns.location_uri = location_uri;

    ns.make = function(id, editable) {
        var strHtml = '<div id="' + id + '_xxxx">'
                    +     '<input id="' + '">'
                    +     '<button id="">+</button>'
                    + '</div>'
                    + '<ol>' + '</ol>';
        $('#' + id).html(strHtml);
    };

})(ns);


// odof.exfee.gadget.make('test');
