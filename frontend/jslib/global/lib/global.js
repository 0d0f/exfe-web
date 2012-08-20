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

    // TODO:后面再优化
    $BODY.on('click.data-link dblclick.data-link', '[data-link]', function (e) {
      var actionType = $(this).data('link');
      var event_ignore = $(this).data('event-ignore');

      if (e.type !== event_ignore) {

        // 判断权限
        var authorization = Store.get('authorization')
          , token = authorization && authorization.token;


        var $db = $('#app-browsing-identity')
          , read_only = $db.data('read-only')
          , settings = $db.data('settings')
          , $readOnly = $('#app-read-only')
          , tokenType = $db.data('token-type')
          , btoken = $db.data('token')
          , action = $db.data('action');

        // read only
        if ($db.size() && read_only && actionType === 'nota') {
          e.stopImmediatePropagation();
          e.stopPropagation();
          e.preventDefault();

          if (!$readOnly.size()) {
            $('#app-main').append(
              $readOnly = $('<div id="app-read-only" data-widget="dialog" data-dialog-type="read_only"></div>')
                .data('settings', settings.browsing)
            );
          }

          $readOnly.trigger('click');
          return false;
        }

        if ($db.size()) {
          // profile 操作, 后端暂不支持browsing-identity 修改身份内容,弹 D4 窗口
          //if (actionType === 'nota' && tokenType === 'user') {
            //e.stopImmediatePropagation();
            //e.stopPropagation();
            //e.preventDefault();
            //$('[data-user-action="' + action + '"]').trigger('click');
            //return false;
          //}
          //else if (actionType === '') {
          if (actionType === '' || actionType === 'nota' && tokenType === 'user') {
            e.stopImmediatePropagation();
            e.stopPropagation();
            e.preventDefault();
            $db.trigger('click');
            return false;
          }
        } else if (!token) {
          e.stopImmediatePropagation();
          e.stopPropagation();
          e.preventDefault();
          if (!$readOnly.size()) {
            $('#app-main').append(
              $readOnly = $('<div id="app-read-only" data-widget="dialog" data-dialog-type="read_only"></div>')
                .data('settings', Store.get('user'))
            );
          }
          $readOnly.trigger('click');
          return false;
        }

      }
    });

    // 只弹两次
    var LIMIT = 2;
    Bus.on('app:cross:edited', function () {
      if (0 === LIMIT) {
        return;
      };
      LIMIT--;
      var $db = $('#app-browsing-identity')
        , action = $db.data('action');

      if (action === 'setup') {
        $('[data-user-action="' + action + '"]').trigger('click');
      }
    });

  //});
});
