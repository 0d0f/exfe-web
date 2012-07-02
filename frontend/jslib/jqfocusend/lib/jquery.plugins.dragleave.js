define(function (require) {

  var jQuery = require('jquery');

  !function ( $ ) {

    /**
     *
     * Refer:
     *    http://dev.w3.org/html5/spec/dnd.html
     *    http://stackoverflow.com/questions/10253663/how-to-detect-the-dragleave-event-in-firefox-when-dragging-outside-the-window
     *    http://jsfiddle.net/robertc/HU6Mk/3/
     */

    /*
    $.event.special.dragenter = {

      delegateType: 'dragenter',

      bindType: 'dragenter',

      handle: function (e) {
        var related = e.target,
            inside = false, ret;

        if (related !== this) {

            if (related) {
                inside = $.contains(this, related);
            }

            if (!inside) {
              ret = e.handleObj.handler.apply(this, arguments);
              e.type = 'dragleave';
            }
        }

        return ret;
      }

    };
    */

    $.event.special.dragleave = {

      delegateType: 'dragleave',

      bindType: 'dragleave',

      handle: function (e) {
        var t = e.target;
        if (!$.contains(this, t)) {
        }
      }
    };

  }( jQuery );

});
