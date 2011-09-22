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
                $('#profile_avatar').html("<img class=big_header src='/eimgs/80_80_"+name+"?"+Timer.getTime()+"'/>");
                //<div id="profile_avatar"><img class="big_header" src=
                }
            }
        });
    };
})(ns);

$(document).ready(function(){

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

    $('#changeavatar').click(function(e) {
        var AWnd=window.open('/s/uploadavatar','fwId','resizable=yes,scrollbars=yes,width=600,height=600');
        AWnd.focus();
    });

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

});
