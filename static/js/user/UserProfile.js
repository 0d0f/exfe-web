var moduleNameSpace = "odof.user.profile";
var ns = odof.util.initNameSpace(moduleNameSpace);


(function(ns){

    ns.saveUsername = function(name) {
        var poststr="name="+name;
        $.ajax({
        type: "POST",
        data: poststr,
        url: site_url+"/s/SaveUserIdentity",
        dataType:"json",
        success: function(data){
            if(data.response.user!=null)
            {
                var name=data.response.identity.name;
                $('#profile_name').html(name);
            }
        }
        });
    };


    ns.updateavatar = function(name) {
        $.ajax({
            type: "GET",
            url: site_url+"/s/GetUserProfile",
            dataType:"json",
            success: function(data){
                if(data.response.user!=null)
                {
                var name=data.response.user.avatar_file_name;
                var Timer=new Date();
                $('#profile_avatar').html("<img class=big_header src='"+odof.comm.func.getHashFilePath(img_url,name)+"/80_80_"+name+"?"+Timer.getTime()+"'/>");
                //<div id="profile_avatar"><img class="big_header" src=
                }
            }
        });
    };


    ns.sentActiveEmail=function(external_identity,button) {
        var poststr="identity="+external_identity;
        $.ajax({
        type: "POST",
        data: poststr,
        url: site_url+"/s/sendActiveEmail",
        dataType:"json",
        success: function(JSONData){
            if(!JSONData.error)
            {
                button.text("sent");
                button.unbind("click");
            }
        }
        });

    };


    ns.getCross = function() {
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getcross',
            dataType : 'json',
            success  : function(data) {
                console.log(data);
            }
        });
    };


    ns.getCross = function() {
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getcross',
            dataType : 'json',
            success  : function(data) {
                if (data && (data.error || data.length === 0)) {
                    return;
                }
                var crosses = {};
                $('#cross_list > .category').hide();
                for (var i in data) {
                    var confirmed = [];
                    for (var j in data[i].exfee) {
                        if (parseInt(data[i].exfee[j].rsvp) === 1) {
                            confirmed.push(data[i].exfee[j].name);
                        }
                    }
                    if (confirmed.length) {
                        confirmed = confirmed.length+' of '+data[i].exfee.length
                                  + ' confirmed: '  + confirmed.join(', ');
                    } else {
                        confirmed = '0 of '+data[i].exfee.length+' confirmed';
                    }
                    var strCross = '<a class="cross_link x_' + data[i]['sort'] + '" href="/!' + data[i]['base62id'] + '">'
                                 +     '<div class="cross">'
                                 +         '<h5>' + data[i]['title'] + '</h5>'
                                 +         '<p>' + data[i]['begin_at'] + '</p>'
                                 +         '<p>' + data[i]['place_line1'] + (data[i]['place_line2'] ? (' <span>(' + data[i]['place_line2'] + ')</span>') : '') + '</p>'
                                 +         '<p>' + confirmed + '</p>'
                                 +     '</div>'
                                 + '</a>';
                    if (!crosses[data[i]['sort']]) {
                        crosses[data[i]['sort']] = '';
                    }
                    crosses[data[i]['sort']] += strCross;
                }
                for (i in crosses) {
                    $('#xType_' + i + ' > .crosses').html(crosses[i]);
                    if (crosses[i]) {
                        $('#xType_' + i).show();
                    }
                }
            }
        });
    };


    ns.getInvitation = function() {
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getinvitation',
            dataType : 'json',
            success  : function(data) {
                if (data && (data.error || data.length === 0)) {
                    return;
                }
                var strInvt = '';
                for (var i in data) {
                    strInvt += '<div id="cross_invitation_' + data[i]['base62id'] + '" class="bnone">'
                             +     '<h5><a href="/!' + data[i]['base62id'] + '">' + data[i]['cross']['title'] + '</a></h5>'
                             +     '<p>' + data[i]['cross']['begin_at'] + ' by ' + data[i]['sender']['name'] + '</p>'
                             +     '<button type="button" id="acpbtn_' + data[i]['base62id'] + '" class="acpbtn">Accept</button>'
                             + '</div>';
                }
                $('#invitations > .crosses').html(strInvt);
            }
        });
    };


    ns.getUpdate = function() {
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getupdate',
            dataType : 'json',
            success  : function(data) {
                if (data && (data.error || data.length === 0)) {
                    return;
                }
                var strLogs = '';
                for (var i in data) {
                    var j, arrExfee;
                    strLogs += '<a class="cross_link" href="/!' + data[i]['base62id'] + '">'
                             +     '<div class="cross">'
                             +         '<h5>' + data[i]['title'] + '</h5>';
                    if (data[i]['change']) {
                        if (data[i]['change']['begin_at']) {
                            strLogs += '<p class="clock"><span>' + data[i]['change']['begin_at']['new_value'] + '</span></p>';
                        }
                        if (data[i]['change']['place']) {
                            strLogs += '<p class="place"><span>' + data[i]['change']['place']['new_value'] + '</span></p>';
                        }
                        if (data[i]['change']['title']
                         && data[i]['change']['title']['old_value']) {
                            strLogs += '<p class="title">Title changed from: <span>' + data[i]['change']['title']['old_value'] + '</span></p>';
                        }
                    }
                    if (data[i]['confirmed']) {
                        arrExfee = [];
                        for (j in data[i]['confirmed']) {
                            if (arrExfee.push(
                                    data[i]['confirmed'][j]['to_name']
                                ) === 3) {
                                break;
                            }
                        }
                        strLogs += '<p class="confirmed"><span>'
                                 + data[i]['confirmed'].length
                                 + '</span> confirmed: <span>'
                                 + arrExfee.join('</span>, <span>') + '</span>'
                                 + (arrExfee.length < data[i]['confirmed'].length ? ' and others' : '')
                                 + '.</p>';
                    }
                    if (data[i]['declined']) {
                        arrExfee = [];
                        for (j in data[i]['declined']) {
                            if (arrExfee.push(
                                    data[i]['declined'][j]['to_name']
                                ) === 3) {
                                break;
                            }
                        }
                        strLogs += '<p class="declined"><span>'
                                 + data[i]['declined'].length
                                 + '</span> declined: <span>'
                                 + arrExfee.join('</span>, <span>') + '</span>'
                                 + (arrExfee.length < data[i]['declined'].length ? ' and others' : '')
                                 + '.</p>';
                    }
                    if (data[i]['addexfe']) {
                        arrExfee = [];
                        for (j in data[i]['addexfe']) {
                            if (arrExfee.push(
                                    data[i]['addexfe'][j]['to_name']
                                ) === 3) {
                                break;
                            }
                        }
                        strLogs += '<p class="invited"><span>'
                                 + data[i]['addexfe'].length
                                 + '</span> invited: <span>'
                                 + arrExfee.join('</span>, <span>') + '</span>'
                                 + (arrExfee.length < data[i]['addexfe'].length ? ' and others' : '')
                                 + '.</p>';
                    }
                    if (data[i]['conversation']) {
                        strLogs += '<p class="conversation"><span>'
                                 + data[i]['conversation']['by_name']
                                 + '</span>: '
                                 + data[i]['conversation']['message'] + '</p>';
                    }
                    strLogs += '</div></a>'
                }
                $('#recently_updates > .crosses').html(strLogs);
            }
        });
    };

})(ns);


