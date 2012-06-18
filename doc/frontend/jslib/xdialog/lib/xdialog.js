define(function (require, exports, module) {
  var $ = require('jquery');
  var R = require('rex');
  var Bus = require('bus');
  var Api = require('api');
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
        'no_password': 'Identity password empty. <span class="xalert-normal">Please sign in through authorization above. To enable password sign-in for this identity, set password from your profile page.</span>',
        'no_external_id': 'Set up this new identity.'
      },

      onCheckUser: function () {
        var user = Store.get('user');
        if (user) {
          // 暂时处理单用户，默认取第一个
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

          this.$('.xbtn-forgotpwd').data('source', {identity: user.identities[0]});
          this.toggleSetupOrForgopwd(true);
        }
      },

      onShowBefore: function () {
        if (this.switchTabType === 'd02') {
          this.$('.modal-body').eq(0).css('opacity', 1);
          this.switchTab('d01');
        }
        // 读取本地存储 user infos
        this.emit('checkUser');
      },

      onShowAfter: function () {
        if (this.switchTabType === 'd01' || this.switchTabType === 'd03') {
          var $identity = this.$('#identity');
          $identity.focusend();
        }
      },

      events: {
        'click #password-eye': function (e) {
          var p = this.$('#password');
          var pt = this.$('#password-text');
          var $elem = $(e.currentTarget);
          if ($elem.hasClass('icon16-pass-hide')) {
            p.addClass('hide');
            pt.val(p.val()).removeClass('hide');
          } else {
            pt.addClass('hide');
            p.val(pt.val()).removeClass('hide');
          }
          $elem.toggleClass('icon16-pass-show icon16-pass-show');
        },
        'click .xbtn-forgotpwd': function (e) {
          e.preventDefault();
          this.switchTab('d24');
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
          this.emit('checkUser');
          this.switchTab('d01', true);
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

            var dfd = Api.request('signin'
              , {
                type: 'POST',
                data: {
                  external_id: od.external_identity,
                  provider: od.provider,
                  password: od.password,
                  name: od.name || '',
                  auto_signin: !!od.auto_signin
                },
                beforeSend: function (xhr) {
                  xsignin.addClass('disabled loading');
                },
                complete: function (xhr) {
                  xsignin.removeClass('disabled loading');
                }
              }
              , function (data) {
                Store.set('signin', data);
                // 最后登陆的 external_identity
                Store.set('last_identity', od.external_identity);
                if (t === 'd01') {
                  window.location = '/profile';
                } else {
                  that.hide();
                  var d = new Dialog(dialogs.welcome);
                  d.render();
                  d.show();
                }
              }
              , function (data) {
                var errorType = data.meta.errorType;
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
            );
          }
        }
      },

      backdrop: true,

      viewData: {
        // class
        cls: 'modal-id',

        title: 'Identification',

        body: ''
          + '<div class="shadow title">Welcome to <span class="x-sign">EXFE</span></div>'
            + '<div class="pull-right">'
              + '<a href="#twitter"><img src="/img/twitter-logo.png" alt="" width="52" height="40"></a>'
            + '</div>'
            + '<div class="authorize">Sign in through:</div>'
            + '<div class="orspliter">or</div>'
            + '<form class="modal-form form-horizontal">'
              + '<fieldset>'
                + '<legend>Enter your identity:</legend>'
                  + '<div class="control-group">'
                    + '<label class="control-label" for="identity">Identity:</label>'
                    + '<div class="controls /*identity-avatar*/">'
                      + '<img class="add-on avatar hide" src="" alt="" width="20" height="20" />'
                      + '<input type="text" class="input-large identity" id="identity" autocomplete="off" data-widget="typeahead" data-typeahead-type="identity" />'
                      + '<i class="help-inline small-loading hide"></i>'
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
                      + '<input type="text" class="input-large hide" autocomplete="off" id="password-text" />'
                      + '<i class="help-inline icon16-pass-hide" id="password-eye"></i>'
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

        // d01 登陆, d02 ISee, d03 注册, d24 验证
        footer: ''
          + '<a href="#" class="xbtn-setup d d01 hide">Set Up?</a>'
          + '<button href="#" class="xbtn-white d d01 xbtn-forgotpwd hide">Forgot Password...</button>'
          + '<button href="#" class="xbtn-white d d03 d24 xbtn-startover hide">Start Over</button>'
          + '<button href="#" class="pull-right d d24 xbtn-blue hide">Verify</button>'
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

      onHideAfter: function () {
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
      },

      backdrop: false,

      viewData: {
        // class
        cls: 'mblack modal-sandbox',

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

      onHideAfter: function () {
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
      },

      backdrop: true,

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
          var dialog_from = this.dialog_from;
          this.hide();
          var $e = this.element;
          this.offSrcNode();
          this.destory();
          $e.remove();
          if (dialog_from) {
            $('[data-dialog-type="' + dialog_from + '"]').trigger('click.dialog.data-api');
            // TODO: 先简单处理，后面是否要保存 target 元素
            $('#identity').focusend();
          }
        },

        'click .xbtn-verify': function (e) {
          var that = this;
          var $e = $(e.currentTarget);
          if ($e.hasClass('disabled') || $e.hasClass('success')) return;
          var i = $e.data('identity');
          if (i) {
            Api.request('forgotPassword'
              , {
                type: 'POST',
                data: {
                  provider: i.provider,
                  external_id: i.external_id
                },
                beforeSend: function (xhr) {
                  $e.addClass('disabled');
                },
                complete: function (xhr) {
                  $e.removeClass('disabled');
                }
              }
              , function (data) {
                if (data.action === 'VERIFYING') {
                  that.$('.identity').next().removeClass('hide');
                  $e.text('Done').addClass('success');
                }
              }
              , function (data) {
              }
            );
          }
        }
      },

      onShowBefore: function (e) {
        var identity_id = $(e.currentTarget).data('identity-id');
        var user = Store.get('user');
        var identities = user.identities;
        var is = R.filter(identities, function (v, i) {
          if (identity_id === v.id) return true;
        })[0];
        if (is) {
          if (is['avatar_filename'] === 'default.png') {
            is['avatar_filename'] = '/img/default_portraituserface_20.png';
          }
          var external_id = is.external_id;
          var src = is.avatar_filename;
          var $identity = this.$('.user-identity');
          $identity.find('img.avatar').attr('src', src);
          $identity.find('i').addClass('icon16-identity-' + is.provider);
          $identity.next().text(external_id);
          this.$('.xbtn-verify').data('identity', is);
        }
      },

      backdrop: false,

      viewData: {

        cls: 'mblack modal-fp',

        title: 'Forgot Password',

        body: ''
          + '<div class="shadow title">Forgot Password</div>'
          + '<div>Identity to reset password:</div>'
          + '<div class="pull-right user-identity">'
            + '<img class="avatar" src="" alt="" width="40" height="40" />'
            + '<i class="provider"></i>'
          + '</div>'
          + '<div class="identity disabled"></div>'
          + '<div class="hide">Verification sent, it should arrive in minutes. Please check your mailbox and follow the instruction.</div>'
          + '<div class="xalert-error hide">Requested too much, hold on awhile. Receive no verification email? It might be mistakenly filtered as spam, please check and un-spam.</div>'
          + '<div class="xalert-success hide">Verification sent, it should arrive in minutes. Please check your mailbox and follow the link.</div>',

        footer: ''
          + '<button class="pull-right xbtn-blue xbtn-verify">Verify</button>'
          + '<a class="pull-right xbtn-cancel">Cancel</a>'

      }
    }

  };

  dialogs.changepassword = {

    options: {

      onHideAfter: function () {
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
      },

      events: {
        'click .xbtn-resetpwd': function (e) {
          var user = Store.get('user');
          var identities = user.identities;
          var is = R.filter(identities, function (v, i) {
            if (v.status === 'CONNECTED') return true;
          });
          if (is.length === 1) {
            e.stopPropagation();
            this.hide();
            var d = new Dialog(dialogs['forgotpassword']);
            d.dialog_from = 'changepassword';
            d.render();
            $(e.currentTarget).data('identity-id', is[0].id);
            d.show(e);
          }
        },
        'click .password-eye': function (e) {
          var $e = $(e.currentTarget);
          var $input = $e.prev();
          $input.prop('type', function (i, val) {
            return val === 'password' ? 'text' : 'password';
          });
          $e.toggleClass('icon16-pass-hide icon16-pass-show');
        },
        'click .xbtn-forgotpwd': function (e) {
          var user = Store.get('user');
          $(e.currentTarget).data('source', {identity: user.identities[0]});
        },
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

          Api.request('setPassword'
            , {
              type: 'POST',
              resources: {
                user_id: user_id
              },
              data: {
                current_password: cppwd,
                new_password: cpnpwd
              },
              beforeSend: function (xhr) {
                $e.addClass('disabled loading');
              },
              complete: function (xhr) {
                $e.removeClass('disabled loading');
              }
            }
            , function (data) {
              $e = that.element;
              that.offSrcNode();
              that.destory();
              $e.remove();
            }
            , function (data) {
              if (data.meta.code === 403) {
                var errorType = data.meta.errorType;
                if (errorType === 'invalid_current_password') {
                  alert('Invalid current password.');
                }
              }
            }
          );

        },
      },

      onShowBefore: function () {
        var user = Store.get('user');
        this.$('.identity > img').attr('src', user.avatar_filename);
        this.$('.identity > span').text(user.name);
      },

      backdrop: false,

      viewData: {

        cls: 'modal-cp mblack modal-large',

        title: 'Change Password',

        body: ''
          + '<div class="shadow title">Change Password</div>'
          + '<form class="modal-form">'
            + '<fieldset>'
              + '<legend>Please enter current password and set new one.</legend>'

              + '<div class="identity">'
                + '<img class="avatar" src="" width="40" height="40" />'
                + '<span></span>'
              + '</div>'

              + '<div class="control-group">'
                + '<label class="control-label" for="cppwd">Password:</label>'
                + '<div class="controls">'
                  + '<input class="input-large" id="cppwd" placeholder="Current password" type="password" autocomplete="off" />'
                  + '<i class="help-inline password-eye icon16-pass-hide"></i>'
                + '</div>'
              + '</div>'

              + '<div class="control-group">'
                + '<label class="control-label" for="cp-npwd">New Password:</label>'
                + '<div class="controls">'
                  + '<input class="input-large" id="cp-npwd" placeholder="Set new EXFE password" type="password" autocomplete="off" />'
                  + '<i class="help-inline password-eye icon16-pass-hide"></i>'
                + '</div>'
              + '</div>'

            + '</fieldset>'
          + '</form>',

        footer: ''
          + '<button href="#" class="xbtn-white xbtn-resetpwd" data-dialog-from="changepassword" data-widget="dialog" data-dialog-type="resetpassword">Reset Password...</button>'
          + '<button class="pull-right xbtn-blue xbtn-success">Done</button>'
          + '<a class="pull-right xbtn-discard" data-dismiss="dialog">Discard</a>'

      }

    }

  };

  dialogs.resetpassword = {
    options: {

      onHideAfter: function () {
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
      },

      events: {
        'click .xbtn-cancel': function (e) {
          var dialog_from = this.dialog_from;
          this.hide();
          if (dialog_from) {
            $('[data-dialog-type="' + dialog_from + '"]').trigger('click.dialog.data-api');
            // TODO: 先简单处理，后面是否要保存 target 元素
          }
        },
      },

      liItem: ''
        + '<li>'
          + '<div class="pull-right user-identity">'
            + '<img class="avatar" src="" width="40" height="40" />'
            + '<i class="provider"></i>'
          + '</div>'
          + '<a class="identity-name" href="#"></a>'
        + '</li>',

      backdrop: false,

      viewData: {

        cls: 'modal-rsp mblack',

        title: 'Forgot Password',

        footer: ''
          + '<a class="pull-right xbtn-cancel">Cancel</a>',

        body: ''
          + '<div class="shadow title">Reset Password</div>'
          + '<div>Reset password by verifying identity:</div>'
          + '<ul class="unstyled">'
          +'</ul>'

      },

      onShowBefore: function () {
        var self = this;
        var user = Store.get('user');
        var item = self.options.liItem;
        var identities = user.identities;
        var $ul = self.$('ul');
        var providers = {
          email: 'email',
          twitter: 'twitter',
          phone: 'phone'
        };
        $ul.html('');
        R.each(identities, function (v, i) {
          if (v.status === 'CONNECTED') {
            var $item = $(item);
            $item.find('a').text(v.provider === 'twitter' ? v.name : v.external_id);
            $item.find('a').attr('data-dialog-from', 'resetpassword');
            $item.find('a').attr('data-dialog-type', 'verification_' + v.provider);
            $item.find('a').attr('data-widget', 'dialog');
            $item.find('a').attr('data-identity-id', v.id);
            $item.find('img').attr('src', v.avatar_filename);
            $item.find('i').addClass('icon16-identity-' + providers[v.provider]);
            $ul.append($item);
          }
        });
      }

    }
  };


  dialogs.addidentity = {
    options: {

      backdrop: true,

      events: {
        'click .xbtn-forgotpwd': function (e) {
          var new_identity = Util.trim(this.$('#new-identity').val());
        },
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

          if (identity.provider) {
            Api.request('addIdentity', {
                type: 'POST',
                data: {
                  external_id: identity.external_identity,
                  provider: identity.provider,
                  password: password
                },
                beforeSend: function (xhr) {
                  $e.addClass('disabled loading');
                },
                complete: function (xhr) {
                  $e.removeClass('disabled loading');
                }
              }, function (data) {
                that.hide();
                Bus.emit('app:addidentity', data);
              });
          }
        }
      },

      onShowBefore: function () {
        this.element.removeClass('hide');
        this.$('#new-identity').focusend();
      },

      onHideAfter: function () {
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
                      + '<i class="help-inline small-loading hide"></i>'
                      + '<div class="xalert-info hide">Set up this new identity.</div>'
                    + '</div>'
                  + '</div>'

                  + '<div class="control-group">'
                    + '<label class="control-label" for="password">Password:</label>'
                    + '<div class="controls">'
                      + '<input type="password" class="input-large" id="password" />'
                      + '<input type="text" class="input-large hide" autocomplete="off" />'
                      + '<i class="help-inline icon16-pass-hide" id="password-eye"></i>'
                      + '<div class="xalert-error hide"></div>'
                    + '</div>'
                  + '</div>'

              + '</fieldset>'
            + '</form>',

        footer: ''
          + '<button href="#" class="xbtn-white xbtn-forgotpwd" data-dialog-from="identification" data-widget="dialog" data-dialog-type="forgotpassword">Forgot Password...</button>'
          + '<button href="#" class="pull-right xbtn-blue xbtn-success disabled">Add</button>'
      }

    },

    availability: false,

    init: function () {
      var that = this;
      Bus.on('widget-dialog-identification-auto', function (data) {
        that.availability = false;
        if (data) {
          if (data.registration_flag === 'SIGN_IN') {
            that.$('.xalert-info').removeClass('hide');
            that.$('.xbtn-forgotpwd').removeClass('hide').data('source', data);
          }
          else if (data.registration_flag === 'SIGN_UP') {
            that.availability = true;
            that.$('.xbtn-forgotpwd').addClass('hide').data('source', null);
          }
          that.$('.xbtn-success').removeClass('disabled');
        } else {
          that.$('.xbtn-success').addClass('disabled');
          that.$('.xbtn-forgotpwd').addClass('hide').data('source', null);
        }

      });

      Bus.on('widget-dialog-identification-nothing', function () {
        that.$('.xbtn-forgotpwd').addClass('hide');
      });
    }

  };

  // emial Verification
  dialogs.verification_email = {

    options: {

      onHideAfter: function () {
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
      },

      events: {
        'click .xbtn-verify': function (e) {
          var $e = $(e.currentTarget);
          if ($e.hasClass('disabled') || $e.hasClass('success')) return;
          var that = this;
          var identity_id = $e.data('identity_id');
          Api.request('verifyUserIdentity'
            , {
              type: 'POST',
              data: {
                identity_id: identity_id
              },
              beforeSend: function (data) {
                $e.addClass('disabled');
              },
              complete: function () {
                $e.removeClass('disabled');
              }
            }
            , function (data) {
              if (data.action === 'VERIFYING') {
                $e.text('Done').addClass('success');
              }
            }
            , function (data) {
            }
          );
        },

        'click .xbtn-cancel': function (e) {
          var dialog_from = this.dialog_from;
          this.hide();
          if (dialog_from) {
            $('[data-dialog-type="' + dialog_from + '"]').trigger('click.dialog.data-api');
            // TODO: 先简单处理，后面是否要保存 target 元素
            $('#identity').focusend();
          }
        }

      },

      backdrop: false,

      viewData: {

        //class
        cls: 'mblack modal-ve',

        title: 'Verification',

        body: ''
          + '<div class="shadow title">Identity Verification</div>'
          + '<div>Identity to verify:</div>'
          + '<div class="pull-right user-identity">'
            + '<img class="avatar" src="" alt="" width="40" height="40">'
            + '<i class="provider icon16-identity-email"></i>'
          + '</div>'
          + '<div class="identity disabled"></div>'
          + '<p>Confirm sending verification to your mailbox?</p>'
          + '<p class="hide">Requested too much, hold on awhile. Receive no verification email? It might be mistakenly filtered as spam. Or try ‘Manual Verification’.</p>',

        footer: ''
          //+ '<button href="#" class="xbtn-white">Manual Verification</button>'
          + '<button class="pull-right xbtn-blue xbtn-verify">Verify</button>'
          + '<a class="pull-right xbtn-cancel">Cancel</a>'

      },

      onShowBefore: function (e) {
        var $e = $(e.currentTarget);
        var identity_id = $e.data('identity-id');
        var user = Store.get('user');
        var identity = R.filter(user.identities, function (v, i) {
          if (v.id === identity_id) return true;
        })[0];
        this.$('.xbtn-verify').data('identity_id', identity.id);
        this.$('.identity').text(identity.external_id);
        this.$('.avatar').attr('src', identity.avatar_filename);
      }

    }

  };

  // twitter Verification
  dialogs.verification_twitter = {

    options: {

      events: {

        'click .xbtn-verify': function (e) {
          var $e = $(e.currentTarget);
          var that = this;
          if ($e.hasClass('disabled') || $e.hasClass('success')) return;
          var identity_id = $e.data('identity_id');
          Api.request('verifyUserIdentity'
            , {
              type: 'POST',
              data: {
                identity_id: identity_id
              },
              beforeSend: function (data) {
                $e.addClass('disabled');
              },
              complete: function () {
                $e.removeClass('disabled');
              }
            }
            , function (data) {
              if (data.action === 'VERIFYING') {
                $e.text('Done').addClass('success');
              }
            }
            , function (data) {
            }
          );
        },

        'click .xbtn-cancel': function (e) {
          var $e = this.element;
          this.offSrcNode();
          this.destory();
          $e.remove();
        }

      },

      backdrop: false,

      viewData: {

        //class
        cls: 'mblack modal-ve',

        title: 'Verification',

        body: ''
          + '<div class="shadow title">Identity Verification</div>'
          + '<div>Identity to verify:</div>'
          + '<div class="pull-right user-identity">'
            + '<img class="avatar" src="" alt="" width="40" height="40">'
            + '<i class="provider icon16-identity-twitter"></i>'
          + '</div>'
          + '<div class="identity disabled"></div>'
          + '<p>You will be directed to Twitter website to authorize <span class="x-sign">EXFE</span>. Don’t forget to follow @<span class="">EXFE</span>, it’s necessary for smooth service integration.</p>'
          + '<p>We hate spam, will NEVER disappoint your trust.</p>',

        footer: ''
          + '<button class="pull-right xbtn-blue xbtn-verify">Verify</button>'
          + '<a class="pull-right xbtn-cancel">Cancel</a>'

      },

      onShowBefore: function (e) {
        var $e = $(e.currentTarget);
        var identity_id = $e.data('identity-id');
        var user = Store.get('user');
        var identity = R.filter(user.identities, function (v, i) {
          if (v.id === identity_id) return true;
        })[0];
        this.$('.xbtn-verify').data('identity_id', identity.id);
        this.$('.identity').text(identity.external_id);
        this.$('.avatar').attr('src', identity.avatar_filename);
      }

    }

  };

  dialogs.verification_phone = {

    options: {

      events: {
      },

      backdrop: false,

      viewData: {

        cls: 'mblack modal-ve',

        title: 'Verification',

        body: ''
          + '<div class="shadow title">Identity Verification</div>'
          + '<div>Identity to verify:</div>'
          + ''

      }

    }

  };

  dialogs.setpassword = {

    options: {

      backdrop: false,

      viewData: {

        // class
        cls: 'mblack modal-sp',

        title: 'Set Password',

        body: ''
          + '<div class="shadow title">Set Password</div>'
          + '<form class="modal-form form-horizontal">'
            + '<fieldset>'
              + '<legend>Please set a universal password for your account. You can sign in by any of your identities (if more than one), with the same password.</legend>'

              + '<div class="control-group">'
                + '<label class="control-label" for="setpassword">Password:</label>'
                + '<div class="controls">'
                  + '<input type="password" class="input-large" id="setpassword" />'
                  + '<input type="text" class="input-large hide" autocomplete="off" id="setpassword-text" />'
                  + '<div class="xalert-error hide"></div>'
                + '</div>'
              + '</div>'

            + '</fieldset>'
          + '</form>'
          + '<p>e.g.: To sign in with your Twitter account. Just use “@myTwitterID@Twitter” as your identity, along with your password above.</p>',

        footer: ''
          + '<button href="#" class="pull-right xbtn-blue xbtn-success">Done</button>'

      }

    }

  };

  // Identification 弹出窗口类
  var Identification = Dialog.extend({

    // 用户有效身份标志位，默认 false
    availability: false,

    init: function () {
      var that = this;
      Bus.on('widget-dialog-identification-auto', function (data) {
        that.availability = false;

        var t;

        if (that.switchTabType === 'd24') {
          t = 'd01';
        }

        if (data) {
          // SIGN_IN
          if (data.registration_flag === 'SIGN_IN') {
            t = 'd01';
            that.toggleSetupOrForgopwd(true);
          }
          // SIGN_UP 新身份
          else if (data.registration_flag === 'SIGN_UP') {
            //t = 'd03';
            that.toggleSetupOrForgopwd(false);
          }
          // RESet Password
          else if (data.registration_flag === 'RESET_PASSWORD') {
            t = 'd24';
            //that.toggleSetupOrForgopwd(false);
          }
          that.availability = true;
        } else {
          //that.toggleSetupOrForgopwd(false);
        }

        t && (that.switchTabType !== t) && that.switchTab(t);

        that.$('.x-signin')[(that.availability ? 'remove' : 'add') + 'Class']('disabled');
        that.$('.xbtn-forgotpwd').data('source', data);
      });

      Bus.on('widget-dialog-identification-nothing', function () {
        that.$('.xbtn-forgotpwd').data('source', null);
        that.availability = false;
        if (that.switchTabType !== 'd03') that.switchTab('d01');
        that.$('.x-signin')[(that.availability ? 'remove' : 'add') + 'Class']('disabled');
      });
    },

    toggleSetupOrForgopwd: function (b) {
      this.$('.xbtn-setup')[(b ? 'add' : 'remove') + 'Class']('hide');
      this.$('.xbtn-forgotpwd')[(b ? 'remove' : 'add') + 'Class']('hide');
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
        var pt = this.$('#password-text');
        if (! pt.hasClass('hide')) {
          this.$('#password').val(pt.val());
        }
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

    switchTab: function (t, b) {
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

      if (this.isShown && (this.switchTabType === 'd01' || this.switchTabType === 'd03')) {
        var $identity = this.$('#identity');
        $identity.focusend();
      }

      if (t === 'd01') this.toggleSetupOrForgopwd(b);

      this.d03(t);
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

      if (dialogTab) data.switchTab(dialogTab);
      data.show(e);

    });
  });

});
