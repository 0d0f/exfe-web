define(function (require, exports, module) {
  var $ = require('jquery');
  var Bus = require('bus');
  var Util = require('util');
  var Store = require('store');
  var $BODY = $(document.body);

  var Dialog = require('dialog');

  var dialogs = {};

  // TODO: js html 分离
  dialogs.identification = {
    options: {

      events: {
        'click .xbtn-setup': function (e) {
          e.preventDefault();
          this.$('.modal-body').eq(0).css('opacity', .2);
          this.switchTab('d02');
        },
        'click .xbtn-isee': function (e) {
          e.preventDefault();
          this.$('.modal-body').eq(0).css('opacity', 1);
          this.switchTab('d03');
        },
        'click .xbtn-startover': function (e) {
          e.preventDefault();
          this.switchTab('d01');
        },
        'click .x-signin': function (e) {
          if ($(e.currentTarget).hasClass('disabled')) {
            // signin disabled
            return;
          }
          var t = this.switchTabType;
          var od = this.getFormData(t);
          if (t === 'd01' || t === 'd03') {
            var dfd = $.ajax({
              url: 'http://api.localexfe.me/v2/users/signin',
              type: 'POST',
              dataType: 'JSON',
              xhrFields: {withCredentials: true},
              data: {
                external_id: od.external_identity,
                provider: od.provider,
                password: od.password,
                name: od.name || ''
              }
            })
              .done(function (data) {
                if (data.meta.code === 200) {
                  console.dir(data);
                  var user_id = data.response.user_id;
                  var token = data.response.token;
                  Store.set('user_id', user_id);
                  Store.set('token', token);
                  //window.location = '/profile.html';
                } else {
                  alert('Sign In Fail.');
                }
              });

          }

        }
      },

      backdrop: false,

      viewData: {
        // class
        cls: 'modal-id',

        title: 'Identification',

        body: ''
          + '<div class="shadow title">Welcome to <span class="x-sign">EXFE</span></div>'
            + '<div class="pull-right">'
              + '<a href="#twitter"><img src="/img/twitter-logo.png" alt="" width="52" height="40"></a>'
            + '</div>'
            + '<div class="authorize">Authorize account through:</div>'
            + '<div class="orspliter">or</div>'
            + '<form class="modal-form form-horizontal">'
              + '<fieldset>'
                + '<legend>Enter your identity information:</legend>'
                  + '<div class="control-group">'
                    + '<label class="control-label" for="identity">Identity:</label>'
                    + '<div class="controls /*identity-avatar*/">'
                      + '<img class="add-on avatar hide" src="" alt="" width="20" height="20" />'
                      + '<input type="text" class="input-large identity" id="identity" autocomplete="off" data-widget="typeahead" data-typeahead-type="identity" />'
                      + '<p class="help-block hide">Set up this new identity.</p>'
                    + '</div>'
                  + '</div>'

                  + '<div class="control-group d d03 hide">'
                    + '<label class="control-label" for="name">Display name:</label>'
                    + '<div class="controls">'
                      + '<input type="text" class="input-large" id="name" autocomplete="off" placeholder="Your name here" />'
                    + '</div>'
                  + '</div>'

                  + '<div class="control-group d d01 d03 hide">'
                    + '<label class="control-label" for="password">Password:</label>'
                    + '<div class="controls">'
                      + '<input type="password" class="input-large" id="password" />'
                      + '<input type="text" class="input-large hide" autocomplete="off" />'
                    + '</div>'
                  + '</div>'

              + '</fieldset>'
            + '</form>',

        // d01 登陆, d02 ISee, d03 注册, d14 验证
        footer: ''
          + '<a href="#" class="xbtn-setup d d01 hide">Set Up?</a>'
          + '<button href="#" class="xbtn-white d d01 xbtn-forgotpwd hide">Forgot Password...</button>'
          + '<button href="#" class="xbtn-white d d03 d14 xbtn-startover hide">Start Over</button>'
          + '<button href="#" class="pull-right d d14 xbtn-blue hide">Verify</button>'
          + '<button href="#" class="pull-right xbtn-blue d d01 d03 x-signin disabled hide">Sign In</button>'
          + '<button href="#" class="pull-right xbtn-white d d02 xbtn-isee hide">I See</button>',

        others: ''
          + '<div class="isee d d02 hide">'
            + '<div class="modal-body">'
              + '<div class="shadow title">Sign-Up-Free</div>'
              + '<div class="signing">Tired of signing up all around?</div>'
              + '<p>Just authorize through your existing accounts on other websites, such as Twitter, Facebook or Google. We hate spam, will NEVER disappoint your trust.</p>'
              + '<p>Otherwise, tell us your email as your identity, and display name that your friends know who you are, along with a password for sign-in.</p>'
            + '</div>'
          + '</div>'
      }

    }
  };

  dialogs.sandbox = {

    options: {

      backdrop: false,

      viewData: {
        // class
        cls: 'modal-sandbox',

        title: 'Sandbox',

        body: ''
          + '<div class="shadow title">“Rome wasn\'t built in a day.”</div>'
          + '<p><span class="x-sign">EXFE</span> [ˈɛksfi] is still in <span class="pilot">pilot</span> stage (with <span class="sandbox">SANDBOX</span> tag). We’re building up blocks of it, thus some bugs and unfinished pages. Any feedback, please email <span class="feedback">feedback@exfe.com</span>. Our apologies for any trouble you may encounter, much appreciated.</p>'
      }
    }
  };

  // Identification 弹出窗口类
  var Identification = Dialog.extend({

    // 用户有效身份标志位，默认 false
    availability: false,

    init: function () {
      // 读取本地存储 user infos

      var that = this;
      Bus.on('widget-dialog-identification-auto', function (data) {
        that.availability = false;
        var t;
        if (data.identities.length) {
          t = 'd01';
          that.availability = true;
        } else if (Util.trim(that.$('#identity').val())) {
          t = 'd03';
          that.availability = true;
        }

        that.switchTab(t);
      });

      Bus.on('widget-dialog-identification-nothing', function () {
        that.availability = false;
        that.switchTab('d01');
      });
    },

    d01: function (t) {
      if (t === 'd01') {
        var val = Util.trim(this.$('#identity').val())
        this.$('.xbtn-setup')[(val ? 'add' : 'remove') + 'Class']('hide');
        this.$('.xbtn-forgotpwd')[(val ? 'remove' : 'add') + 'Class']('hide');
      }
    },

    d03: function (t) {
      // new user
      if (t === 'd03') {
        this.$('#password')
          .attr('placeholder', 'Set Password here');
      }
    },

    getFormData: function (t) {
      var val = Util.trim(this.$('#identity').val());
      var identity = Util.parseId(val);
      if (t === 'd01' || t === 'd03') {
        identity.password = this.$('#password').val();
      }
      if (t === 'd03') {
        identity.name = Util.trim(this.$('#name').val());
      }
      return identity;
    },

    switchTab: function (t) {
      this.$('.d')
        .not('.hide')
        .addClass('hide')
        .end()
        .filter('.' + t)
        .removeClass('hide');

      this.$('.x-signin')[(this.availability ? 'remove' : 'add') + 'Class']('disabled');

      this.switchTabType = t;

      this.d01(t);
      this.d03(t);
    }

  });

  /* MODAL DATA-API
   * -------------- */

  $(function () {
   $BODY.on('click.dialog.data-api', '[data-widget="dialog"]', function (e) {
      var $this = $(this)
        , data = $this.data('dialog')
        , href
        , dialogType = $this.data('dialog-type')
        , dialogTab = $this.data('dialog-tab');

      e.preventDefault();

      if (!data)  {

        if (dialogType) {
          data = new (dialogType === 'identification' ? Identification : Dialog)(dialogs[dialogType]);
          data.render();
          if (dialogTab) data.switchTab(dialogTab);
          $this.data('dialog', data);
        }

      }

      data.show();

    });
  });

});
