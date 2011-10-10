/**
 * @Description:    Cross edit module
 * @Author:         HanDaoliang <handaoliang@gmail.com>
 * @createDate:     Sup 15,2011
 * @CopyRights:		http://www.exfe.com
**/

var moduleNameSpace = "odof.cross.edit";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){
    ns.editURI = window.location.href;
    ns.cross_time_bubble_status = 0;
    /**
     * display edit bar
     *
     * */
    ns.showEditBar = function(){
        jQuery("#edit_cross_bar").slideDown(300);
        jQuery("#submit_data").bind("click",function(){
            odof.cross.edit.submitData();
        });
        jQuery("#cross_titles").addClass("enable_click");
        jQuery("#cross_titles").bind("click",function(){
            odof.cross.edit.bindEditTitlesEvent();
        });

        //bind event for cross time container
        jQuery("#cross_times_area").addClass("enable_click");
        jQuery("#cross_times_area").bind("click",function(){
             odof.cross.edit.bindEditTimesEvent();
        });

        jQuery("#cross_desc").show();
        jQuery("#cross_desc_short").hide();

        jQuery("#cross_desc").addClass("enable_click");
        jQuery("#cross_desc").bind("click",function(){
            odof.cross.edit.bindEditDescEvent();
        });


        /////////////////////////////////////////
        $('#exfee_edit').fadeIn();
        //$('#exfee_area').addClass('enable_click');
        //$('#exfee_area').bind('click', function() {
        //    console.log('leask');
        //});

    };

    /**
     * while user click titles, show edit textarea.
     *
     * */
    ns.bindEditTitlesEvent = function(){
        jQuery("#cross_titles").hide();
        jQuery("#cross_titles_textarea").show();
        jQuery('#cross_titles_textarea').bind("clickoutside",function(event) {
            if(event.target != jQuery("#cross_titles")[0]){
                jQuery("#cross_titles").html(jQuery("#cross_titles_textarea").val());
                jQuery("#cross_titles_textarea").hide();
                jQuery("#cross_titles_textarea").unbind("clickoutside");
                jQuery("#cross_titles").show();
            }
        });
    };

    /**
     * user edit time, show edit time area.
     *
     * */
    ns.bindEditTimesEvent = function(){
        //check if had bind a event for #cross_time_bubble
        var eventTemp = jQuery("#cross_time_bubble").data("events");
        //console.log(eventTemp);
        if(!eventTemp){
            jQuery('#cross_time_bubble').bind("clickoutside",function(event) {
                //console.log(event.target.parentNode);
                if(event.target.parentNode != jQuery("#cross_times_area")[0]){
                    //console.log("aaaa");
                    jQuery("#cross_time_bubble").hide();
                    jQuery("#cross_time_bubble").unbind("clickoutside");
                }else{
                    //console.log("bbbb");
                    jQuery("#cross_time_bubble").show();
                }
            });
        }
        /*
        jQuery(document).bind('click',function(e){
            console.log(e.target.parentNode);
        });
        */

        var timeDisplayContainer = [
            document.getElementById("cross_datetime_original"),
            document.getElementById("cross_times")
        ];
        exCal.initCalendar(timeDisplayContainer, 'cross_time_container',"datetime");

    };

    /**
     * User Edit cross description
     *
     * */
    ns.bindEditDescEvent = function(){
        jQuery("#cross_desc").hide();
        jQuery("#cross_desc_textarea").slideDown(400);
        jQuery('#cross_desc_textarea').bind("clickoutside",function(event) {
            if(event.target.parentNode != jQuery("#cross_desc")[0]){
                var str = odof.cross.edit.formateString(jQuery("#cross_desc_textarea").val());
                jQuery("#cross_desc").html(str);
                jQuery("#cross_desc_textarea").slideUp(400);
                jQuery("#cross_desc_textarea").unbind("clickoutside");
                jQuery("#cross_desc").show();
            }
        });
    };

    ns.formateString = function(str){
        var strstr = "0107a88030bfca5e5f72346966901d6a";
            str = str.replace(/(\r\n|\n|\r)/gm,strstr);
        var strArr = str.split(strstr);
        var reString = "";
        for(var i=0; i<strArr.length; i++){
            reString += '<p class="text">'+ strArr[i] +'</p>';
        }
        return reString;
    };

    /**
     * while user submit data
     *
     * */
    ns.submitData = function(){
        var title = jQuery("#cross_titles_textarea").val();
        var time = jQuery("#datetime").val();
        var desc = jQuery("#cross_desc_textarea").val();
        jQuery.ajax({
            url:ns.editURI + "/crossEdit",
            type:"POST",
            dataType:"json",
            data:{
                jrand: Math.round(Math.random()*10000000000),
                ctitle: title,
                ctime: time,
                cdesc: desc
            },
            //回调
            success:function(JSONData){
                ns.callbackActions(JSONData);
            },
            error:function(){
                alert('ll');
        //console.log(JSONData);
            }
        });
        //jQuery("#edit_cross_bar").slideUp(300);
    };

    /**
     * submit call back actions
     *
     * */
    ns.callbackActions = function(JSONData){
        if(!JSONData.error){
            window.location.href = ns.editURI;
        }else{
            jQuery("#error_msg").html(JSONData.msg);
        }

    };

    /**
     * revert cross page
     *
     * */
    ns.revertCross = function(){
        window.location.href=ns.editURI;
    };

    /**
     * expand cross description
     *
     * */
    ns.expandDesc = function(){
        jQuery("#cross_desc").show();
        jQuery("#cross_desc_short").hide();
    };

    /**
     *
     * by Leask
     * */
    ns.exfeeEdit = function(status){
        ns.exfeeEditStatus = status;
        switch (status) {
            case 'edit':
                $('#exfee_edit_box').fadeIn();
                $('#exfee_remove').fadeIn();
                $('#exfee_edit').hide();
                $('#exfee_remove').attr('disabled', false);
                $('#exfee_area').bind('clickoutside', function(event) {
                    if (event.target.id === '') {
                        return;
                    }
                    odof.cross.edit.exfeeEdit();
                });
                $('.exfee_del').hide();
                ns.exfees = $('#exfee_area > .samlcommentlist').html();
                break;
            case 'remove':
                $('#exfee_remove').attr('disabled', true);
                $('#exfee_area').bind('click', function(event) {
                    if (event.target.id === 'exfee_remove' || event.target.className === 'exfee_del') {
                        return;
                    }
                    odof.cross.edit.exfeeEdit('edit');
                });
                $('.exfee_del').show();
                break;
            default:
                $('#exfee_edit_box').fadeOut();
                $('#exfee_remove').hide();
                $('#exfee_edit').fadeIn();
                $('#exfee_edit_box').unbind('clickoutside');
                $('.exfee_del').hide();
        }
        if (status !== 'remove') {
            $('#exfee_area').unbind('click');
        }
        $('#exfee_input').val('');
    };

    /**
     *
     * by Leask
     * */
    ns.revertExfee = function() {
        $('#exfee_area > .samlcommentlist').html(ns.exfees);
        $('#exfee_input').val('');
    };

    /**
     *
     * by Leask
     * */
    ns.parseId = function(strId) {
        if (/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/.test(strId)) {
            var iLt = strId.indexOf('<'),
                iGt = strId.indexOf('>');
            return {name : odof.util.trim(strId.substring(0,     iLt)),
                    id   : odof.util.trim(strId.substring(++iLt, iGt)),
                    type : 'email'};
        } else if (/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/.test(strId)) {
            return {id   : odof.util.trim(strId),
                    type : 'email'};
        } else {
            return {id   : odof.util.trim(strId),
                    type : 'unknow'};
        }
    };

    /**
     *
     * by Leask
     * */
    ns.identityExfee = function() {
        ns.arrIdentitySub = [];
        ns.numNewIdentity = 0;
        var arrIdentityOri = $('#exfee_input').val().split(/,|;|\r|\n|\t/);
        $('#exfee_input').val('');
        for (var i in arrIdentityOri) {
            if ((arrIdentityOri[i] = odof.util.trim(arrIdentityOri[i]))) {
                ns.arrIdentitySub.push(ns.parseId(arrIdentityOri[i]));
            }
        }

        $.ajax({
            type     : 'GET',
            url      : site_url + '/identity/get?identities=' + JSON.stringify(ns.arrIdentitySub),
            dataType : 'json',
            success  : function(data) {
                var exfee_pv     = '',
                    name         = '',
                    identifiable = {};
                for (var i in data.response.identities) {
                    var identity         = data.response.identities[i].external_identity,
                        id               = data.response.identities[i].id,
                        avatar_file_name = data.response.identities[i].avatar_file_name;
                        name             = data.response.identities[i].name;
                    if ($('#exfee_' + id).attr('id') == null) {
                        name = (name ? name : identity).replace('<', '&lt;').replace('>', '$gt;');
                        exfee_pv += '<li id="exfee_' + id + '">'
                                  +     '<button type="button" class="exfee_del"></button>'
                                  +     '<p class="pic20">'
                                  +         '<img src="/eimgs/80_80_' + avatar_file_name + '" alt="">'
                                  +     '</p>'
                                  +     '<p class="smcomment">'
                                  +         '<span>' + name + '</span>' + identity
                                  +     '</p>'
                                  +     '<p class="cs">'
                                  +         '<em class="c2"></em>'
                                  +     '</p>'
                                  + '</li>';
                    }
                    identifiable[identity] = true;
                }
                for (i in ns.arrIdentitySub) {
                    if (!identifiable[ns.arrIdentitySub[i].id]) {
                        switch (ns.arrIdentitySub[i].type) {
                            case 'email':
                                name =  ns.arrIdentitySub[i].name
                                     ? (ns.arrIdentitySub[i].name + ' <'  + ns.arrIdentitySub[i].id + '>')
                                     :  ns.arrIdentitySub[i].id;
                                break;
                            default:
                                name =  ns.arrIdentitySub[i].id;
                        }
                        name = name.replace('<', '&lt;').replace('>', '&gt;');
                        ns.numNewIdentity++;
                        exfee_pv += '<li id="exfee_new' + ns.numNewIdentity + '">'
                                  +     '<button type="button" class="exfee_del"></button>'
                                  +     '<p class="pic20">'
                                  +         '<img src="/eimgs/80_80_' + avatar_file_name + '" alt="">'
                                  +     '</p>'
                                  +     '<p class="smcomment">'
                                  +         '<span>' + name + '</span>'
                                  +     '</p>'
                                  +     '<p class="cs">'
                                  +         '<em class="c2"></em>'
                                  +     '</p>'
                                  + '</li>';
                    }
                }
                $('#exfee_area > .samlcommentlist').html($('#exfee_area > .samlcommentlist').html() + exfee_pv);
                switch (ns.exfeeEditStatus) {
                    case 'edit':
                        $('.exfee_del').hide();
                        break;
                    case 'remove':
                        $('.exfee_del').show();
                }
                ns.summaryExfee();
                //updateExfeeList();
            }
        });
        //$('#exfee_count').html($('span.exfee_exist').length + $('span.exfee_new').length);
    };

    /**
     *
     * by Leask
     * */
    ns.summaryExfee = function() {
        $('.bignb').html($('.cs > .c1').length);
        $('.malnb').html($('.samlcommentlist > li').length);
    };

    /**
     *
     * by Leask
     */
    ns.changeRsvp = function(target) {
        var intC = parseInt(target.className.substr(1)) + 1;
     // target.className = 'c' + (intC > 4 ? 1 : intC);
        target.className = 'c' + (intC > 2 ? 1 : intC);
        ns.summaryExfee();
    };

})(ns);

