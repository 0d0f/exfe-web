define(function (require, exports, module) {
  var $ = require('jquery')
    , Bus = require('bus')
    , Store = require('store')
    , $BODY = $(document.body);

  //$(function () {
    function _docddEventhandler(e) {
      e.stopPropagation();
      e.preventDefault();
      return false;
    }
    $BODY
      .on('drop', _docddEventhandler)
      //.on('dragenter', _docddEventhandler)
      //.on('dragleave', _docddEventhandler)
      .on('dragover', _docddEventhandler);

    var toggle = '[data-toggle="dropdown"]';
    function clearMenus(e) {
      $(toggle).removeClass('open');
    }
    $BODY
      .on('click.dropdown.data-api', clearMenus);


    /*
     * User-Panel 下拉菜单动画效果
     */
    // 初始化高度
    var _i_ = false;
    function hover(e) {
      var self = $(this)
        , timer = self.data('timer')
        , clicked = self.data('clicked')
        , $userPanel = self.find('div.user-panel').addClass('show')
        , h = -$userPanel.outerHeight();

      e.preventDefault();

      if (e.type === 'mouseleave' && !clicked) {
        timer = setTimeout(function () {
          $userPanel
            .stop()
            .animate({top: h}, 200, function () {
              self.prev().addClass('hide');
              self.parent().removeClass('user');
            });
          clearTimeout(timer);
          self.data('timer', timer = null);
        }, 500);

        self.data('timer', timer);
        return false;
      }

      if (clicked) {
        self.data('clicked', false);
        return;
      }

      if (timer) {
        clearTimeout(timer);
        self.data('timer', timer = null);
        return false;
      }

      if (!_i_) {
        $userPanel.css('top', h);
        self.find('.user-panel').addClass('show');
        //_i_ = true;
      }

      self.prev().removeClass('hide');
      self.parent().addClass('user');
      $userPanel
        .stop()
        .animate({top: 56}, 100);
    }

    $BODY.on('mouseenter.dropdown mouseleave.dropdown', '#app-user-menu .dropdown-wrapper', hover);

    $BODY.on('click.usermenu', '#app-user-menu .dropdown-wrapper a[href^="/#"]', function (e) {
      var self = $('#app-user-menu .dropdown-wrapper')
        , $userPanel = self.find('div.user-panel').addClass('show')
        , h = -$userPanel.outerHeight();

      $userPanel.css('top', h);

      self
        .prev()
        .addClass('hide')
        .end()
        .parent()
        .removeClass('user');

      self.data('clicked', true);
    });

    $BODY.on('click.usermenu', '#app-signout', function (e) {
      Bus.emit('xapp:cross:end');
      $('.navbar .dropdown-wrapper').find('.user-panel').remove();
      $('#app-signin')
        .show()
        .next().hide()
        .removeClass('user')
        .find('.fill-left').addClass('hide')
        .end()
        .find('#user-name span').text('');
      Store.remove('authorization');
      window.location.href = '/';
    });

    $BODY.on();

  //});
});
