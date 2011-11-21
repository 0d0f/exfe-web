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
                console.log(data);
            }
        });
    };


    ns.getInvitation = function() {
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getinvitation',
            dataType : 'json',
            success  : function(data) {
                console.log(data);
            }
        });
    };


    ns.getUpdate = function() {
        $.ajax({
            type     : 'GET',
            url      : site_url + '/s/getupdate',
            dataType : 'json',
            success  : function(data) {
                console.log(data);
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
