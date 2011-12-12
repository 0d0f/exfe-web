var moduleNameSpace = 'odof.user.profile';
var ns = odof.util.initNameSpace(moduleNameSpace);


(function(ns){

    ns.strLsKey = 'profile_cross_fetchArgs';


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
                }
            }
        });
    };


    ns.sentActiveEmail=function(external_identity,button) {
        var poststr="identity="+external_identity;
        $.ajax({
        type: "POST",
        data: poststr,
        url: site_url+"/s/sendActiveMail",
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


    ns.makeCross = function(data) {
        var result = {crosses : {}, more : {}};
        for (var i in data) {
            if (!result['crosses'][data[i]['sort']]) {
                result['crosses'][data[i]['sort']] = '';
                result['more'][data[i]['sort']] = 0;
            }
            if (data[i]['more']) {
                result['more'][data[i]['sort']]++;
                continue;
            }
            var confirmed = [];
            for (var j in data[i].exfee) {
                if (parseInt(data[i].exfee[j].rsvp) === 1) {
                    confirmed.push(data[i].exfee[j].name);
                }
            }
            if (confirmed.length) {
                confirmed = confirmed.length + ' of ' + data[i].exfee.length
                          + ' confirmed: '   + confirmed.join(', ');
            } else {
                confirmed = '0 of ' + data[i].exfee.length + ' confirmed';
            }
            var strCross = '<a id="past_cross_' + data[i]['id'] + '" class="cross_link x_' + data[i]['sort'] + '" href="/!' + data[i]['base62id'] + '">'
                         +     '<div class="cross">'
                         +         '<h5>' + data[i]['title'] + '</h5>'
                         +         '<p>' + data[i]['begin_at'] + '</p>'
                         +         '<p>' + data[i]['place_line1'] + (data[i]['place_line2'] ? (' <span>(' + data[i]['place_line2'] + ')</span>') : '') + '</p>'
                         +         '<p>' + confirmed + '</p>'
                         +     '</div>'
                         + '</a>';
            result['crosses'][data[i]['sort']] += strCross;
        }
        return result;
    };


    ns.getCross = function() {
        var fetchArgs = null;
        if (typeof localStorage !== 'undefined') {
            fetchArgs = localStorage.getItem(odof.user.profile.strLsKey);
            try {
                fetchArgs = JSON.parse(fetchArgs);
            } catch (err) {
                fetchArgs = {};
            }
        }
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getcross',
            data     : fetchArgs,
            dataType : 'json',
            success  : function(data) {
                if (data && (data.error || data.length === 0)) {
                    return;
                }
                var crosses   = odof.user.profile.makeCross(data),
                    fetchArgs = null;
                $('#cross_list > .category').hide();
                if (typeof localStorage !== 'undefined') {
                    fetchArgs = localStorage.getItem(odof.user.profile.strLsKey);
                    if (fetchArgs) {
                        try {
                            fetchArgs = JSON.parse(fetchArgs);
                        } catch (err) {
                            fetchArgs = {};
                        }
                    } else {
                        fetchArgs = {};
                    }
                }
                for (var i in crosses['crosses']) {
                    var xCtgrId = '#xType_' + i,
                        xListId = xCtgrId + ' > .crosses';
                    $(xListId).html(crosses['crosses'][i]);
                    if (crosses['crosses'][i]) {
                        $(xCtgrId).show();
                    }
                    if (crosses['more'][i]) {
                        if (i === 'past') {
                            endlessScrollAvail = true;
                        } else {
                            $(xCtgrId + ' > .more_or_less_area > .more_or_less').show();
                        }
                    } else {
                        if (i === 'past') {
                            endlessScrollAvail = false;
                        } else {
                            $(xCtgrId + ' > .more_or_less_area > .more_or_less').hide();
                        }
                    }
                    if (typeof fetchArgs[i + '_folded'] !== 'undefined'
                            && fetchArgs[i + '_folded']) {
                        $(xCtgrId + ' > .category_title > .arrow').removeClass('arrow').addClass('arrow_up');
                        $(xListId).hide();
                        $(xCtgrId + ' > .more_or_less_area').hide();
                    }
                }
            }
        });
    };


    ns.rawGetCross = function(strXType) {
        if (!strXType) {
            return;
        }
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getcross',
            data     : {'upcoming_included'  : strXType === 'upcoming',
                        'upcoming_more'      : true,
                        'anytime_included'   : strXType === 'anytime',
                        'anytime_more'       : true,
                        'sevenDays_included' : strXType === 'sevenDays',
                        'sevenDays_more'     : true,
                        'later_included'     : strXType === 'later',
                        'later_more'         : true,
                        'past_included'      : strXType === 'past',
                        'past_more'          : true,
                        'past_quantity'      : $('#xType_past > .crosses > a').length},
            dataType : 'json',
            success  : function(data) {
                if (data && (data.error || data.length === 0)) {
                    return;
                }
                var crosses   = odof.user.profile.makeCross(data);
                for (var i in crosses['crosses']) {
                    var xCtgrId = '#xType_' + i;
                    switch (i) {
                        case 'upcoming':
                        case 'anytime':
                        case 'sevenDays':
                        case 'later':
                            if (crosses['crosses'][i]) {
                                $(xCtgrId + ' > .crosses').html(crosses['crosses'][i]);
                            }
                            if (crosses['more'][i]) {
                                $(xCtgrId + ' > .more_or_less_area > .more_or_less').show();
                            } else {
                                $(xCtgrId + ' > .more_or_less_area > .more_or_less').hide();
                            }
                            break;
                        case 'past':
                            if (crosses['crosses'][i]) {
                                $(xCtgrId + ' > .crosses').append(crosses['crosses'][i]);
                            }
                            if (crosses['more'][i]) {
                                endlessScrollAvail = true;
                            } else {
                                endlessScrollAvail = false;
                            }
                            endlessScrollDoing = false;
                    }
                }
            }
        });
    };


    ns.getMoreCross = function(event) {
        var objEvent = event.target;
        while (!$(objEvent).hasClass('category')) {
            objEvent = objEvent.parentNode;
        }
        ns.rawGetCross(objEvent.id.split('_')[1]);
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
                $('#invitations').hide();
                $('#invitations_shadow').hide();
                for (var i in data) {
                    strInvt += '<div id="cross_invitation_' + data[i]['base62id'] + '" class="invitation cross">'
                             +     '<button type="button" id="accept_button_' + data[i]['base62id'] + '">Accept</button>'
                             +     '<h5><a href="/!' + data[i]['base62id'] + '">' + data[i]['cross']['title'] + '</a></h5>'
                             +     '<p>' + data[i]['cross']['begin_at'] + ' by ' + data[i]['sender']['name'] + '</p>'
                             + '</div>';
                }
                $('#invitations > .crosses').html(strInvt);
                if (strInvt) {
                    $('#invitations').show();
                    $('#invitations_shadow').show();
                }
                $('.invitation > button').hide();
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
                $('#recently_updates').hide();
                $('#recently_updates_shadow').hide();
                for (var i in data) {
                    var j, arrExfee,
                        strLogX = '<a class="cross_link" href="/!' + data[i]['base62id'] + '">'
                                +     '<div class="cross">'
                                +         '<h5>' + data[i]['title'] + '</h5>',
                        numChgs = 0;
                    if (data[i]['change']) {
                        if (data[i]['change']['begin_at']) {
                            strLogX += '<p class="clock"><em>' + data[i]['change']['begin_at']['new_value'] + '</em></p>';
                        }
                        if (data[i]['change']['place']) {
                            strLogX += '<p class="place"><em>' + data[i]['change']['place']['new_value'] + '</em></p>';
                        }
                        if (data[i]['change']['title']
                         && data[i]['change']['title']['old_value']) {
                            strLogX += '<p class="title">Title changed from: <em>' + data[i]['change']['title']['old_value'] + '</em></p>';
                        }
                        numChgs++;
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
                        strLogX += '<p class="confirmed"><em>'
                                 + data[i]['confirmed'].length
                                 + '</em> confirmed: <em>'
                                 + arrExfee.join('</em>, <em>') + '</em>'
                                 + (arrExfee.length
                                 < data[i]['confirmed'].length
                                 ? ' and others' : '')
                                 + '.</p>';
                        numChgs++;
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
                        strLogX += '<p class="declined"><em>'
                                 + data[i]['declined'].length
                                 + '</em> declined: <em>'
                                 + arrExfee.join('</em>, <em>') + '</em>'
                                 + (arrExfee.length
                                 < data[i]['declined'].length
                                 ? ' and others' : '')
                                 + '.</p>';
                        numChgs++;
                    }
                    if (data[i]['addexfee']) {
                        arrExfee = [];
                        for (j in data[i]['addexfe']) {
                            if (arrExfee.push(
                                    data[i]['addexfe'][j]['to_name']
                                ) === 3) {
                                break;
                            }
                        }
                        strLogX += '<p class="invited"><span>'
                                 + data[i]['addexfe'].length
                                 + '</em> invited: <em>'
                                 + arrExfee.join('</em>, <em>') + '</em>'
                                 + (arrExfee.length
                                 < data[i]['addexfe'].length
                                 ? ' and others' : '')
                                 + '.</p>';
                        numChgs++;
                    }
                    if (data[i]['conversation']) {
                        strLogX += '<p class="conversation"><em>'
                                 + data[i]['conversation']['by_name']+'</em>: '
                                 + data[i]['conversation']['message']+'<br><em>'
                                 + data[i]['conversation']['num_msgs']
                                 + '</em> new post in conversation.</p>';
                        numChgs++;
                    }
                    strLogX += '</div></a>';
                    strLogs += numChgs ? strLogX : '';
                }
                $('#recently_updates > .crosses').html(strLogs);
                if (strLogs) {
                    $('#recently_updates').show();
                    $('#recently_updates_shadow').show();
                }
            }
        });
    };


    ns.endlessScroll = function() {
        if (!endlessScrollAvail) {
            endlessScrollDoing = false;
            return;
        }
        var objDoc   = $(document);
        if (objDoc.scrollTop() + odof.util.getClientSize()['height']
        === objDoc.height()) {
            if (endlessScrollDoing) {
                return;
            }
            endlessScrollDoing = true;
            ns.rawGetCross('past');
        }
    };

})(ns);