jQuery(document).ready(function(){

    jQuery("#edit_icon").bind("click",function(){
        odof.cross.edit.showEditBar();
    });
    jQuery("#revert_cross_btn").bind("click",function(){
        odof.cross.edit.revertCross();
    });
    jQuery("#desc_expand_btn").bind("click",function(){
        odof.cross.edit.expandDesc();
    });

    // exfee edit init
    $('#exfee_edit_box').hide();
    $('#exfee_remove').hide();
    $('#exfee_edit').hide();
    $('#exfee_edit').bind('click', function() {
        odof.cross.edit.exfeeEdit('edit');
    });
    $('#exfee_remove').bind('click', function() {
        odof.cross.edit.exfeeEdit('remove');
    });
    $('#exfee_input').keypress(function(e) {
        if ((e.keyCode ? e.keyCode : e.which) == 13) {
            odof.cross.edit.identityExfee();
            e.preventDefault();
        }
    });
    $('.exfee_del').live('click', function() {
        $(this.parentNode).remove();
    });
    $('#exfee_revert').bind('click', function() {
        odof.cross.edit.revertExfee();
    });
    $('#exfee_done').bind('click', function() {
        odof.cross.edit.exfeeEdit();
    });
    $('.cs > em').live('click', function(event){
        odof.cross.edit.changeRsvp(event.target);
    });

});