$(document).ready(function() {

    $('#editprofile').click(function(e) {
        if($('#profile_name').attr("status")=='view')
        {
            $('#profile_name').html("<input id='edit_profile_name' value='"+$('#profile_name').html()+"'>");
            $('#profile_name').attr("status","edit");
            $('#changeavatar').show();
        }
        else
        {
            var name_val=$("#edit_profile_name").val();
            $('#profile_name').html(name_val);
            odof.user.profile.saveUsername(name_val);
            $('#profile_name').attr("status","view");
            $('#changeavatar').hide();
        }
    });

    $('.sendactiveemail').click(function(e) {
        var external_identity=$(this).attr("external_identity");
        odof.user.profile.sentActiveEmail(external_identity,$(this));
    });
    /*
    $('#changeavatar').click(function(e) {
        var AWnd=window.open('/s/uploadavatar','fwId','resizable=yes,scrollbars=yes,width=600,height=600');
        AWnd.focus();
    });
    */

    $('.p_right').click(function(e) {
        var strXType = e.target.id.split('_')[1],
            objArrow = null;
        if ((objArrow = $('#' + e.target.id + ' > .arrow')).length) {
            objArrow.removeClass('arrow').addClass('arrow_up');
            $('.x_' + strXType).hide();
        } else if ((objArrow = $('#' + e.target.id + ' > .arrow_up')).length) {
            objArrow.removeClass('arrow_up').addClass('arrow');
            $('.x_' + strXType).show();
        }
    });

    document.title = 'EXFE - ' + $('#profile_name').html();

    $('.bnone').bind('mousemove mouseout', function(event) {
        var objEvent = event.target;
        while (objEvent.id.split('_').shift() !== 'cross') {
            objEvent = objEvent.parentNode;
        }
        var objBtn   = $('#' + objEvent.id + ' > dd > .acpbtn');
        switch (event.type) {
            case 'mousemove':
                objBtn.show();
                break;
            case 'mouseout':
                objBtn.hide();
        }
    });

    $('.acpbtn').hide();
    $('.acpbtn').click(function(e) {
        location.href = '/rsvp/accept?xid=' + e.target.id.split('_')[1];
    });

    odof.user.profile.getCross();
    odof.user.profile.getInvitation();
    odof.user.profile.getUpdate();

});