$(document).ready(function() {

    // by Handaoliang

    $('#editprofile').click(function(e) {
        if($('#profile_name').attr("status")=='view')
        {
            $('#profile_name').html("<input id='edit_profile_name' value='"+$('#profile_name').html()+"'>");
            $('#profile_name').attr("status","edit");
            $('#changeavatar').show();
        } else {
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


    // by Leask Huang

    document.title = 'EXFE - ' + $('#profile_name').html();

    $('.invitation').live('mousemove mouseout', function(event) {
        var objEvent = event.target;
        while (!$(objEvent).hasClass('invitation')) {
            objEvent = objEvent.parentNode;
        }
        var objBtn   = $('#' + objEvent.id + ' > button');
        switch (event.type) {
            case 'mousemove':
                objBtn.show();
                break;
            case 'mouseout':
                objBtn.hide();
        }
    });

    $('.category_title').click(function(event) {
        var objEvent = event.target;
        while (!$(objEvent).hasClass('category')) {
            objEvent = objEvent.parentNode;
        }
        var fetchArgs = null,
            objArrow  = null,
            bolFolded = false,
            strXType  = objEvent.id.split('_')[1];
        if (typeof localStorage !== 'undefined') {
            fetchArgs = localStorage.getItem(odof.user.profile.strLsKey);
            if (fetchArgs) {
                try {
                    fetchArgs = JSON.parse(fetchArgs);
                } catch (err) {
                    fetchArgs = {};
                }
            } else {
                fetchArgs = {};
            }
        }
        if ((objArrow
          = $('#' + objEvent.id + ' > .category_title > .arrow')).length) {
            objArrow.removeClass('arrow').addClass('arrow_up');
            $('#' + objEvent.id + ' > .crosses').hide();
            $('#' + objEvent.id + ' > .more_or_less_area').hide();
            bolFolded = true;
        } else if ((objArrow
          = $('#' + objEvent.id + ' > .category_title > .arrow_up')).length) {
            objArrow.removeClass('arrow_up').addClass('arrow');
            $('#' + objEvent.id + ' > .crosses').show();
            $('#' + objEvent.id + ' > .more_or_less_area').show();
            bolFolded = false;
        }
        if (fetchArgs) {
            fetchArgs[strXType + '_folded'] = bolFolded;
            localStorage.setItem(odof.user.profile.strLsKey,
                                 JSON.stringify(fetchArgs));
        }
    });

    $('.cross').live('mousemove mouseout', function(event) {
        var objEvent = event.target;
        while (!$(objEvent).hasClass('cross')) {
            objEvent = objEvent.parentNode;
        }
        switch (event.type) {
            case 'mousemove':
                $(objEvent).addClass('cross_mouseover');
                break;
            case 'mouseout':
                $(objEvent).removeClass('cross_mouseover');
        }
    });

    $('.invitation > button').live('click', function(e) {
        location.href = '/rsvp/accept?xid=' + e.target.id.split('_')[2];
    });

    $('.more_or_less > a').click(odof.user.profile.getMoreCross);

    window.endlessScrollDoing = false;
    window.endlessScrollAvail = false;
    window.endlessScrollTimer = setInterval('odof.user.profile.endlessScroll()',
                                            500);

    odof.user.profile.getCross();
    odof.user.profile.getInvitation();
    odof.user.profile.getUpdate();

});
