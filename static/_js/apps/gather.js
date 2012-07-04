ns.updateTitle = function(force) {
    var objTitle        = $('#gather_title'),
        strOriginTitle  = objTitle.val();
    crossData.title     = odof.util.trim(strOriginTitle);
    if (crossData.title === '') {
        crossData.title = defaultTitle;
    }
    if (strOriginTitle  === '' && force
     && strOriginTitle  !== crossData.title) {
        objTitle.val(crossData.title);
    }
    document.title = 'EXFE - ' + crossData.title;
    odof.x.render.showTitle();
};


ns.updateDesc = function() {
    var maxChrt = 33,
        maxLine = 9,
        extSpce = 10,
        objDesc = $('#gather_desc');
    crossData.description = odof.util.trim(objDesc.val());
    odof.x.render.showDesc();
    if (crossData.description === '') {
        $('#gather_desc_x').html(defaultDesc);
    } else {
        var arrDesc = crossData.description.split(/\r|\n|\r\n/),
            intLine = arrDesc.length;
        for (var i in arrDesc) {
            intLine += arrDesc[i].length / maxChrt | 0;
        }
        var difHeight = parseInt(objDesc.css('line-height'))
                      * (intLine ? (intLine > maxLine ? maxLine : intLine) : 1)
                      + extSpce - (objDesc.height());
        if (difHeight <= 0) {
            $('#gather_desc_x').html('');
            return;
        }
        objDesc.animate({'height' : '+=' + difHeight}, 100);
        $('#gather_desc_x').animate({'height' : '+=' + difHeight}, 100);
        $('#gather_desc_blank').animate({'height' : '+=' + difHeight}, 100);
        $('#gather_desc_x').html('');
    }
};


ns.updateTime = function(displaytime, typing) {
    var objTimeInput = $('#datetime_original');
    if (displaytime) {
        objTimeInput.val(displaytime);
    }
    if ((crossData.origin_begin_at = odof.util.trim(objTimeInput.val())) === '') {
        crossData.begin_at = '';
        $('#gather_date_x').html(typing ? sampleTime : defaultTime);
    } else {
        var strTime = odof.util.parseHumanDateTime(
                crossData.origin_begin_at,
                odof.comm.func.convertTimezoneToSecond(odof.comm.func.getTimezone())
            );
        crossData.begin_at = strTime ? strTime : null;
        $('#gather_date_x').html('');
    }
    if (crossData.begin_at === null) {
        objTimeInput.addClass('error');
        $('#gather_submit').addClass('disabled');
    } else {
        objTimeInput.removeClass('error');
        $('#gather_submit').removeClass('disabled');
    }
    odof.x.render.showTime();
};


ns.updatePlace = function(keepLocation) {
    var strPlace = odof.util.parseLocation($('#gather_place').val());
    if (!keepLocation && crossData.place.line1 !== strPlace[0]) {
        crossData.place.lat         = '';
        crossData.place.lng         = '';
        crossData.place.external_id = '';
        crossData.place.provider    = '';
        $('#calendar_map_container').hide();
    }
    crossData.place.line1 = strPlace[0];
    crossData.place.line2 = strPlace[1];
    if (crossData.place.line1 + crossData.place.line2 === '') {
        $('#gather_place_x').html(defaultPlace);
    } else {
        $('#gather_place_x').html('');
    }
    odof.x.render.showPlace();
};


ns.summaryX = function() {
    var x   = odof.util.clone(crossData);
    x.place = JSON.stringify(x.place);
    x.exfee = JSON.stringify(odof.exfee.gadget.getExfees('gatherExfee'));
    return x;
};


ns.submitX = function() {
    if (this.xSubmitting || crossData.begin_at === null) {
        return;
    }
    this.xSubmitting = true;
    $('#gather_failed_hint').hide();
    $('#gather_submit').removeClass('mouseover');
    $('#gather_submit').removeClass('mousedown');
    $('#gather_submit').addClass('disabled');
    // @todo daisy showing here
    // $('#gather_submit').html('');

    var x = this.summaryX();
    x.draft_id = this.draft_id;

    if (typeof window.mapRequest !== 'undefined') {
        window.mapRequest.abort();
    }

    $.ajax({
        type     : 'POST',
        url      : site_url + '/x/gather',
        dataType : 'json',
        data     : x,
        success  : function(data) {
            if (data) {
                if (data.success) {
                    location.href = '/!' + data.crossid;
                    return;
                } else {
                    switch (data.error) {
                        case 'notlogin':
                            odof.x.gather.autoSubmit = true;
                            odof.user.status.doShowLoginDialog();
                            break;
                        case 'notverified':
                            // @todo: inorder to gather X, user must be verified
                            // odof.exlibs.ExDialog.initialize('');
                    }
                }
            }
            var curTop = parseInt($('#gather_submit_blank').css('padding-top'));
            $('#gather_submit_blank').css(
                'padding-top',
                (curTop ? curTop : (curTop + 20)) + 'px'
            );
            // @todo daisy showing here
            $('#gather_failed_hint').show();
            $('#gather_submit').removeClass('mouseover');
            $('#gather_submit').removeClass('mousedown');
            $('#gather_submit').removeClass('disabled');
            $('#gather_submit').html('Re-submit');
            odof.x.gather.xSubmitting = false;
        },
        failure : function(data) {
            // @todo daisy showing here
            $('#gather_failed_hint').show();
            $('#gather_submit').removeClass('mouseover');
            $('#gather_submit').removeClass('mousedown');
            $('#gather_submit').removeClass('disabled');
            $('#gather_submit').html('Re-submit');
            odof.x.gather.xSubmitting = false;
        }
    });
};


