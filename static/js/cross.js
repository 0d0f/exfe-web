define(function (require, exports, module) {

    var $     = require('jquery');
    var Store = require('store');
    var Api   = require('api');


    var Cross = {
        title       : '',
        description : '',
        time        : {
            begin_at : {
                date_word : '',
                date      : '',
                time_word : '',
                time      : '',
                timezone  : '',
                id        : 0,
                type      : 'EFTime'
            },
            origin       : '',
            outputformat : '',
            id           : 0,
            type         : 'CrossTime'
        },
        place       : {
            title       : '',
            description : '',
            lng         : 0,
            lat         : 0,
            provider    : '',
            external_id : 0,
            id          : 0,
            type        : 'Place'
        },
        attribute   : {state : 'published'},
        exfee_id    : 0,
        widget      : {
            background : {
                image     : '',
                widget_id : 0,
                id        : 0,
                type      : 'Background'
            }
        },
        relative    : {id : 0, relation : ''},
        type        : 'Cross'
    };


    var Exfee = {
        id          : 0,
        type        : 'Exfee',
        invitations : []
    };


    var ShowTitle = function() {
        $('.cross-title > h1').html(Cross.title);
    };


    var ShowDescription = function() {
        $('.cross-description').html(Cross.description);
    };


    var ShowTime = function() {
        $('.cross-time').html(Cross.time.begin_at.date_word);
    };


    var ShowPlace = function() {
        $('.cross-dp.cross-place > h2').html(Cross.place.title);
        $('.cross-dp.cross-place > address').html(Cross.place.description);
    };


    var ShowExfee = function() {

    };


    var ShowBackground = function() {

    };


    var ShowCross = function() {
        ShowTitle();
        ShowDescription();
        ShowTime();
        ShowPlace();
        ShowExfee();
        ShowBackground();
    };


    var UpdateCross = function(objCross) {
        Cross.title       = objCross.title;
        Cross.description = objCross.description;
        Cross.time        = objCross.time;
        Cross.place       = objCross.place;
        Cross.exfee       = objCross.exfee;
        Cross.exfee_id    = objCross.exfee.id;
        Cross.background  = objCross.background;
        ShowCross();
    }


    // get current user
    var Signin  = Store.get('signin');
    var User    = Signin ? Store.get('user') : null;
    if (User) {
        Api.setToken(Signin.token);
    }

    // get cross
    var Cross_id = 100134;
    if (Cross_id) {
        Api.request(
            'getCross',
            {resources : {cross_id : Cross_id}},
            function(data) {
                UpdateCross(data.cross);
            },
            function(data) {
                // failed
                console.log(data);
            }
        );
    }



});
