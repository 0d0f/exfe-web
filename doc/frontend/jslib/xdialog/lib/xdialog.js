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

      errors: {
        'failed': 'Wrong password, try again.',
        'no_password': 'Identity password empty. <span class="">Please sign in through authorization above. To enable password sign-in for this identity, set password from your profile page.</span>',
        'no_external_id': 'Set up this new identity.'
      },

      onCheckUser: function () {
        var user = Store.get('user');
        if (user) {
          var external_identity = user.identities[0].external_id;
          this.$('#identity').val(external_identity);
          this.$('.x-signin').removeClass('disabled loading');
          this.availability = true;
          if (user.identities[0]['avatar_filename'] === 'default.png') {
            user.identities[0]['avatar_filename'] = '/img/default_portraituserface_20.png';
          }
          this.$('#identity')
            .prev()
            .attr('src', user.identities[0]['avatar_filename'])
            .parent()
            .addClass('identity-avatar');
        }
      },

      onShow: function () {
        // 读取本地存储 user infos
        this.emit('checkUser');
        if (this.switchTabType === 'd02') {
          this.$('.modal-body').eq(0).css('opacity', 1);
          this.switchTab('d01');
        }
      },

      events: {
        'click .xbtn-forgotpwd': function (e) {
          e.preventDefault();
          this.hide();
        },
        'click .xbtn-setup': function (e) {
          e.preventDefault();
          this.$('.modal-body').eq(0).css('opacity', .2);
          this.switchTab('d02');
        },
        'click .xbtn-isee': function (e) {
          e.preventDefault();
          this.$('.modal-body').eq(0).css('opacity', 1);
          this.$('#identity').val('');
          this.switchTab('d03');
        },
        'click .xbtn-startover': function (e) {
          e.preventDefault();
          this.$('#identity').val('');
          this.switchTab('d01');
          this.emit('checkUser');
        },
        'click .x-signin': function (e) {
          var xsignin = $(e.currentTarget);
          if (xsignin.hasClass('disabled')) {
            // signin disabled
            return;
          }
          var that = this;
          var t = this.switchTabType;
          var od = this.getFormData(t);
          if (t === 'd01' || t === 'd03') {
            var dfd = $.ajax({
              url: Util.apiUrl + '/users/signin',
              type: 'POST',
              dataType: 'JSON',
              xhrFields: {withCredentials: true},
              data: {
                external_id: od.external_identity,
                provider: od.provider,
                password: od.password,
                name: od.name || '',
                auto_signin: !!od.auto_signin
              },
              beforeSend: function (xhr) {
                xsignin.addClass('disabled loading');
              }
            })
              .done(function (data) {
                xsignin.removeClass('disabled loading');
                var meta = data.meta;
                if (meta.code === 200) {
                  Store.set('signin', data.response);
                  if (t === 'd01') {
                    window.location = '/profile.html';
                  } else {
                    that.hide();
                    var d = new Dialog(dialogs.welcome);
                    d.render();
                    d.show();
                  }
                } else if (meta.code === 403) {
                  var errorType = meta.errorType;
                  if (errorType === 'no_password' || errorType === 'failed') {
                      that.$('.xalert-password')
                      .html(that.options.errors[errorType])
                      .removeClass('hide');
                  } else if (errorType === 'no_external_id') {
                    that.$('#name')
                      .nextAll('.xalert-info')
                      .removeClass('hide');
                  }
                }
              })
                .fail(function (data) {});

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
                      + '<div class="xalert-info hide">Set up this new identity.</div>'
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

                  + '<div class="control-group d d01">'
                    + '<div class="controls">'
                      + '<label class="checkbox">'
                        + '<input type="checkbox" id="auto-signin" value="1" checked>'
                        + 'Sign in automatically'
                      + '</label>'
                    + '</div>'
                  + '</div>'

                  + '<div class="xalert-error xalert-password hide"></div>'

              + '</fieldset>'
            + '</form>',

        // d01 登陆, d02 ISee, d03 注册, d14 验证
        footer: ''
          + '<a href="#" class="xbtn-setup d d01 hide">Set Up?</a>'
          + '<button href="#" class="xbtn-white d d01 xbtn-forgotpwd hide" data-dialog-from=".modal-id" data-widget="dialog" data-dialog-type="forgotpassword">Forgot Password...</button>'
          + '<button href="#" class="xbtn-white d d03 d14 xbtn-startover hide">Start Over</button>'
          + '<button href="#" class="pull-right d d14 xbtn-blue hide">Verify</button>'
          + '<button href="#" class="pull-right xbtn-blue d d01 d03 x-signin disabled hide">Sign In</button>'
          + '<button href="#" class="pull-right xbtn-blue d xbtn-success hide">Done</button>'
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

      onHidden: function () {
        var $e = this.element;
        this.options.srcNode.data('dialog', null);
        this.destory();
        $e.remove();
      },

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

  dialogs.welcome = {

    options: {
      events: {
        'click .xbtn-blue': function (e) {
          window.location = '/profile.html';
        }
      },

      onHidden: function () {
        var $e = this.element;
        this.options.srcNode.data('dialog', null);
        this.destory();
        $e.remove();
      },

      backdrop: false,

      viewData: {
        // class
        cls: 'modal-wc',

        title: 'Welcome',

        body: ''
          + '<div class="shadow title">Hi, SteveE.</div>'
          + '<div class="center shadow">Thanks for using <span class="x-sign">EXFE</span>.</div>'
          + '<p class="center">An utility for hanging out with friends.</p>'
          + '<p>A verification email has been sent.<br>Please check your mailbox.</p>'
          + '<p><span class="x-sign">X</span> (cross) is a gathering of people, on purpose or not. We save you from calling up every one RSVP, losing in endless emails and messages off the point.</p>'
          + '<p><span class="x-sign">EXFE</span> your friends, gather a <span class="x-sign">X</span>.</p>',

        footer: ''
          + '<button href="#" class="pull-right xbtn-blue">GO</button>'
      }
    }
  };


  dialogs.forgotpassword = {

    options: {
      events: {
        'click .xbtn-cancel': function (e) {
          this.hide();
          if (this.dialog_from) $(this.dialog_from).removeClass('hide');
          var $e = this.element;
          this.offSrcNode();
          this.destory();
          $e.remove();
        },
        'click .xbtn-blue': function (e) {
          // Verify ajax
        }
      },

      onShow: function (e) {
        var data = Store.get('user');
        if (data) {
          var identity = data.identities[0];
          if (identity['avatar_filename'] === 'default.png') {
            identity['avatar_filename'] = '/img/default_portraituserface_20.png';
          }
          var external_id = identity.external_id;
          var src = identity.avatar_filename;
          var $identity = this.$('.identity');
          $identity.find('img').attr('src', src);
          $identity.find('span').html(external_id);
        }
      },

      backdrop: false,

      viewData: {

        cls: 'modal-fp',

        title: 'Forgot Password',

        body: ''
          + '<div class="shadow title">Forgot Password</div>'
          + '<div>Identity to reset password:</div>'
          + '<div class="identity disabled">'
            + '<img class="pull-right avatar" src="" width="20" height="20">'
            + '<span></span>'
          + '</div>'
          + '<div>Confirm sending reset token to your mailbox?</div>'
          + '<div class="xalert-error hide">Requested too much, hold on awhile. Receive no verification email? It might be mistakenly filtered as spam, please check and un-spam.</div>'
          + '<div class="xalert-success hide">Verification sent, it should arrive in minutes. Please check your mailbox and follow the link.</div>',

        footer: ''
          + '<button class="pull-right xbtn-blue">Verify</button>'
          + '<button class="pull-right xbtn-blue hide">Done</button>'
          + '<a class="pull-right xbtn-cancel">Cancel</a>'

      }
    }

  };

  dialogs.changepassword = {

    options: {

      onHidden: function () {
        var $e = this.element;
        this.options.srcNode.data('dialog', null);
        this.destory();
        $e.remove();
      },

      events: {
        'click .xbtn-success': function (e) {
          var that = this;
          var cppwd = that.$('#cppwd').val();
          var cpnpwd = that.$('#cp-npwd').val();

          // note: 暂时先用 alert
          if (!cppwd || !cpnpwd) {
            if (!cppwd) {
              alert('Please input current password.');
            } else {
              alert('Please input new password.');
            }
            return;
          }

          e.preventDefault();

          var $e = $(e.currentTarget);
          var signinData = Store.get('signin');
          var user_id = signinData.user_id;
          var token = signinData.token;

          $.ajax({
            type: 'post',
            data: {
              current_password: cppwd,
              new_password: cpnpwd
            },
            dataType: 'json',
            url: Util.apiUrl + '/users/' + user_id + '/setpassword?token=' + token,
            xhrFields: { withCredentials: true },
            beforeSend: function (xhr) {
              $e.addClass('disabled loading');
            }
          })
            .done(function (data) {
              $e.removeClass('disabled loading');
              if (data.meta.code === 200) {
                $e = that.element;
                that.offSrcNode();
                that.destory();
                $e.remove();
              } else if (data.meta.code === 403) {
                var errorType = data.meta.errorType;
                if (errorType === 'invalid_current_password') {
                  alert('Invalid current password.');
                }
              }
            });
        },
      },

      onShow: function () {
        var user = Store.get('user');
        this.$('#cp-fullname').val(user.name);
      },

      viewData: {

        cls: 'modal-cp mblack',

        title: 'Change Password',

        body: ''
          + '<div class="shadow title">Change Password</div>'
          + '<form class="modal-form form-horizontal">'
            + '<fieldset>'
              + '<legend style="white-space: nowrap;">Please enter current password and set new password.</legend>'
              + '<div class="control-group">'
                + '<label class="control-label" for="cp-fullname">Full name:</label>'
                + '<div class="controls">'
                  + '<input class="input-large disabled" tabIndex="-1" id="cp-fullname" value="" disabled="disabled" type="text">'
                + '</div>'
              + '</div>'

              + '<div class="control-group">'
                + '<label class="control-label" for="cppwd">Password:</label>'
                + '<div class="controls">'
                  + '<input class="input-large" id="cppwd" placeholder="Type current password" type="password">'
                + '</div>'
              + '</div>'

              + '<div class="control-group">'
                + '<label class="control-label" for="cp-npwd">New Password:</label>'
                + '<div class="controls">'
                  + '<input class="input-large" id="cp-npwd" placeholder="Type new password" type="password">'
                + '</div>'
              + '</div>'

            + '</fieldset>'
          + '</form>',

        footer: ''
          + '<button href="#" class="xbtn-white xbtn-forgotpwd" data-dialog-from=".modal-cp" data-widget="dialog" data-dialog-type="forgotpassword">Forgot Password...</button>'
          + '<button class="pull-right xbtn-blue xbtn-success">Done</button>'
          + '<a class="pull-right xbtn-discard" data-dismiss="dialog">Discard</a>'

      }

    }

  };

  dialogs.addidentity = {
    options: {
      events: {
        'click .xbtn-success': function (e) {
          var new_identity = Util.trim(this.$('#new-identity').val());
          var password = this.$('#password').val();

          if (!new_identity || !password) {
            if (!new_identity) {
              alert('Identity empty.');
            } else {
              alert('Identity password empty.');
            }
            return;
          }

          var $e = $(e.currentTarget);
          var signinData = Store.get('signin');
          var user_id = signinData.user_id;
          var token = signinData.token;
          var that = this;

          var identity = Util.parseId(new_identity);
          console.dir(identity);

          if (identity.provider) {

            $.ajax({
              url: Util.apiUrl + '/users/addIdentity?token=' + token,
              type: 'POST',
              data: {
                external_id: identity.external_identity,
                provider: identity.provider,
                password: password
              },
              dataType: 'json',
              xhrFields: { withCredentials: true },
              beforeSend: function (xhr) {
                $e.addClass('disabled loading');
              }
            })
              .done(function (data) {
                $e.removeClass('disabled loading');
                if (data.meta.code === 200) {
                  that.emit('hidden');
                }
              });;

          }
        }
      },

      onShow: function () {
        this.element.removeClass('hide');
        this.$('#new-identity')[0].focus();
      },

      onHidden: function () {
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
      },

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
                    + '<label class="control-label" for="new-identity">Identity:</label>'
                    + '<div class="controls /*identity-avatar*/">'
                      + '<img class="add-on avatar hide" src="" alt="" width="20" height="20" />'
                      + '<input type="text" class="input-large identity" id="new-identity" autocomplete="off" data-widget="typeahead" data-typeahead-type="identity" />'
                      + '<div class="xalert-info hide">Set up this new identity.</div>'
                    + '</div>'
                  + '</div>'

                  + '<div class="control-group">'
                    + '<label class="control-label" for="password">Password:</label>'
                    + '<div class="controls">'
                      + '<input type="password" class="input-large" id="password" />'
                      + '<input type="text" class="input-large hide" autocomplete="off" />'
                      + '<div class="xalert-error hide"></div>'
                    + '</div>'
                  + '</div>'

              + '</fieldset>'
            + '</form>',

        footer: ''
          + '<button href="#" class="xbtn-white xbtn-forgotpwd" data-dialog-from=".modal-id" data-widget="dialog" data-dialog-type="forgotpassword">Forgot Password...</button>'
          + '<button href="#" class="pull-right xbtn-blue xbtn-success disabled">Add</button>'
      }

    },

    availability: false,

    init: function () {
      var that = this;
      Bus.on('widget-dialog-identification-auto', function (data) {
        /*
        that.availability = false;
        if (data && data.registration_flag === 'SIGN_UP') {
          that.availability = true;
        }
        */
      });
    }

  };

  //
  dialogs.verification_twitter = {
  }

  // Identification 弹出窗口类
  var Identification = Dialog.extend({

    // 用户有效身份标志位，默认 false
    availability: false,

    init: function () {
      var that = this;
      Bus.on('widget-dialog-identification-auto', function (data) {
        that.availability = false;
        var t;
        if (data) {
          // 新身份
          if (data.registration_flag === 'SIGN_UP') {
            t = 'd03';
          }
        }

        t && that.switchTab(t);
        that.$('.xbtn-forgotpwd').data('source', data);
      });

      Bus.on('widget-dialog-identification-nothing', function () {
        that.$('.xbtn-forgotpwd').data('source', null);
        that.availability = false;
        if (that.switchTabType !== 'd03') that.switchTab('d01');
        that.$('.x-signin')[(that.availability ? 'remove' : 'add') + 'Class']('disabled');
      });
    },

    d01: function (t) {
      if (t === 'd01') {
        var val = Util.trim(this.$('#identity').val());
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
      if (t === 'd01') {
        identity.auto_signin = this.$('#auto-signin').prop('checked');
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
      this.$('.xalert-error').addClass('hide');
      this.$('.xalert-info').addClass('hide');

      this.switchTabType = t;

      this.d01(t);
      this.d03(t);

      if (t === 'd01' || t === 'd03') {
        var $identity = this.$('#identity');
        $identity[0].focus();
      }

    }

  });

  /* MODAL DATA-API
   * -------------- */

  $(function () {
   $BODY.on('click.dialog.data-api', '[data-widget="dialog"]', function (e) {
      var $this = $(this)
        , data = $this.data('dialog')
        , settings
        , dialogType = $this.data('dialog-type')
        , dialogTab = $this.data('dialog-tab')
        , dialogFrom = $this.data('dialog-from')
        , dataSource = $this.data('source');

      e.preventDefault();

      if (!data)  {

        if (dialogType) {
          settings = dialogs[dialogType];
          data = new (dialogType === 'identification' ? Identification : Dialog)(settings);
          data.options.srcNode = $this;
          if (dialogFrom) data.dialog_from = dialogFrom;
          data.render();
          $this.data('dialog', data);
        }

      }

      data.show(e);
      if (dialogTab) data.switchTab(dialogTab);

    });
  });

});
