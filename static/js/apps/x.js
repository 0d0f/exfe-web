/**
 * @Description: X render module
 * @Author:      Leask Huang <leask@exfe.com>
 * @createDate:  Dec 30, 2011
 * @CopyRights:  http://www.exfe.com
 */

var moduleNameSpace = 'odof.x.render',
    ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns)
{

    ns.arrRvsp   = ['', 'Accepted', 'Declined', 'Interested'];


    ns.showTitle = function()
    {
        var objTitle = $('#x_title');
        objTitle.html(crossData.title);
        document.title = 'EXFE - ' + crossData.title;
        if (objTitle.hasClass('x_title_double') && objTitle.height() < 112) {
            objTitle.addClass('x_title_normal').removeClass('x_title_double');
        }
        if (objTitle.hasClass('x_title_normal') && objTitle.height() > 70) {
            objTitle.addClass('x_title_double').removeClass('x_title_normal');
        }
    };


    ns.showDesc = function(editing)
    {
        var strDesc = crossData.description === '' && editing
                    ? 'Write some words about this X.'
                    : crossData.description;
        var converter = new Showdown.converter();
        $('#x_desc').html(converter.makeHtml(strDesc));

        /**
         * @todo
        $('#x_expand_btn').bind('click', function() {
            odof.x.render.expandDesc();
        });
        ns.expandDesc = function(){
            $("#cross_desc").show();
            #("#cross_desc_short").hide();
        };
         */
    };


    ns.showRsvp = function(editable)
    {
        if (editable) {
            if (myrsvp) {
                $('#x_rsvp_status').html(this.arrRvsp[myrsvp]);
                $('#x_rsvp_msg').show();
                $('.x_rsvp_button').hide();
                $('#x_rsvp_change').show();
            } else {
                $('#x_rsvp_msg').hide();
                $('.x_rsvp_button').show();
                $('#x_rsvp_change').hide();
            }
        } else {
            $('#x_rsvp_msg').hide();
            $('.x_rsvp_button').show().addClass('readonly');
            $('#x_rsvp_change').hide();
        }
    };


    ns.showTime = function()
    {
        var strRelativeTime = '',
            strAbsoluteTime = '';
        if (!crossData.begin_at || crossData.begin_at === '0000-00-00 00:00:00') {
            strRelativeTime = 'Sometime';
        } else {
            strRelativeTime = odof.util.getRelativeTime(crossData.begin_at);
            strAbsoluteTime = odof.util.getHumanDateTime(crossData.begin_at);
        }
        $('#x_time_relative').html(strRelativeTime);
        $('#x_time_absolute').html(strAbsoluteTime);
    };


    ns.showPlace = function()
    {
        var objPlace = $('#x_place_line1');
        objPlace.html(crossData.place.line1 ? crossData.place.line1 : 'Somewhere');
        $('#x_place_line2').html(crossData.place.line2.replace(/\r/g, '<br>'));
        if (objPlace.hasClass('x_place_line1_double') && objPlace.height() < 70) {
            objPlace.addClass('x_place_line1_normal').removeClass('x_place_line1_double');
        }
        if (objPlace.hasClass('x_place_line1_normal') && objPlace.height() > 53) {
            objPlace.addClass('x_place_line1_double').removeClass('x_place_line1_normal');
        }
    };


    ns.showConversation = function()
    {
        var strMessage = '';
        for (var i in crossData.conversation) {
            strMessage += this.makeMessage(crossData.conversation[i]);
        }
        $('#x_conversation_list').html(strMessage);
    };


    ns.makeMessage = function(objItem)
    {
        return '<li class="cleanup">'
             +     '<img src="' + odof.comm.func.getUserAvatar(
                   objItem.identity.avatar_file_name, 80, img_url)
             +     '" class="x_conversation_avatar">'
             +     '<div class="x_conversation_message">'
             +         '<p class="x_conversation_content_area">'
             +             '<span class="x_conversation_identity">'
             +                 objItem.identity.name + ': '
             +             '</span>'
             +             '<span class="x_conversation_content">'
             +                 objItem.content
             +             '</span>'
             +         '</p>'
             +         '<span class="x_conversation_time">'
             +             odof.util.getRelativeTime(objItem.created_at)
             +         '</span>'
             +     '</div>'
             + '</li>';
    };


    ns.show = function(editable)
    {
        var strCnvstn = editable
                      ? '<div id="x_conversation_area">'
                      +     '<h3>Conversation</h3>'
                      +     '<div id="x_conversation_input_area" class="cleanup">'
                      +         '<img id="x_conversation_my_avatar" class="x_conversation_avatar">'
                      +         '<textarea id="x_conversation_input"></textarea>'
                      +         '<input id="x_conversation_submit" type="button" title="Say!">'
                      +     '</div>'
                      +     '<ol id="x_conversation_list"></ol>'
                      + '</div>'
                      : '',
            crossHtml = '<div id="x_title_area">'
                      +     '<h2 id="x_title" class="x_title x_title_normal"></h2>'
                      +     '<input id="x_title_edit" class="x_title" style="display:none;">'
                      + '</div>'
                      + '<div id="x_content" class="cleanup">'
                      +     '<div id="x_mainarea">'
                      +         '<div id="x_desc_area">'
                      +             '<div id="x_desc" class="x_desc"></div>'
                      +             '<textarea id="x_desc_edit" class="x_desc" style="display:none;"></textarea>'
                      +             '<a id="x_desc_expand" href="javascript:void(0);">Expand</a>'
                      +         '</div>'
                      +         '<div id="x_rsvp_area" class="cleanup">'
                      +             '<span id="x_rsvp_msg">'
                      +                 'Your RSVP is "<span id="x_rsvp_status"></span>".'
                      +             '</span>'
                      +             '<a id="x_rsvp_yes"    href="javascript:void(0);" class="x_rsvp_button">Accept</a>'
                      +             '<a id="x_rsvp_no"     href="javascript:void(0);" class="x_rsvp_button">Decline</a>'
                      +             '<a id="x_rsvp_maybe"  href="javascript:void(0);" class="x_rsvp_button">interested</a>'
                      +             '<a id="x_rsvp_change" href="javascript:void(0);">Change?</a>'
                      +         '</div>'
                      +         strCnvstn
                      +     '</div>'
                      +     '<div id="x_sidebar">'
                      +         '<div id="x_time_area">'
                      +             '<h3   id="x_time_relative"></h3>'
                      +             '<span id="x_time_absolute"></span>'
                      +         '</div>'
                      +         '<div id="x_place_area">'
                      +             '<h3   id="x_place_line1" class="x_place_line1_normal"></h3>'
                      +             '<span id="x_place_line2"></span>'
                      +         '</div>'
                      +         '<div id="x_exfee_area"></div>'
                      +     '</div>'
                      + '</div>';

        $('#x_view_content').html(crossHtml);
        this.showComponents();
        this.showRsvp(editable);
        if (editable) {
            $('#x_conversation_my_avatar').attr(
                'src',
                odof.comm.func.getUserAvatar(
                    myIdentity.avatar_file_name, 80, img_url
                )
            );
            this.showConversation();
        }
    };


    ns.showComponents = function()
    {
        this.showTitle();
        this.showDesc();
        this.showTime();
        this.showPlace();
    };

})(ns);
