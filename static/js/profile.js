function saveUsername(name)
{
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
}

function updateavatar(name)
{
    $.ajax({
        type: "GET",
        url: site_url+"/s/GetUserProfile", 
        dataType:"json",
        success: function(data){
            if(data.response.user!=null)
            {
            var name=data.response.user.avatar_file_name;
            var Timer=new Date();
            $('#profile_avatar').html("<img class=big_header src='/eimgs/64_64_"+name+"?"+Timer.getTime()+"'/>");
            //<div id="profile_avatar"><img class="big_header" src=
            }
        }
    });
}

$(document).ready(function(){
        $('#editprofile').click(function(e){
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
                saveUsername(name_val);
                $('#profile_name').attr("status","view");
                $('#changeavatar').hide();
            }
            });
        $('#changeavatar').click(function(e){
                var AWnd=window.open('/s/uploadavatar','fwId','resizable=yes,scrollbars=yes,width=600,height=600');
                AWnd.focus();
            });

});