ns.afterLogin = function(status) {
    // check status
    if (status.response.success !== 'undefined' && status.response.success) {
        // update my identity
        myIdentity = {
            avatar_file_name  : status.response.user_info.user_avatar_file_name,
            external_identity : status.response.user_info.external_identity,
            identityid        : status.response.user_info.identity_id,
            name              : status.response.user_info.identity_name,
            provider          : status.response.user_info.provider
        };
        odof.exfee.gadget.exfeeChecked = [];
    } else {
        return;
    }
    // update title
    var oldDefaultTitle = defaultTitle;
    defaultTitle = 'Meet ' + status.response.user_info.user_name;
    if (crossData.title === oldDefaultTitle || crossData.title === '') {
        $('#gather_title').val('');
        odof.x.gather.updateTitle(true);
    }
    // update host @todo: set me as host!
    $('#gather_hostby').attr('disabled', true);
    $('#gather_hostby').val(odof.exfee.gadget.displayIdentity(myIdentity));
    // add me as exfee
    var meExfee = odof.util.clone(myIdentity);
    meExfee.host = true;
    meExfee.rsvp = 1;
    odof.exfee.gadget.addExfee('gatherExfee', [meExfee], true);
    // auto submit
    if (odof.x.gather.autoSubmit) {
        odof.x.gather.autoSubmit = false;
        odof.x.gather.submitX();
    }
}




$(document).ready(function() {
                        timezone        : odof.comm.func.getTimezone(),
                        background      : backgrounds[parseInt(Math.random() * backgrounds.length)]};

    // X render
    odof.x.render.show(false);

    // Exfee input
    var curExfees = [];
    if (myIdentity) {
        var meExfee = odof.util.clone(myIdentity);
        meExfee.host = true;
        meExfee.rsvp = 1;
        curExfees.push(meExfee);
    }
    odof.exfee.gadget.make('gatherExfee', curExfees, true, odof.x.gather.showExfee);

    // title
    $('#gather_title').bind('focus blur keyup', function(event) {
        switch (event.type) {
            case 'focus':
                $('#gather_title').addClass('gather_focus').removeClass('gather_blur');
                break;
            case 'blur':
                $('#gather_title').addClass('gather_blur').removeClass('gather_focus');
                odof.x.gather.updateTitle(true);
                break;
            case 'keyup':
                odof.x.gather.updateTitle();
        }
    });
    odof.x.gather.updateTitle(true);
    $('#gather_title').select();
    $('#gather_title').focus();

    // description
    $('#gather_desc').bind('focus blur keyup', function(event) {
        switch (event.type) {
            case 'focus':
                $('#gather_desc_x').addClass('gather_focus').removeClass('gather_blur');
                break;
            case 'blur':
                $('#gather_desc_x').addClass('gather_blur').removeClass('gather_focus');
                odof.x.gather.updateDesc();
                break;
            case 'keyup':
                odof.x.gather.updateDesc();
        }
    });
    odof.x.gather.updateDesc();

    // datetime
    $('#datetime_original').bind('focus blur keyup', function(event) {
        switch (event.type) {
            case 'focus':
                $('#gather_date_x').html(sampleTime);
                $('#gather_date_x').addClass('gather_focus').removeClass('gather_blur');
                exCal.initCalendar(
                    $('#datetime_original')[0],
                    'calendar_map_container',
                    function(displayTimeString, standardTimeString) {
                        odof.x.gather.updateTime(displayTimeString);
                    }
                );
                // @todo: time format tips
                // .html($('#gather_date_bg').html() === gDateDefaultText ? 'e.g. 6PM Today' : '');
                // @todo: disable time input box for version #oC
                // $('#datetime_original').blur();
                break;
            case 'blur':
                $('#gather_date_x').html(defaultTime);
                $('#gather_date_x').addClass('gather_blur').removeClass('gather_focus');
        }
        odof.x.gather.updateTime(null, event.type !== 'blur');
    });
    odof.x.gather.updateTime();

    // place
    $('#gather_place').bind('focus blur keyup', function (event) {
        switch (event.type) {
            case 'focus':
                $('#gather_place_x').addClass('gather_focus').removeClass('gather_blur');
                break;
            case 'blur':
                $('#gather_place_x').addClass('gather_blur').removeClass('gather_focus');
                odof.x.gather.updatePlace();
                break;
            case 'keyup':
                odof.x.gather.updatePlace();
                odof.apps.maps.getLocation('gather_place','calendar_map_container', 'create_cross');
        }
    });
    odof.x.gather.updatePlace();
});
