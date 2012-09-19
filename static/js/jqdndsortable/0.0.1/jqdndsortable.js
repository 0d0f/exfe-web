define(function (require) {

  var $ = require('jquery');
  var $BODY = $(document.body);
  /**
    * Refer:
    *  https://github.com/bgrins/nativesortable/blob/master/nativesortable.js
    *  https://github.com/farhadi/html5sortable/blob/master/jquery.sortable.js
    */

  /**
    * Usage:
    *    <ul>
    *      <li>1</li>
    *      <li>2</li>
    *      <li>3</li>
    *    </ul>
    *
    *    $('ul').dndsortable({items: '> li'});
    *
    */
  $.fn.dndsortable = function (options) {
    options = $.extend({}, $.fn.dndsortable.defaults, options);

    return this.each(function () {
      var $this = $(this)
        , index
        , dragging
        , selector = options.list + options.items
        , items = $this.find(options.items);

      items
        .addClass(options.childClass)
        .attr('draggable', 'true');

      console.log($this, selector);
      $this
        // dragstart
        .on('dragstart.ui', selector, function (e) {
          e.stopPropagation();
          if (e.originalEvent.dataTransfer) {
            e.originalEvent.dataTransfer.effectAllowed = 'moving';
            e.originalEvent.dataTransfer.setData('Text', options.setData(this));
          }

          index = $(dragging = this).addClass(options.draggingClass).index();

          // start handle
          options.start && options.start.call(this, index);

          return true;
        })
        // dragend
        .on('dragend.ui', selector, function (e) {
          e.stopPropagation();
          $(this).removeClass(options.draggingClass);

          // end handle
          options.end && options.end.call(this, index);
          index = undefined;
          dragging = null;
          dragenterData(this, false);

          return false;
        })
        // dragenter
        .on('dragenter.ui', selector, function (e) {
          if (!dragging || dragging === this) {
            return true;
          }

          var ele = this;
          var $ele = $(ele);
          // Prevent dragenter on a child from allowing a dragleave on the container
          var prevCounter = dragenterData(this);
          dragenterData(this, prevCounter + 1);

          if (prevCounter === 0) {
            $ele.addClass(options.overClass);

            if (!options.wrap) {
              wrap($this, dragging, this, options.delay, function (dragging, dropzone) {
                options.enter && options.enter.call(dropzone);

                $ele[$(dragging).index() < $ele.index() ? 'after' : 'before'](dragging);
              });
            }

          }

          return false;
        })
        // dragleave
        .on('dragleave.ui', selector, function (e) {
          // Prevent dragenter on a child from allowing a dragleave on the container
          var prevCounter = dragenterData(this);
          dragenterData(this, prevCounter - 1);

          // This is a fix for child elements firing dragenter before the parent fires dragleave
          if (!dragenterData(this)) {
            $(this).removeClass(options.overClass);
            dragenterData(this, false);

            options.leave && options.leave.call(this);
          }

          return false;
        })
        // drop
        .on('drop.ui', selector, function (e) {
          e.stopPropagation();
          e.preventDefault();

          if (this === dragging) {
            return;
          }

          if (options.wrap) {
            wrap($this, dragging, this, options.delay, function (dragging, dropzone) {
                options.sort ? options.sort.call($this, dragging, dropzone) : sort.call($this, dragging, dropzone);

                var data;
                if (e.originalEvent.dataTransfer) {
                data = e.originalEvent.dataTransfer.getData('Text');
              }

              options.change && options.change.call(dropzone, data);
            });
          }

          return false;
        })
        // dragover
        .on('dragover.ui', selector, function (e) {
          if (!dragging) {
            return true;
          }
          e.stopPropagation();
          e.preventDefault();
          return false;
        });

    });
  };

  $.fn.dndsortable.defaults = {
    delay: 0,
    wrap: false,
    list: 'ul',
    items: '> li',
    overClass: 'sortable-over',
    placeholderClass: 'sortable-placeholder',
    draggingClass: 'sortable-dragging',
    childClass: 'sortable-child',
    dragenterData: 'child-dragenter',
    setData: function () {}
    //start: function () {}
    //end: function () {}
    //enter: function () {}
    //leave: function () {}
    //change: function () {}
  };

  function dragenterData(ele, val, ded) {
    ele = $(ele);
    ded = $.fn.dndsortable.defaults.dragenterData;
    if (arguments.length === 1) {
      return ele.data(ded) || 0;
    } else if (!val) {
      ele.data(ded, null);
    } else {
      ele.data(ded, Math.max(0, val));
    }
  }

  function sort(dragging, dropzone) {
    var sibling = $(dragging).next();
    if (sibling[0] === dropzone) $(dragging).before(dropzone);
    else {
      $(dropzone).before(dragging);
      sibling.before(dropzone);
    }
  }

  function wrap(container, dragging, dropzone, delay, fn) {
    if (delay) {
      var t = container.data('drag-timer');
      if (t) {
        clearTimeout(t);
        t = null;
        t = setTimeout(function () {
          fn(dragging, dropzone);
        }, delay);
        container.data('drag-timer', t);
      }
    } else {
      fn(dragging, dropzone);
    }
  }

});
