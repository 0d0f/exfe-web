define('xdialog', function (require, exports, module) {
  var $ = require('jquery');
  var R = require('rex');
  var Bus = require('bus');
  var Api = require('api');
  var Util = require('util');
  var Store = require('store');
  var Handlebars = require('handlebars');
  var $BODY = $(document.body);

  var Dialog = require('dialog');

  var dialogs = {};

  exports.dialogs = dialogs;

  // TODO: js html 分离
  dialogs.identification = {
    options: {

      errors: {
        'failed': 'Password incorrect.',
        'no_password': 'Password incorrect.',
        'no_external_id': 'Set up this new identity.'
      },

      onCheckUser: function () {
        var lastIdentity = Store.get('lastIdentity')
          , last_external_username = Store.get('last_external_username');
        if (lastIdentity) {
          this.availability = true;

          this.$('#identity').val(last_external_username);
          this.$('.x-signin').removeClass('disabled loading');
          this.$('.user-identity')
            .removeClass('hide')
            .find('img')
            .attr('src', lastIdentity.avatar_filename)
            .next()
            .attr('class', 'provider icon16-identity-' + lastIdentity.provider);

          this.$('.xbtn-forgotpwd').data('source', [lastIdentity]);

          this.switchTab('d01');
        }

        this.$('.help-subject').toggleClass('icon14-question', !this.availability);
      },

      onShowBefore: function (e) {
        var source = $(e.currentTarget).data('source');
        if (source) {
          this.$('#identity').val(source);
        } else {
          // 读取本地存储 user infos
          this.emit('checkUser');
        }
      },

      onShowAfter: function () {
        if (this.switchTabType === 'd00'
            || this.switchTabType === 'd01'
            || this.switchTabType === 'd02') {
          this.$('#identity').focusend();
        }
      },

      onHideAfter: function () {
        this.$('.modal-body').eq(0).css('opacity', 1);
        this.switchTabType = 'd00';

        // abort ajax
        if (this._oauth_) {
          this._oauth_.abort();
        }
        this.destory();

        // TODO: 删除 `popmenu` 元素，暂时先放着
        $('.popmenu').remove();
      },

      events: {
        // bind `enter` key for submit
        'keypress .modal-main': function (e) {
          var t = this.switchTabType
            , kc = e.keyCode;

          // enter
          if (this.availability && (t === 'd01' || t === 'd02') && kc === 13) {
            this.$('.x-signin').click();
          }
        },

        'click .oauth > a': function (e) {
          e.stopPropagation();
          e.preventDefault();
          var that = this;
          var $e = $(e.currentTarget)
            , oauthType = $e.data('oauth');

          if (oauthType === 'twitter') {
            that._oauth_ = $.ajax({
              url: '/OAuth/twitterAuthenticate',
              type: 'POST',
              dataType: 'JSON',
              data: {
                callback: window.location.href
              },
              beforeSend: function (xhr) {
                that.$('.modal-body').eq(0).css('opacity', 0);
                that.switchTab('d05');

                that.$('.authentication')
                  .find('img')
                  .removeClass('hide');

                that.$('.authentication')
                  .find('.redirecting')
                  .removeClass('hide');

                that.$('.authentication')
                  .find('.xalert-fail')
                  .addClass('hide');

                that.$('.xbtn-oauth')
                  .addClass('hide');
              },
              success: function (data) {
                var code = data.meta.code;
                if (code === 200) {
                  window.location.href = data.response.redirect;
                } else {
                  that.$('.authentication')
                    .find('img')
                  .addClass('hide');

                  that.$('.authentication')
                    .find('.redirecting')
                    .addClass('hide');

                  that.$('.authentication')
                    .find('.xalert-fail')
                    .removeClass('hide');

                  that.$('.xbtn-oauth')
                    .removeClass('hide');
                }
              }
            });
          }
        },
        'click .xbtn-oauth': function (e) {
          this.$('.modal-body').eq(0).css('opacity', 1);
          this.switchTab('d00');
          return false;
        },
        'click .xbtn-verify': function (e) {
          var that = this;
          var $e = $(e.currentTarget);
          if ($e.hasClass('xbtn-success')) {
            that.$('.verify-after').addClass('hide');
            $e.removeClass('xbtn-success').text('Verify');
            that.hide();
            return false;
          }
          var provider = that._identity.provider;
          var external_id = that._identity.external_id;
          Api.request('verifyIdentity'
            , {
              type: 'POST',
              data: {
                provider: provider,
                external_username: external_id
              }
            }
            , function (data) {
              that.$('.verify-before').addClass('hide');
              if (data.action === 'VERIFYING') {
                that.$('.verify-after').removeClass('hide');
                $e.addClass('xbtn-success').text('Done');
              } else if (data.action === 'REDIRECT') {
                //$e.addClass('verify-error').removeClass('hide');
              } else {
                $e.addClass('verify-error').removeClass('hide');
              }
            }
          );
        },
        'blur #identity': function (e) {
          var val = Util.trim($(e.currentTarget).val());
          var $identity = this.$('[for="identity"]');
          var $text = $identity.find('span');
          if (val.length && !Util.parseId(val).provider) {
            $identity.addClass('label-error');
            $text.text('Invalid identity.');
          } else {
            $identity.removeClass('label-error');
            $text.text('');
          }
        },
        'blur #name': function (e) {
          var val = Util.trim($(e.currentTarget).val());
          var $name = this.$('[for="name"]');
          var $text = $name.find('span');
          if (!val) {
            $name.addClass('label-error');
            $text.text('');
          } else if (Util.utf8length(val) > 30) {
            $text.text('Too long.');
            $name.addClass('label-error');
          } else if (Util.zh_CN.test(val)) {
            $name.addClass('label-error');
            $text.text('Invalid character.');
          } else {
            $name.removeClass('label-error');
            $text.text('');
          }
        },
        'blur #password': function (e) {
          var val = Util.trim($(e.currentTarget).val());
          var $pass = this.$('[for="password"]');
          var $text = $pass.find('span');
          if (!val) {
            $pass.addClass('label-error');
            $text.text('Password incorrect.');
          } else {
            $pass.removeClass('label-error');
            $text.text('');
          }
        },
        'click .help-subject': function (e) {
          var $e = $(e.currentTarget)
            , flag = this.identityFlag
            , s;

          if ($e.hasClass('icon14-question')) {
            s = 'Identity is your online representative, such as email, <span class="strike">mobile no.</span>, and your account username on other websites like Twitter, Facebook, etc.';
            $e.parent().find('.xalert-error:eq(0)').html(s).removeClass('hide');
          } else {
            $e.toggleClass('icon14-question icon14-clear');

            this.resetInputs();

            this.$('.user-identity').addClass('hide');

            // 清楚user 缓存
            Store.remove('lastIdentity');
            Store.remove('last_external_username');
            Store.remove('authorization');
            Store.remove('user');
            Store.remove('identities');

            // cleanup `xidentity` source data
            // TODO: 后期移调
            this.$('[data-typeahead-type="identity"]').data('typeahead').source = null;

            this.switchTab('d00');
          }

        },
        'click #password-eye': function (e) {
          var $e = $(e.currentTarget);
          var $input = $e.prev();
          $input.prop('type', function (i, val) {
            return val === 'password' ? 'text' : 'password';
          });
          $e.toggleClass('icon16-pass-hide icon16-pass-show');
        },
        'click .xbtn-forgotpwd': function (e) {
          e.preventDefault();
          this.element.addClass('hide');
          $('#js-modal-backdrop').addClass('hide');
          //this.hide();
          //this.switchTab('d04');
        },
        'click .xbtn-setup': function (e) {
          e.preventDefault();
          this.$('.modal-body').eq(0).css('opacity', 0.05);
          this.switchTab('d03');
        },
        'click .xbtn-isee': function (e) {
          e.preventDefault();
          this.$('.modal-body').eq(0).css('opacity', 1);
          this.$('#identity').val('');
          this.switchTab('d02');
        },
        'click .xbtn-startover': function (e) {
          e.preventDefault();
          this.$('#identity').val('');
          //this.emit('checkUser');
          this.resetInputs()
          this.switchTab('d00', true);
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

          that.$('#password').trigger('blur');
          if (t === 'd02') {
            that.$('#name').trigger('blur');
          }

          if (!od.password || (t === 'd02' && !od.name)) {
            return;
          }

          // 非 normal email
          if (od.provider !== 'email') {
            od.external_identity = od.external_username;
          }

          if (t === 'd01' || t === 'd02') {

            Api.request('signin'
              , {
                type: 'POST',
                data: {
                  external_username: od.external_identity,
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
                // 清除浏览身份
                // TODO
                delete App.request.session.browsing_authorization;
                delete App.request.session.browsing_user;

                Store.set('authorization', data);
                // 最后登陆的 external_identity
                Store.set('last_external_username', od.external_identity);

                that.hide();
                if (t === 'd01' || t === 'd02') {
                  Bus.emit('app:user:signin', data.token, data.user_id, false, true);
                } else {
                  var d = new Dialog(dialogs.welcome);
                  d.render();
                  d.show({
                    identity: {
                      name: od.name,
                      provider: od.provider
                    }
                  });
                }
              }
              , function (data) {
                var errorType = data.meta.errorType,
                    errorDetail = data.meta.errorDetail;
                if (errorType === 'no_password' || errorType === 'failed') {
                  that.$('[for="password"]')
                    .addClass('label-error')
                    .find('span').text(errorDetail || that.options.errors[errorType]);
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

        //title: 'Identification',
        title: 'Start',

        // TODO: oAuth 地址设置
        body: ''
          + '<div class="shadow title">Welcome to <span class="x-sign">EXFE</span></div>'
            + '<div class="clearfix">'
              + '<div class="pull-left authorize">Authenticate with:</div>'
              + '<div class="pull-left oauth">'
                + '<a href="#" class="oauth-twitter" data-oauth="twitter">twitter</a>'
              + '</div>'
            + '</div>'
            + '<div class="orspliter">or</div>'
            + '<form class="modal-form">'
              + '<fieldset>'
                + '<legend>Use your online identity:</legend>'

                  + '<div class="clearfix control-group">'
                    + '<label class="control-label" for="identity">Identity: <span class="xalert-message"></span></label>'
                    + '<div class="pull-right user-identity hide">'
                      + '<div class="avatar">'
                        + '<img src="" alt="" width="40" height="40" />'
                        + '<i class="provider"></i>'
                      + '</div>'
                    + '</div>'
                    + '<div class="controls">'
                      + '<input type="text" class="input-large identity" id="identity" autocomplete="off" data-widget="typeahead" data-typeahead-type="identity" placeholder="Enter your email" />'
                      + '<i class="help-subject"></i>'
                      + '<i class="help-inline small-loading hide"></i>'
                      + '<div class="xalert xalert-error hide" style="margin-top: 5px;"></div>'

                      + '<div class="xalert xalert-error authenticate hide" style="margin-top: 5px;">'
                        + '<span class="xalert-fail">Please directly authenticate identity above.</span><br />To enable password sign-in for this identity, set an <span class="x-sign">EXFE</span> password first in your profile page.'
                      + '</div>'

                    + '</div>'
                  + '</div>'

                  + '<div class="form-title d d02 hide">Welcome! Please set up your new account.<span class="pull-right form-title-bd"></span></div>'
                  + '<div class="control-group d d02 hide">'
                    + '<label class="control-label" for="name">Display name: <span></span></label>'
                    + '<div class="controls">'
                      + '<input type="text" class="input-large" id="name" autocomplete="off" placeholder="Desired recognizable name" />'
                    + '</div>'
                  + '</div>'

                  + '<div class="control-group d d01 d02 hide">'
                    + '<label class="control-label" for="password">Password: <span></span></label>'
                    + '<div class="controls">'
                      + '<input type="password" class="input-large" id="password" autocomplete="off" />'
                      + '<i class="help-inline icon16-pass-hide pointer" id="password-eye"></i>'
                    + '</div>'
                  + '</div>'

                  + '<div class="control-group d d01 hide">'
                    //+ '<div class="controls">'
                    + '<div class="control-label">'
                      + '<label class="checkbox pointer">'
                        + '<input type="checkbox" id="auto-signin" value="1" checked />'
                        + 'Sign in automatically'
                      + '</label>'
                    + '</div>'
                  + '</div>'

                  + '<div class="verify-before d d04 hide">'
                    + '<span class="xalert-fail">This identity requires verification before using.</span><br />'
                    + 'Confirm sending verification to your mailbox?'
                  + '</div>'

                  + '<div class="verify-after hide">'
                    + 'Verification sent, it should arrive in minutes. Please check your mailbox and follow the instruction.'
                  + '</div>'

                  + '<div class="verify-error hide">'
                    + '<span class="xalert-fail">Requested too much, hold on awhile.</span><br />'
                    + 'Receive no verification email? It might be mistakenly filtered as spam, please check and un-spam. Alternatively, use ‘Manual Verification’.'
                  + '</div>'

              + '</fieldset>'
            + '</form>',

        footer: ''
          + '<button class="xbtn-white d d01 xbtn-forgotpwd hide" data-dialog-from="identification" data-widget="dialog" data-dialog-type="forgotpassword">Forgot Password...</button>'
          + '<button class="xbtn-white d d02 d04 xbtn-startover hide">Start Over</button>'
          + '<button class="pull-right d d04 xbtn-blue xbtn-verify hide">Verify</button>'
          + '<a href="#" class="pull-right xbtn-setup d d00 hide">Looking for sign-up?</a>'
          + '<button class="pull-right xbtn-blue d d01 d02 x-signin disabled hide">Start</button>'
          //+ '<button class="pull-right xbtn-blue d d04 xbtn-success hide">Done</button>'
          + '<button class="pull-right xbtn-white d d03 xbtn-isee hide">I See</button>'
          + '<button class="pull-right xbtn-white d hide">OK</button>'
          + '<button class="pull-right xbtn-white d xbtn-oauth hide">Back</button>',

        others: ''
          + '<div class="isee d d03 hide">'
            + '<div class="modal-body">'
              + '<div class="shadow title">“Sign-Up-Free”</div>'
              + '<p>Tired of signing up all around? Just authorize through your existing accounts from other websites, such as Twitter, <span class="strike">Facebook, Google, etc.</span> (soon to support)</p>'
              + '<p>We hate spam, will NEVER disappoint your trust.</p>'
              + '<p>Alternatively, traditional registration process with email and password is also available.</p>'
            + '</div>'
          + '</div>'
          + '<div class="authentication d d05 hide">'
            + '<div class="modal-body">'
              + '<div class="shadow title">Authentication</div>'
              //+ '<div class="center shadow title">through Twitter</div>'
              + '<div class="content">'
                + '<img class="hide" src="/static/img/loading.gif" width="32" height="32" />'
                + '<p class="redirecting hide">Redirecting to Twitter…</p>'
                + '<p class="xalert-fail hide">Failed to connect with Twitter server.</p>'
              + '</div>'
            + '</div>'
          + '</div>'
      }

    }

  };

  dialogs.sandbox = {

    options: {

      onHideAfter: function () {
        this.destory();
      },

      backdrop: false,

      viewData: {
        // class
        cls: 'mblack modal-sandbox',

        title: 'Sandbox',

        body: ''
          + '<div class="shadow title">“Rome wasn\'t built in a day.”</div>'
          + '<p><span class="x-sign">EXFE</span> [’ɛksfi] is still in <span class="pilot">pilot</span> stage (with <span class="sandbox">SANDBOX</span> tag). We’re building up blocks, consequently some bugs or unfinished pages may happen. Our apologies for any trouble you may encounter. Any feedback, please email <span class="feedback">feedback@exfe.com</span>. Much appreciated.</p>'
      }
    }
  };

  dialogs.welcome = {

    options: {
      events: {
        'click .xbtn-go': function (e) {
          var that = this;

          if (this._provider === 'email') {
            if (/^\/![a-zA-z0-9]+$/.test(window.location.pathname)) {
              window.location = window.location.pathname;
              return;
            }
          }
          else if (this.$('#follow').prop('checked') && this._token) {
            $.ajax({
              url: '/OAuth/followExfe?token=' + this._token,
              type: 'POST',
              data: {
                //token: this._token,
                identity_id: this._identity_id
              },
              success: function () {
                Store.remove('oauth');
              }
            });
          }

          that.hide();
        },

        'click .why': function (e) {
          this.$('.answer').toggleClass('hidden');
        }
      },

      onShowBefore: function (data) {
        var identity = data.identity
          , title = this.$('.title').eq(0);

        this._provider = identity.provider;
        this._identity_id = identity.id;
        this._token = data.token;
        this._following = data.following;

        if (identity.provider === 'email') {
          this.$('.provider-email').removeClass('hide');
          title.text('Hi, ' + identity.name + '.');
        } else {
          this.$('.provider-other').removeClass('hide');
          this.$('#follow').prop('checked', this._following);
          title.text('Hi, ' + Util.printExtUserName(identity) + '.');
        }
      },

      onHideAfter: function () {
        this.destory();
      },

      backdrop: false,

      viewData: {
        // class
        cls: 'mblack modal-large modal-wc',

        title: 'Welcome',

        body: ''
          + '<div class="shadow title"></div>'
          + '<div class="center shadow title" style="margin-bottom: 0;">Thanks for using <span class="x-sign">EXFE</span>.</div>'
          + '<p class="center">A utility for hanging out with friends.</p>'
          + '<div class="modal-content">'
            + '<p>We save you from calling up every one RSVP, losing in endless emails and messages off the point.</p>'
            + '<p><span class="x">·X·</span> (cross) is a gathering of people, for any intent. When you get an idea to call up friends to do something together, just “Gather a <span class="x">·X·</span>.</p>'
            + '<p><span class="x-sign">EXFE</span> your friends.</p>'
            + '<p class="provider-email hide" style="color: #191919;">*A welcome email has been sent to your mailbox. Please check to verify your address.*</p>'
            + '<div class="provider-other hide">'
              + '&nbsp;&nbsp;<span class="underline why">why?</span>'
              + '<label class="pull-left checkbox pointer">'
                + '<input type="checkbox" id="follow" value="1" checked />'
                + 'Follow @<span class="x-sign">EXFE</span> on Twitter.'
              + '</label>'
              + '<p class="pull-left answer hidden">So we could send you invitation PRIVATELY through Direct Message. We hate spam, will NEVER disappoint your trust.</p>'
            + '</div>'
          + '</div>',

        footer: ''
          + '<button class="pull-right xbtn-white xbtn-go">GO</button>'
      }
    }
  };


  dialogs.forgotpassword = {

    updateIdentity: function (identity) {
      var provider = identity.provider;
      var src = identity.avatar_filename;
      var $identity = this.$('.context-identity');
      this.$('.tab').addClass('hide');
      if (provider === 'email') {
        this.$('.tab1').removeClass('hide');
        this.$('.xbtn-send').data('identity', identity);
      }
      else {
        this.$('.tab2').removeClass('hide');
        this.$('.authenticate').data('identity', identity);
      }
      $identity.find('.avatar img').attr('src', identity.avatar_filename);
      $identity.find('.provider').attr('class', 'provider icon16-identity-' + identity.provider);
      $identity.find('.identity').text(identity.eun);
    },

    options: {
      onHideAfter: function (e) {
        // jquery.Event
        // TODO: 临时处理 , 首页 登录窗口
        if (e) {
          var dialog_from = this.dialog_from;
          if (dialog_from) {
            $('[data-dialog-type="' + dialog_from + '"]').data('dialog').hide();
          }
        }

        this.destory();
      },

      events: {
        'click .authenticate': function (e) {
          var that = this;
          that._oauth_ = $.ajax({
            url: '/OAuth/twitterAuthenticate',
            type: 'POST',
            dataType: 'JSON',
            data: {
              callback: window.location.href
            }
          })
            .done(function (data) {
              var code = data.meta.code;
              if (code === 200) {
                window.location.href = data.response.redirect;
              }
            }
          );
        },

        'click .caret-outer': function (e) {
          this.$('.dropdown-toggle').addClass('open');
          e.stopPropagation();
        },

        'hover .dropdown-menu > li': function (e) {
          var t = e.type,
              $e = $(e.currentTarget);

          $e[(t === 'mouseenter' ? 'add' : 'remove') + 'Class']('active');
        },

        'click .dropdown-menu > li': function (e) {
          var ids = this.$('.dropdown-menu').data('identities')
            , index = $(e.currentTarget).data('index');

          this.updateIdentity(ids[index]);
          // TODO: 优化
          //this.$('.dropdown-toggle').removeClass('open');
        },

        'click .xbtn-cancel': function (e) {
          var dialog_from = this.dialog_from;
          this.hide();
          if (dialog_from) {
            $('[data-dialog-type="' + dialog_from + '"]').data('dialog').element.removeClass('hide');
            $('#js-modal-backdrop').removeClass('hide');
            //$('[data-dialog-type="' + dialog_from + '"]').trigger('click.dialog.data-api');
            // TODO: 先简单处理，后面是否要保存 target 元素
          }
        },

        'click .xbtn-done': function (e) {
          this.hide(e);
          return;
        },

        'click .xbtn-send': function (e) {
          var that = this;
          var $e = $(e.currentTarget);
          if ($e.hasClass('disabled')) {
            return;
          }
          var i = $e.data('identity');
          if (i) {
            Api.request('forgotPassword'
              , {
                type: 'POST',
                data: {
                  provider: i.provider,
                  external_username: i.external_username
                },
                beforeSend: function (xhr) {
                  that.$('.send-before').removeClass('hide');
                  that.$('.send-after').addClass('hide');
                  $e.addClass('disabled');
                },
                complete: function (xhr) {
                  $e.removeClass('disabled');
                }
              }
              , function (data) {
                // 后台暂时没有 limit 限制
                if (data.action === 'VERIFYING') {
                  that.$('.identity').next().removeClass('hide');
                  $e.addClass('hide');
                  that.$('.xbtn-done').removeClass('hide');
                  that.$('.send-before').addClass('hide');
                  that.$('.send-after').removeClass('hide');
                }
              }
              //, function (data) {}
            );
          }
        }
      },

      onShowBefore: function (e) {
        var that = this
          , ids = $(e.currentTarget).data('source')
          , l
          , first;
        if (ids && (l = ids.length)) {
          first = ids[0];
          var eun = first.external_username;
          if (first.provider === 'twitter') {
            eun = '@' + first.external_username;
          }
          first.eun = eun;
          if (1 < l) {
            that.$('.context-identity').addClass('switcher');
            var s = '';
            for (var i = 0; i < l; i++) {
              var eun = ids[i].external_username;
              s += '<li data-index="' + i + '"><i class="pull-right icon16-identity-' + ids[i].provider + '"></i>';
              if (ids[i].provider === 'twitter') {
                eun = '@' + ids[i].external_username;
              }
              ids[i].eun = eun;
              s += '<span>' + eun + '</span>'
              s += '</li>';
            }
            that.$('.dropdown-menu').html(s).data('identities', ids);
          }

          this.updateIdentity(first);
        }
      },

      backdrop: false,

      viewData: {

        cls: 'mblack modal-fp',

        title: 'Forgot Password',

        body: ''
          + '<div class="shadow title">Forgot Password</div>'
          + '<div>You can reset your <span class="x-sign">EXFE</span> password through identity:</div>'
          + '<div class="context-identity">'
            + '<div class="pull-right avatar">'
              + '<img src="" alt="" width="40" height="40" />'
              + '<i class="provider"></i>'
            + '</div>'
            + '<div class="clearfix dropdown-toggle" data-toggle="dropdown">'
              + '<div class="pull-left box identity"></div>'
              + '<ul class="dropdown-menu"></ul>'
              + '<div class="pull-left caret-outer hide"><b class="caret"></b></div>'
            + '</div>'
          + '</div>'
          + '<div class="alert-label">'
            + '<div class="send-before tab tab1 hide">Confirm sending reset token to your mailbox?</div>'
            + '<div class="send-after tab hide">Verification sent, it should arrive in minutes. Please check your mailbox and follow the instruction.</div>'
            + '<div class="xalert-error tab hide">'
              + '<p>Requested too much, hold on awhile.</p>'
              + '<p>Receive no verification email? It might be mistakenly filtered as spam, please check and un-spam. Alternatively, use ‘Manual Verification’.</p>'
            + '</div>'

            + '<div class="authenticate-before tab tab2 hide">You will be directed to Twitter website to authenticate identity above, you can reset password then.</div>'
          + '</div>',

        footer: ''
          + '<button class="pull-right xbtn-white xbtn-done hide">Done</button>'
          + '<button class="pull-right xbtn-blue xbtn-send tab tab1 hide">Send</button>'
          + '<button class="pull-right xbtn-blue authenticate tab tab2 hide">Authenticate</button>'
          + '<a class="pull-right xbtn-cancel">Cancel</a>'

      }
    }

  }

  dialogs.changepassword = {

    options: {

      onHideAfter: function () {
        this.destory();
      },

      events: {
        'click .xbtn-resetpwd': function (e) {
          var user = Store.get('user');
          var identities = user.identities;
          var is = R.filter(identities, function (v, i) {
            if (v.status === 'CONNECTED') {
              return true;
            }
          });
          if (is.length === 1) {
            e.stopPropagation();
            this.hide();
            var d = new Dialog(dialogs.forgotpassword);
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
          var user = Store.get('user')
            , identities = user.identities
            , ids = [];

          R.each(identities, function (v, i) {
            if (v.status === 'CONNECTED') {
              ids.push(v);
            }
          });
          $(e.currentTarget).data('source', ids);
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

          var $e = $(e.currentTarget)
            , authorization = Store.get('authorization')
            , user_id = authorization.user_id
            , token = authorization.token;

          Api.request('setPassword'
            , {
              type: 'POST',
              params: { token: token },
              resources: { user_id: user_id },
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
              var meta = data && data.meta;
              if (meta) {
                if (meta.code === 403) {
                  var errorType = data.meta.errorType;
                  if (errorType === 'invalid_current_password') {
                    alert('Invalid current password.');
                  }
                }
                else if (meta.code === 401
                    && meta.errorType === 'authenticate_timeout') {

                  that.destory();

                  var $d = $('<div data-widget="dialog" data-dialog-type="authentication" data-destory="true" class="hide"></div>');
                  $('#app-tmp').append($d);
                  $d.trigger('click.dialog.data-api');
                }
              }
            }
          );

        }
      },

      onShowBefore: function () {
        var user = Store.get('user');
        this.$('.avatar > img').attr('src', user.avatar_filename);
        this.$('.username').text(user.name);
      },

      backdrop: false,

      viewData: {

        cls: 'modal-cp mblack modal-large',

        title: 'Change Password',

        body: ''
          + '<div class="shadow title">Change Password</div>'
          + '<form class="modal-form">'
            + '<fieldset>'
              + '<legend>Enter your current <span class="x-sign">EXFE</span> password, and set new one. All your identities share the same password for sign-in and account management.</legend>'

              + '<div class="clearfix context-user">'
                + '<div class="pull-left avatar">'
                  + '<img src="" width="40" height="40" />'
                + '</div>'
                + '<div class="pull-left username"></div>'
              + '</div>'

              + '<div class="control-group">'
                + '<label class="control-label" for="cppwd">Password:</label>'
                + '<div class="controls">'
                  + '<input class="input-large" id="cppwd" placeholder="Current password" type="password" autocomplete="off" />'
                  + '<i class="help-inline password-eye icon16-pass-hide pointer"></i>'
                + '</div>'
              + '</div>'

              + '<div class="control-group">'
                + '<label class="control-label" for="cp-npwd">New Password:</label>'
                + '<div class="controls">'
                  + '<input class="input-large" id="cp-npwd" placeholder="Set new EXFE password" type="password" autocomplete="off" />'
                  + '<i class="help-inline password-eye icon16-pass-hide pointer"></i>'
                + '</div>'
              + '</div>'

            + '</fieldset>'
          + '</form>',

        footer: ''
          + '<button class="xbtn-white xbtn-forgotpwd" data-dialog-from="changepassword" data-widget="dialog" data-dialog-type="forgotpassword">Forgot Password...</button>'
          + '<button class="pull-right xbtn-blue xbtn-success">Change</button>'
          + '<a class="pull-right xbtn-discard" data-dismiss="dialog">Discard</a>'

      }

    }

  };

  dialogs.addidentity = {
    options: {

      backdrop: false,

      events: {
        'click #password-eye': function (e) {
          var $e = $(e.currentTarget);
          var $input = $e.prev();
          $input.prop('type', function (i, val) {
            return val === 'password' ? 'text' : 'password';
          });
          $e.toggleClass('icon16-pass-hide icon16-pass-show');
        },
        'click .xbtn-forgotpwd': function (e) {
          var $btn = $(e.currentTarget)
            , disabled = $btn.hasClass('disabled');

          if (disabled) {
            e.stopPropagation();
            return false;
          }
        },
        'click .xbtn-startover': function (e) {
          this.$('.d1, d2').addClass('hide');
          this.$('.d0').removeClass('hide');
          this.$('#identity').val('').focus();
        },
        'click .xbtn-add': function (e) {
          var that = this
            , authorization = Store.get('authorization')
            , token = authorization.token
            , od = that._identity

          if (!od) {
            return false;
          }

          var provider = od.provider
            , external_username = od.external_username || od.external_identity || '';
          var defe = Api.request('addIdentity',
            {
              type: 'POST',
              params: { token: token },
              data: {
                external_username: external_username,
                provider: provider
              }
            },
            function (data) {
              var identity = data.identity
                , user = Store.get('user')
                , identities = user.identities;
              identities.push(identity);
              Store.set('user', user);
              var s = Handlebars.compile($('#jst-identity-item').html());
              var h = s(data.identity);
              $('.identity-list').append(h);
              that.destory();
            },
            function (data) {
              var meta = data && data.meta;
              if (meta
                  && meta.code === 401
                  && meta.errorType === 'authenticate_timeout') {

                that.destory();
                var $d = $('<div data-widget="dialog" data-dialog-type="authentication" data-destory="true" class="hide"></div>');
                $('#app-tmp').append($d);
                $d.trigger('click.dialog.data-api');
              }
            }
          );
          /*
          if (od) {
            od.password = this.$('#password').val();
            if (!od.password) {
              return;
            }
            Api.request('signin'
              , {
                type: 'POST',
                data: {
                  external_username: od.external_username,
                  provider: od.provider,
                  password: od.password,
                  name: '',
                  auto_signin: !od.auto_signin
                },
                beforeSend: function (xhr) {
                },
                complete: function (xhr) {
                }
              }
              , function (data) {
                  console.log(data);
                }
            );
          }
          */
        },
        'click .xbtn-verify': function (e) {
          var that = this;
          var $e = $(e.currentTarget);
          if ($e.hasClass('xbtn-success')) {
            that.$('.verify-after').addClass('hide');
            $e.removeClass('xbtn-success').text('Verify');
            that.hide();
            return false;
          }
          var provider = that._identity.provider;
          var external_id = that._identity.external_id;
          Api.request('verifyIdentity'
            , {
              type: 'POST',
              data: {
                provider: provider,
                external_username: external_id
              }
            }
            , function (data) {
              that.$('.verify-before').addClass('hide');
              if (data.action === 'VERIFYING') {
                that.$('.verify-after').removeClass('hide');
                $e.addClass('xbtn-success').text('Done');
              } else if (data.action === 'REDIRECT') {
                //$e.addClass('verify-error').removeClass('hide');
              } else {
                $e.addClass('verify-error').removeClass('hide');
              }
            }
          );
        },
        'click .xbtn-done': function (e) {
          var that = this
            , provider = that._identity.provider
            , external_username = that._identity.external_identity || that._identity.external_username
            , authorization = Store.get('authorization')
            , token = authorization.token;
          Api.request('addIdentity',
            {
              type: 'POST',
              params: { token: token },
              data: {
                external_username: external_username,
                provider: provider
              }
            },
            function (data) {
              var identity = data.identity
                , user = Store.get('user')
                , identities = user.identities;
              identities.push(identity);
              Store.set('user', user);
              var s = Handlebars.compile($('#jst-identity-item').html());
              var h = s(data.identity);
              $('.identity-list').append(h);
              that.destory();
            },
            function (data) {
              var meta = data && data.meta;
              if (meta
                  && meta.code === 401
                  && meta.errorType === 'authenticate_timeout') {

                that.destory();
                var $d = $('<div data-widget="dialog" data-dialog-type="authentication" data-destory="true" class="hide"></div>');
                $('#app-tmp').append($d);
                $d.trigger('click.dialog.data-api');
              }
            }
          );
        }
      },

      onShowBefore: function () {
        this.element.removeClass('hide');
        this.$('#identity').focusend();
      },

      onHideAfter: function () {
        this.destory();
      },

      viewData: {
        // class
        cls: 'mblack modal-id modal-ai',

        title: 'Add Identity',

        body: ''
            + '<div class="shadow title">Add Identity</div>'
            + '<div class="clearfix">'
              + '<div class="pull-left authorize">Authenticate with:</div>'
              + '<div class="pull-left oauth">'
                + '<a href="#" class="oauth-twitter" data-oauth="twitter">twitter</a>'
              + '</div>'
            + '</div>'
            + '<div class="orspliter">or</div>'
            + '<form class="modal-form">'
              + '<fieldset>'
                + '<legend>Use your online identity:</legend>'

                  + '<div class="clearfix control-group">'
                    + '<label class="control-label" for="identity">Identity: <span class="xalert-message"></span></label>'
                    + '<div class="pull-right user-identity hide">'
                      + '<div class="avatar">'
                        + '<img src="" alt="" width="40" height="40" />'
                        + '<i class="provider"></i>'
                      + '</div>'
                    + '</div>'
                    + '<div class="controls">'
                      + '<input type="text" class="input-large identity" id="identity" autocomplete="off" data-widget="typeahead" data-typeahead-type="identity" placeholder="Enter your email" />'
                      + '<i class="help-subject"></i>'
                      + '<i class="help-inline small-loading hide"></i>'
                      + '<div class="xalert xalert-error hide" style="margin-top: 5px;"></div>'

                      + '<div class="xalert xalert-error authenticate hide" style="margin-top: 5px;">'
                        + '<span class="xalert-fail">Please directly authenticate identity above.</span><br />To enable password sign-in for this identity, set an <span class="x-sign">EXFE</span> password first in your profile page.'
                      + '</div>'

                    + '</div>'
                  + '</div>'

                  + '<div class="control-group d d0">'
                    + '<label class="control-label" for="password">Password: <span></span></label>'
                    + '<div class="controls">'
                      + '<input type="password" class="input-large" id="password" autocomplete="off" placeholder="Identity\'s EXFE password" />'
                      + '<i class="help-inline icon16-pass-hide pointer" id="password-eye"></i>'
                    + '</div>'
                  + '</div>'

                  + '<div class="verify-before d d1 hide">'
                    + '<span class="xalert-fail">This identity requires verification before using.</span><br />'
                    + 'Confirm sending verification to your mailbox?'
                  + '</div>'

                  + '<div class="verify-after d2 hide">'
                    + 'Verification sent, it should arrive in minutes. Please check your mailbox and follow the instruction.'
                  + '</div>'

              + '</fieldset>'
            + '</form>',

        footer: ''
          + '<button class="xbtn-white xbtn-startover d d1 hide">Start Over</button>'
          + '<button class="xbtn-white xbtn-forgotpwd d d0 disabled" data-dialog-from="addidentity" data-widget="dialog" data-dialog-type="forgotpassword">Forgot Password...</button>'
          + '<button class="pull-right xbtn-blue xbtn-add d d0">Add</button>'
          + '<button class="pull-right xbtn-blue xbtn-verify d d1 hide">Verify</button>'
          + '<button class="pull-right xbtn-white xbtn-done d d2 hide">Done</button>'
      }

    },

    availability: false,

    init: function () {
      var that = this;
      Bus.off('widget-dialog-identification-auto');
      Bus.on('widget-dialog-identification-auto', function (data) {
        if (data) {
          if (data.identity) {
            that._identity = data.identity;
            that.$('.user-identity').removeClass('hide')
              .find('img').attr('src', data.identity.avatar_filename)
              .next().attr('class', 'provider icon16-identity-' + data.identity.provider);
          } else {
            that.$('.user-identity').addClass('hide');
            that._identity = null;
          }

          var registration_flag = data.registration_flag;
          // SIGN_IN
          if (registration_flag === 'SIGN_IN') {
            that.$('.d1, .d2').addClass('hide');
            that.$('.d0').removeClass('hide');
            that.$('.xbtn-forgotpwd').removeClass('disabled').data('source', [that._identity]);
          }
          // SIGN_UP 新身份
          else if (registration_flag === 'SIGN_UP') {
            that._identity = Util.parseId(that.$('#identity').val());
            that.$('.d0, .d1').addClass('hide');
            that.$('.d2').removeClass('hide');
          }
          // AUTHENTICATE
          else if (registration_flag === 'AUTHENTICATE') {
            that._identity = Util.parseId(that.$('#identity').val());
            that.$('.d1, .d2').addClass('hide');
            that.$('.d0').removeClass('hide');
            that.$('label[for="password"]').parent().addClass('hide');
          }
          // VERIFY
          else if (registration_flag === 'VERIFY') {
            that.$('.d0, .d2').addClass('hide');
            that.$('.d1').removeClass('hide');
          }

          that.$('.xbtn-success').removeClass('disabled');
        } else {
          that.$('.xbtn-success').addClass('disabled');
          that.$('.xbtn-forgotpwd').addClass('disabled').data('source', null);
        }


      });
      Bus.off('widget-dialog-identification-nothing');
      Bus.on('widget-dialog-identification-nothing', function () {
      });
    }

  };

  dialogs.addIdentityAfterSignIn = {

    options: {

      events: {
        'click .xbtn-cancel': function () {
          this.destory();
        },
        'click .xbtn-add': function () {
          var that = this
            , authorization = Store.get('authorization')
            , token = authorization.token
            , external_username = this._identity.external_username
            , provider = this._identity.provider;
          var defe = Api.request('addIdentity',
            {
              type: 'POST',
              params: { token: token },
              data: {
                external_username: external_username,
                provider: provider
              }
            },
            function (data) {
              var identity = data.identity
                , user = Store.get('user')
                , identities = user.identities;
              identities.push(identity);
              Store.set('user', user);
              that.destory();
              window.location.href = '/';
            },
            function (data) {
              var meta = data && data.meta;
              if (meta
                  && meta.code === 401
                  && meta.errorType === 'authenticate_timeout') {

                that.destory();
                var $d = $('<div data-widget="dialog" data-dialog-type="authentication" data-destory="true" class="hide"></div>');
                $('#app-tmp').append($d);
                $d.trigger('click.dialog.data-api');
              }
            }
          );
        }
      },

      backdrop: false,
      viewData: {
        cls: 'mblack modal-aifsi',
        title: 'Set Up Account',
        body: ''
            + '<div class="shadow title">Add Identity</div>'
            + '<div class="clearfix context-user">'
              + '<div class="pull-left avatar">'
                + '<img width="40" height="40" alt="" src="" />'
              + '</div>'
              + '<div class="pull-left username"></div>'
            + '</div>'
            + '<div>Please authorize identity underneath through Twitter to add into your account above.</div>'
            + '<div class="context-identity">'
              + '<div class="pull-right avatar">'
                + '<img width="40" height="40" alt="" src="" />'
                + '<i class="provider"></i>'
              + '</div>'
              + '<div class="clearfix">'
                + '<div class="pull-left box identity"></div>'
              + '</div>'
            + '</div>',

        footer: ''
          + '<button class="pull-right xbtn-blue xbtn-add">Add</button>'
          + '<a class="pull-right xbtn-cancel">Cancel</a>'
      },

      onShowBefore: function (e) {
        var data = $(e.currentTarget).data('source')
          , identity = data.identity
          , user = Store.get('user');
        this._identity = identity;
        this.$('.context-user')
          .find('img').attr('src', user.avatar_filename)
          .parent()
          .next().text(user.name);
        this.$('.context-identity')
          .find('img').attr('src', identity.avatar_filename)
          .next().addClass('icon16-identity-' + identity.provider);
        this.$('.identity').text(Util.printExtUserName(identity));
        if (identity.provider !== 'email') {
          this.$('.xbtn-done').text('Authorize');
        }
      }

    }

  };


  // merge identity
  dialogs.mergeidentity = {

    options: {
      onHideAfter: function () {
        this.destory();
      },

      events: {
        'click .xbtn-donot': function (e) {
          this.hide();
        },
        'click .xbtn-merge': function (e) {
          var that = this
            , $ids = this.$('.merge-list').find('input:checked')
            , ids = []
            , authorization = Store.get('authorization')
            , token = authorization.token;
          if ($ids.length) {
            for (var i = 0, l = $ids.length; i < l; ++i) {
              ids.push($ids.eq(i).parents('li').data('identity-id'));
            }
            Api.request('mergeIdentities'
              , {
                type: 'POST',
                //params: { token: token },
                params: { token: this.browsing_token },
                data: {
                  //browsing_identity_token: this.browsing_token,
                  identity_ids: '[' + ids.toString() + ']'
                }
              }
              , function (data) {
                  that.hide();
                  window.location.href = '/';
                }
            );
            return;
          }
          this.hide();
        }
      },

      backdrop: false,

      viewData: {

        //class
        cls: 'mblack modal-mi',

        title: 'Merge Identity',

        body: ''
          + '<div class="shadow title">Merge Identity</div>'
          + '<div>You just successfully merged identity underneath:</div>'
          + '<div class="context-identity">'
            + '<div class="pull-right avatar">'
              + '<img width="40" height="40" alt="" src="" />'
              + '<i class="provider"></i>'
            + '</div>'
            + '<div class="clearfix">'
              + '<div class="pull-left box identity"></div>'
            + '</div>'
          + '</div>'
          + '<div class="clearfix merge-container">'
            + '<div class="alert-label">Following identities might also belong to you. Merge them into your current account to avoid switching identities back and forth.</div>'
            + '<div class="merge-list">'
              + '<ul class="unstyled">'
              + '</ul>'
            + '</div>'
          + '</div>',

        footer: ''
          + '<button class="pull-right xbtn-blue xbtn-merge" style="margin-left: 10px;">Merge</button>'
          + '<button class="pull-right xbtn-white xbtn-donot">Do NOT</button>'

      },

      onShowBefore: function (e) {
        var data = $(e.currentTarget).data('source')
          , merged_identity = data.merged_identity
          , browsing_token = data.browsing_token
          , mergeable_user = data.mergeable_user
          , identities = mergeable_user.identities
          , li = '<li class="clearfix" data-identity-id="{{id}}">'
                + '<label for="identity-{{i}}">'
                  + '<input class="pull-left" id="identity-{{i}}" name="identity-{{i}}" type="checkbox" />'
                  + '<div class="pull-left box identity">{{external_username}}</div>'
                  + '<div class="pull-right avatar">'
                    + '<img width="40" height="40" alt="" src="{{avatar_filename}}" />'
                    + '<i class="provider icon16-identity-{{provider}}"></i>'
                  + '</div>'
                + '</label>'
              + '</li>'
          , $ul = this.$('.merge-list ul');
        this.$('.context-identity')
          .find('img').attr('src', merged_identity.avatar_filename);
        this.$('.context-identity')
          .find('.identity').text(Util.printExtUserName(merged_identity));
        this.browsing_token = browsing_token;
        for (var i = 0, l = identities.length; i < l; ++i) {
          var ll = li;
          $ul.append(
            $(li
              .replace('{{id}}', identities[i].id)
              .replace(/\{\{i\}\}/g, i)
              .replace('{{external_username}}', Util.printExtUserName(identities[i]))
              .replace('{{avatar_filename}}', identities[i].avatar_filename)
              .replace('{{provider}}', identities[i].provider)
             ));
        }
      }

    }

  };


  // emial Verification
  dialogs.verification_email = {

    options: {

      onHideAfter: function () {
        this.destory();
      },

      events: {
        'click .xbtn-verify': function (e) {
          var $e = $(e.currentTarget);
          if ($e.hasClass('disabled') || $e.hasClass('success')) {
            if ($e.hasClass('success')) {
              this.hide();
            }
            return;
          }
          var that = this;
          var identity_id = $e.data('identity_id');
          var authorization = Store.get('authorization');
          var token = authorization.token;
          Api.request('verifyUserIdentity'
            , {
              type: 'POST',
              params: { token: token },
              data: { identity_id: identity_id },
              beforeSend: function (data) {
                $e.addClass('disabled');
              },
              complete: function () {
                $e.removeClass('disabled');
              }
            }
            , function (data) {
              that.$('.verify-before').addClass('hide');
              if (data.action === 'VERIFYING') {
                that.$('.verify-after').removeClass('hide');
                $e.text('Done').addClass('success');
              } else {
                that.$('.xalert-error').removeClass('hide');
              }
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
          + '<div class="box identity"></div>'
          + '<p class="verify-before">Confirm sending verification to your mailbox?</p>'
          + '<p class="verify-after hide">Verification sent, it should arrive in minutes. Please check your mailbox and follow the instruction.</p>'
          + '<div class="xalert-error hide"><span class="xalert-fail">Requested too much, hold on awhile.</span><br />Receive no verification email? It might be mistakenly filtered as spam, please check and un-spam. Alternatively, use ‘Manual Verification’.</div>',

        footer: ''
          //+ '<button class="xbtn-white">Manual Verification</button>'
          + '<button class="pull-right xbtn-blue xbtn-verify">Verify</button>'
          + '<a class="pull-right xbtn-cancel">Cancel</a>'

      },

      onShowBefore: function (e) {
        var $e = $(e.currentTarget);
        var identity_id = $e.data('identity-id') || $e.parents('li').data('identity-id');
        var user = Store.get('user');
        var identity = R.filter(user.identities, function (v, i) {
          if (v.id === identity_id) {
            return true;
          }
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
          if ($e.hasClass('disabled') || $e.hasClass('success')) {
            if ($e.hasClass('success')) {
              this.hide();
            }
            return;
          }
          var identity_id = $e.data('identity_id');
          var authorization = Store.get('authorization');
          var token = authorization.token;

          Api.request('verifyUserIdentity'
            , {
              type: 'POST',
              params: { token: token },
              data: { identity_id: identity_id },
              beforeSend: function (data) {
                $e.addClass('disabled');
              },
              complete: function () {
                $e.removeClass('disabled');
              }
            }
            , function (data) {
                window.location.href = data.url;
              }
          );
        },

        'click .xbtn-cancel': function (e) {
          this.hide();
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
          + '<div class="box identity"></div>'
          + '<p>You will be directed to Twitter website to authenticate. Don’t forget to follow @<span class="x-sign">EXFE</span> so we could send you invitation PRIVATELY through Direct Message.</p>'
          + '<p>We hate spam, will NEVER disappoint your trust.</p>',

        footer: ''
          + '<button class="pull-right xbtn-blue xbtn-verify">Verify</button>'
          + '<a class="pull-right xbtn-cancel">Cancel</a>'

      },

      onHideAfter: function (e) {
        this.destory();
      },

      onShowBefore: function (e) {
        var $e = $(e.currentTarget);
        var identity_id = $e.parents('li').data('identity-id');
        var user = Store.get('user');
        var identity = R.find(user.identities, function (v) {
          if (v.id === identity_id) {
            return true;
          }
        });
        this.$('.xbtn-verify').data('identity_id', identity.id);
        this.$('.identity').text(Util.printExtUserName(identity));
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

      onHideAfter: function () {
        this.destory();
      },

      events: {
        'click .password-eye': function (e) {
          var $e = $(e.currentTarget);
          var $input = $e.prev();
          $input.prop('type', function (i, val) {
            return val === 'password' ? 'text' : 'password';
          });
          $e.toggleClass('icon16-pass-hide icon16-pass-show');
        },
        'click .xbtn-success': function (e) {
          var that = this;
          var stpwd = that.$('#stpwd').val();
          var xbtn = that.options.srcNode;

          // note: 暂时先用 alert
          if (!stpwd) {
            if (!stpwd) {
              alert('Please set EXFE password.');
            }
            return;
          }

          e.preventDefault();

          var $e = $(e.currentTarget)
            , user = this._user
            , token = this._token;
          if (this._setup) {
            Api.request('setPassword'
              , {
                type: 'POST',
                params: { token: token },
                resources: { user_id: user.id },
                data: { new_password: stpwd },
                beforeSend: function (xhr) {
                  $e.addClass('disabled loading');
                },
                complete: function (xhr) {
                  $e.removeClass('disabled loading');
                }
              }
              , function (data) {
                Bus.on('app:user:signin', data.token, data.user_id, true);
                xbtn
                  .data('dialog', null)
                  .data('dialog-type', 'changepassword')
                  .find('span').text('Change Password...');
                $('.set-up').remove();
                that.hide();
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
          }
          else {
            Api.request('resetPassword',
              {
                type: 'POST',
                data: {
                  token: token,
                  name: user.name,
                  password: stpwd
                }
              },
              function (data) {
                Store.set('authorization', data.authorization);
                window.location.href = '/';
                that.hide();
              }
            );
          }
        }
      },

      backdrop: false,

      viewData: {

        // class
        cls: 'mblack modal-sp',

        title: 'Set Password',

        body: ''
          + '<div class="shadow title">Set Password</div>'
          + '<form class="modal-form">'
            + '<fieldset>'
              + '<legend>Please set <span class="x-sign">EXFE</span> password of your account.<br />All your identities share the same password for sign-in and account management.</legend>'

              + '<div class="clearfix context-user">'
                + '<div class="pull-left avatar">'
                  + '<img width="40" height="40" alt="" src="" />'
                + '</div>'
                + '<div class="pull-left username"></div>'
              + '</div>'

              + '<div class="control-group">'
                + '<label class="control-label" for="stpwd">Password:</label>'
                + '<div class="controls">'
                  + '<input class="input-large" id="stpwd" placeholder="Set EXFE password" type="password" autocomplete="off" />'
                  + '<i class="help-inline password-eye icon16-pass-hide pointer"></i>'
                + '</div>'
              + '</div>'

            + '</fieldset>'
          + '</form>',

        footer: ''
          + '<button class="pull-right xbtn-blue xbtn-success">Done</button>'

      },

      onShowBefore: function (e) {
        var data = $(e.currentTarget).data('source');
        this._setup = false;
        if (data) {
          this._user = data.user;
          this._token = data.token;
          this._setup = data.setup;
        }
        else {
          this._user = Store.get('user');
          this._token = Store.get('authorization').token;
        }
        this.$('.avatar img').attr('src', this._user.avatar_filename);
        this.$('.username').text(this._user.name);
      }

    }

  };


  // Set Up Account
  // --------------------------------------------------------------------------
  dialogs.setup_email = {

    options: {

      onHideAfter: function () {
        this.destory();
      },

      events: {

        'blur #name': function (e) {
          var val = Util.trim($(e.currentTarget).val());
          var $name = this.$('[for="name"]');
          var $text = $name.find('span');
          if (!val) {
            $name.addClass('label-error');
            $text.text('');
          } else if (Util.utf8length(val) > 30) {
            $text.text('Too long.');
            $name.addClass('label-error');
          } else if (Util.zh_CN.test(val)) {
            $name.addClass('label-error');
            $text.text('Invalid character.');
          } else {
            $name.removeClass('label-error');
            $text.text('');
          }
        },

        'blur #password': function (e) {
          var val = Util.trim($(e.currentTarget).val());
          var $pass = this.$('[for="password"]');
          var $text = $pass.find('span');
          if (!val) {
            $pass.addClass('label-error');
            $text.text('Password incorrect.');
          } else {
            $pass.removeClass('label-error');
            $text.text('');
          }
        },

        'click #password-eye': function (e) {
          var $e = $(e.currentTarget);
          var $input = $e.prev();
          $input.prop('type', function (i, val) {
            return val === 'password' ? 'text' : 'password';
          });
          $e.toggleClass('icon16-pass-hide icon16-pass-show');
        },

        'click .xbtn-success': function (e) {
          var that = this;
          var isUserToken = this._tokenType === 'user';

          var forward = this._forward;
          var page = this._page;

          var api_url = isUserToken ? 'resetPassword' : 'setupUserByInvitationToken';
          var reqData = {};

          reqData.name = $.trim(this.$('#name').blur().val());
          reqData.password = this.$('#password').blur().val();

          if (isUserToken) {
            reqData.token = this._originToken;
          }
          else {
            reqData.invitation_token = this._originToken;
          }

          if (this.$('[for="name"]').hasClass('label-error')
              && this.$('[for="password"]').hasClass('label-error')) {
            return;
          }

          Api.request(api_url,
            {
              type: 'POST',
              data: reqData
            },
            function (data) {

              if (page === 'resolve') {
                var authorization = Store.get('authorization');
                if (!authorization) {
                  Store.set('authorization', data.authorization);
                  Store.set('user', that._browsing_user);
                  window.location.href = '/';
                } else {
                  $('#app-user-menu').find('.set-up').remove();
                  var $bi = $('#app-browsing-identity');
                  var settings = $bi.data('settings');
                  settings.setup = false;
                  $bi.data('settings', settings).trigger('click.data-api');
                }
              }
              else {
                var authorization = data.authorization
                Bus.emit('app:user:signin:after', function () {
                  window.location.href = '/';
                });
                Bus.emit('app:user:signin', authorization.token, authorization.user_id);
              }
              that.hide();
            }
          );

        }

      },

      backdrop: false,

      viewData: {

        // class
        cls: 'mblack modal-su',

        title: 'Set Up Account',

        body: ''
          + '<div class="shadow title">Welcome to <span class="x-sign">EXFE</span></div>'
          + '<form class="modal-form">'
            + '<fieldset>'
              + '<legend>For easier further use, please set up account of your identity underneath. Otherwise, <span class="underline">sign in</span> your existing account to merge with this identity.</legend>'

                + '<div class="clearfix control-group">'
                  + '<div class="pull-right user-identity">'
                    + '<img class="avatar" src="" alt="" width="40" height="40" />'
                    + '<i class="provider"></i>'
                  + '</div>'
                  + '<div class="identity box"></div>'
                + '</div>'

                + '<div class="control-group">'
                  + '<label class="control-label" for="name">Display name: <span></span></label>'
                  + '<div class="controls">'
                    + '<input type="text" class="input-large" id="name" autocomplete="off" placeholder="Your recognizable name" />'
                  + '</div>'
                + '</div>'

                + '<div class="control-group">'
                  + '<label class="control-label" for="password">Password: <span></span></label>'
                  + '<div class="controls">'
                    + '<input type="password" class="input-large" id="password" autocomplete="off" placeholder="Set EXFE password" />'
                    + '<i class="help-inline icon16-pass-hide pointer" id="password-eye"></i>'
                  + '</div>'
                + '</div>'

            + '</fieldset>'
          + '</form>',

        footer: ''
          + '<button class="xbtn-white xbtn-sitm" data-widget="dialog" data-dialog-type="identification" data-dialog-tab="d00">Sign In to Merge…</button>'
          + '<button class="pull-right xbtn-blue xbtn-success">Done</button>'
          + '<a class="pull-right xbtn-discard" data-dismiss="dialog">Cancel</a>'
      },

      onShowBefore: function (e) {
        var data = $(e.currentTarget).data('source');
        if (!data) return;
        var identity = data.identity;
        this._browsing_user = data.browsing_user;
        this._tokenType = data.tokenType;
        this._originToken = data.originToken;
        this._forward = data.forward || '/';
        this._page = data.page;
        this.$('#name').val(data.user_name || '');
        this.$('.identity').text(Util.printExtUserName(identity));
        this.$('.avatar')
          .attr('src', identity.avatar_filename)
          .next().addClass('icon16-identity-' + identity.provider);
        this.$('.xbtn-siea').data('source', Util.printExtUserName(identity));
      }

    }

  };


  // Set Up Twitter
  dialogs.setup_twitter = {

    options: {

      onHideAfter: function () {
        // abort ajax
        if (this._oauth_) {
          this._oauth_.abort();
        }
        this.destory();
      },

      events: {
        'click .authorize': function (e) {
            this._oauth_ = $.ajax({
              url: '/OAuth/twitterAuthenticate',
              type: 'POST',
              dataType: 'JSON',
              beforeSend: function (xhr) {
              },
              success: function (data) {
                var code = data.meta.code;
                if (code === 200) {
                  window.location.href = data.response.redirect;
                } else {
                }
              }
            });
        }
      },

      backdrop: false,

      viewData: {

        // class
        cls: 'mblack modal-su',

        title: 'Set Up Account',

        body: ''
          + '<div class="shadow title">Welcome to <span class="x-sign">EXFE</span></div>'
          + '<form class="modal-form">'
            + '<fieldset>'
              + '<legend>You’re browsing as identity underneath, please authorize through Twitter to set up your <span class="x-sign">EXFE</span> account.</legend>'

                + '<div class="clearfix control-group">'
                  + '<div class="pull-right user-identity">'
                    + '<img class="avatar" src="" alt="" width="40" height="40" />'
                    + '<i class="provider"></i>'
                  + '</div>'
                  + '<div class="box identity"></div>'
                + '</div>'

                + '<div class="clearfix">'
                  + '<button class="pull-right xbtn-blue authorize">Authorize</button>'
                  + '<a class="pull-right underline pointer cancel" data-dismiss="dialog">Cancel</a>'
                + '</div>'

                + '<div class="spliterline"></div>'

                + '<div>Otherwise, sign in your existing <span class="x-sign">EXFE</span> account to merge with this identity.</div>'

            + '</fieldset>'
          + '</form>',

        footer: ''
          + '<button class="pull-right xbtn-white xbtn-siea" data-widget="dialog" data-dialog-type="identification" data-dialog-tab="d00">Sign In and Add…</button>'
      },

      onShowBefore: function (e) {
        var data = $(e.currentTarget).data('source');
        if (!data) return;
        var identity = data.identity;
        this._tokenType = data.tokenType;
        this._originToken = data.originToken;
        this.$('.identity').text(Util.printExtUserName(identity));
        this.$('.avatar')
          .attr('src', identity.avatar_filename)
          .next().addClass('icon16-identity-' + identity.provider);
        this.$('.xbtn-siea').data('source', Util.printExtUserName(identity));
      }

    }

  };


  // Browsing Identity
  // --------------------------------------------------------------------------
  dialogs.browsing_identity = {

    options: {

      onHideAfter: function () {
        this.destory();
      },

      events: {

        'click .xbtn-go': function (e) {
          window.location.href = '/';
        },

        'click .xbtn-merge': function (e) {
          var that = this
            , token = this._token
            , identity = this._identity;
          Api.request('mergeIdentities'
            , {
              type: 'POST',
              //params: { token: token },
              params: { token: token },
              data: {
                //browsing_identity_token: this.browsing_token,
                identity_ids: '[' + identity.id + ']'
              }
            }
            , function (data) {
                that.hide();
                if (data.mergeable_user) {
                  var mergeable_user = data.mergeable_user;
                  var d = $('<div id="js-dialog-merge" data-destory="true" data-widget="dialog" data-dialog-type="mergeidentity">');
                  var user = Store.get('user');
                  d.data('source', {
                    merged_identity: R.find(user.identities, function (v) {
                      if (v.id === identity.id) { return true; }
                    }),
                    browsing_token: token,
                    mergeable_user: data.mergeable_user
                  });
                  d.appendTo($('#app-tmp'));
                  d.trigger('click.dialog.data-api');
                  $('.modal-mi').css('top', 230);
                }
                else {
                  window.location.href = '/';
                }
              }
          );
        }

      },

      backdrop: false,

      viewData: {

        // class
        cls: 'mblack modal-bi',

        title: 'Browsing Identity',

        body: ''
          + '<div class="shadow title">Browsing Identity</div>'
          + '<div class="user hide">'
            + '<div>You’re currently signed in account underneath, you can continue with this account.</div>'
            + '<div class="clearfix context-user">'
              + '<div class="pull-left avatar">'
                + '<img width="40" height="40" alt="" src="" />'
              + '</div>'
              + '<div class="pull-left username"></div>'
            + '</div>'
            + '<div class="clearfix">'
              + '<button class="pull-right xbtn-white xbtn-go">Go</button>'
              + '<a class="pull-right xbtn-cancel" data-dismiss="dialog">Cancel</a>'
            + '</div>'
            + '<div class="spliterline"></div>'
          + '</div>'
          + '<div class="browsing-tips"><span class="tip-0 hide">Otherwise, you’re</span><span class="tip-1 hide">You’re</span> currently browsing this page as identity underneath, please choose an option to continue.</div>'
          + '<div class="context-identity">'
            + '<div class="pull-right avatar">'
              + '<img width="40" height="40" alt="" src="" />'
              + '<i class="provider"></i>'
            + '</div>'
            + '<div class="clearfix">'
              + '<div class="pull-left box identity"></div>'
            + '</div>'
          + '</div>',

        footer: ''
          + '<button class="pull-right xbtn-blue xbtn-merge hide">Merge into account above</button>'
          + '<button class="xbtn-white xbtn-sias hide" data-widget="dialog" data-dialog-type="identification" data-dialog-tab="d00">Sign In and Switch</button>'
          + '<button class="xbtn-white xbtn-sui hide" data-widget="dialog" data-dialog-type="setup_email">Set Up Identity</button>'

      },

      onShowBefore: function (e) {
        var settings = $(e.currentTarget).data('settings');
        if (!settings) return;
        var user = settings.normal
          , browsing_user = settings.browsing
          , setup = settings.setup
          , action = settings.action;

        this._token = settings.originToken;
        this._user = user;
        this._browsing_user = browsing_user;
        this._setup = setup;
        this._action = action;
        this._tokenType = settings.tokenType;

        if (this._user) {
          this.$('.user')
            .removeClass('hide')
            .find('img')
            .attr('src', user.avatar_filename)
            .parent()
            .next().text(user.name || user.nickname);
          this.$('.xbtn-merge').removeClass('hide');
          this.$('.browsing-tips').find('.tip-0').removeClass('hide');
        }
        else {
          this.$('.xbtn-sias, .xbtn-sui').addClass('pull-right');
          this.$('.browsing-tips').find('.tip-1').removeClass('hide');
        }

        this.$('browsing-tips').find('span').eq(this._user ? 0 : 1).removeClass('hide')

        // browsing default identity
        var bdidentity = browsing_user.identities[0];
        this._identity = bdidentity;
        var beun = Util.printExtUserName(bdidentity);

        this.$('.context-identity')
          .find('img')
          .attr('src', bdidentity.avatar_filename)
          .next().addClass('icon16-identity-' + bdidentity.provider)
        this.$('.context-identity')
          .find('.identity').text(beun);

        //if (!this._setup) { // test
        if (this._setup) {
          this.$('.xbtn-sui')
            .removeClass('hide')
            .attr('data-dialog-type', 'setup_' + bdidentity.provider)
            .data('source', {
              identity: bdidentity,
              originToken: settings.originToken,
              tokenType: settings.tokenType
            }
          );
        }
        else {
          this.$('.xbtn-sias')
            .removeClass('hide')
            .data('source', beun);
        }
      }

    }

  };


  // Read-only Browsing
  dialogs.read_only = {

    options: {

      onHideAfter: function () {
        this.destory();
      },

      backdrop: false,

      viewData: {

        // class
        cls: 'mblack modal-ro',

        title: 'Read-only Browsing',

        body: ''
          + '<div class="shadow title">Read-only Browsing</div>'
          + '<form class="modal-form">'
            + '<fieldset>'
              + '<legend>You’re browsing this page in read-only mode as <span></span> underneath. To change anything on this page, please <span class="underline">sign in</span> first.</legend>'

                + '<div class="user hide">'
                  + '<div class="identity">'
                    + '<img class="avatar" src="" width="40" height="40" />'
                    + '<span></span>'
                  + '</div>'
                + '</div>'

                + '<div class="clearfix control-group browsing-identity hide">'
                  + '<div class="pull-right user-identity">'
                    + '<img class="avatar" src="" alt="" width="40" height="40" />'
                    + '<i class="provider"></i>'
                  + '</div>'
                  + '<div class="box identity"></div>'
                + '</div>'

            + '</fieldset>'
          + '</form>',

        footer: ''
          + '<button class="pull-right xbtn-blue" data-widget="dialog" data-dialog-type="identification" data-dialog-tab="d00">Sign In...</button>'
          + '<a class="pull-right xbtn-discard" data-dismiss="dialog">Cancel</a>'
      },

      onShowBefore: function (e) {
        var settings = $(e.currentTarget).data('settings')
        if (!settings) return;
        var isBrowsing = settings.isBrowsing
        var beun = Util.printExtUserName(settings.identities[0]);
        this.$('legend span').eq(0).text(isBrowsing ? 'identity' : 'user');
        this.$('.xbtn-blue').data('source', beun);
        if (isBrowsing) {
          var bi = this.$('.browsing-identity').removeClass('hide');
          bi.find('.identity').text(beun);
          bi.find('.avatar').attr('src', settings.identities[0].avatar_filename);
          bi.find('.provider').addClass('icon16-identity-' + settings.identities[0].provider)
        } else {
          var u = this.$('.user').removeClass('hide')
          u.find('span').text(settings.name);
          u.find('.avatar').attr('src', settings.avatar_filename);
        }
      }

    }

  };


  // revoked identity
  dialogs.revoked = {

    options: {

      onHideAfter: function () {
        this.destory();
      },

      backdrop: false,

      viewData: {

        // class
        cls: 'mblack modal-re',

        title: 'Revoked Identity',

        body: ''
          + '<div class="shadow title">Revoked Identity</div>'

      }

    }

  };


  dialogs.authentication = {

    updateIdentity: function (identity) {
      var provider = identity.provider;
      var src = identity.avatar_filename;
      var $identity = this.$('.context-identity');
      $identity.find('.avatar img').attr('src', identity.avatar_filename);
      $identity.find('.provider').attr('class', 'provider icon16-identity-' + identity.provider);
      $identity.find('.identity').text(identity.eun);
    },

    options: {

      viewData: {

        cls: 'mblack modal-au',

        title: 'Authentication',

        body: ''
          + '<div class="shadow title">Authorization</div>'
          + '<div class="d0 hide">'
            + '<div class="detials">You’re about to change your account details, for security concerns, please authenticate your account.</div>'
            + '<div class="clearfix context-user">'
              + '<div class="pull-left avatar">'
                + '<img src="" alt="" width="40" height="40" />'
              + '</div>'
              + '<div class="pull-left username"></div>'
            + '</div>'
            + '<div class="modal-form">'
              + '<div class="control-group">'
                + '<label class="control-label" for="password">Password: <span></span></label>'
                + '<div class="controls">'
                  + '<input type="password" class="input-large" id="password" autocomplete="off" placeholder="Your EXFE password" />'
                  + '<i class="help-inline icon16-pass-hide pointer" id="password-eye"></i>'
                + '</div>'
              + '</div>'
            + '</div>'
          + '</div>'

          + '<div class="d1 hide">'
            + '<div class="detials">You’re about to change your account details. For security concern, please re-authenticate your identity and set your <span class="x-sign">EXFE</span> password first.</div>'
            + '<div class="context-identity">'
              + '<div class="pull-right avatar">'
                + '<img src="" alt="" width="40" height="40" />'
                + '<i class="provider"></i>'
              + '</div>'
              + '<div class="clearfix dropdown-toggle" data-toggle="dropdown">'
                + '<div class="pull-left box identity"></div>'
                + '<ul class="dropdown-menu"></ul>'
                + '<div class="pull-left caret-outer hide"><b class="caret"></b></div>'
              + '</div>'
            + '</div>'
            + '<div class="why">Why I have to do this?</div>'
            + '<div class="answer">Sorry for the inconvenience. Sometimes, we have to compromise on experience for your account security. Re-authentication is to avoid modification by others who can possibly access your computer.</div>'
          + '</div>',

        footer: ''
          + '<button class="pull-left xbtn xbtn-white xbtn-forgotpwd d0 hide" data-dialog-from="authentication" data-widget="dialog" data-dialog-type="forgotpassword">Forgot Password...</button>'
          + '<button class="pull-right xbtn xbtn-blue xbtn-auth d1 hide">Authenticate</button>'
          + '<button class="pull-right xbtn xbtn-blue xbtn-done d0 hide">Done</button>'
          + '<a class="pull-right xbtn-discard d0 hide" data-dismiss="dialog">Cancel</a>'

      },

      events: {
        'click .xbtn-done': function (e) {
          var that = this
            , user = Store.get('user')
            , default_identity = user.identities[0]
            , external_username = default_identity.external_username
            , provider = default_identity.provider
            , password = Util.trim(that.$('#password').val());

          // 重新鉴权
          Api.request('signin',
            {
              type: 'POST',
              data: {
                external_username: external_username,
                provider: provider,
                password: password,
                name: '',
                auto_signin: true
              }
            },
            function (data) {
              Store.set('authorization', data);
              that.destory();
            }
          );
        },

        'click .xbtn-auth': function (e) {
          var that = this;
          var $e = $(e.currentTarget);
          if ($e.hasClass('xbtn-success')) {
            that.$('.verify-after').addClass('hide');
            $e.removeClass('xbtn-success').text('Verify');
            that.hide();
            return false;
          }
          var provider = that._identity.provider;
          var external_id = that._identity.external_id;
          Api.request('verifyIdentity'
            , {
              type: 'POST',
              data: {
                provider: provider,
                external_username: external_id
              }
            }
            , function (data) {
              }
          );
        },

        'click .caret-outer': function (e) {
          this.$('.dropdown-toggle').addClass('open');
          e.stopPropagation();
        },

        'hover .dropdown-menu > li': function (e) {
          var t = e.type,
              $e = $(e.currentTarget);

          $e[(t === 'mouseenter' ? 'add' : 'remove') + 'Class']('active');
        },

        'click .dropdown-menu > li': function (e) {
          var ids = this.$('.dropdown-menu').data('identities')
            , index = $(e.currentTarget).data('index');

          this.updateIdentity(ids[index]);
          // TODO: 优化
          //this.$('.dropdown-toggle').removeClass('open');
        }
      },

      onHideAfter: function () {
        this.destory();
      },

      onShowBefore: function (e) {
        var that = this
          , user = Store.get('user')
          , hasPassword = user.password;
        that.$('.d' + (hasPassword ? 0 : 1)).removeClass('hide');
        if (hasPassword) {
          that.$('.modal-body .d0')
            .find('.avatar img').attr('src', user.avatar_filename)
            .parent()
            .next().text(user.name);
          that.$('.xbtn-forgotpwd').data('source', user.identities);
        }
        else {
          var ids = user.identities
            , l
            , first;
          if (ids && (l = ids.length)) {
            first = ids[0];
            first.eun = Util.printExtUserName(first);
            if (1 < l) {
              that.$('.context-identity').addClass('switcher');
              var s = '';
              for (var i = 0; i < l; i++) {
                s += '<li data-index="' + i + '"><i class="pull-right icon16-identity-' + ids[i].provider + '"></i>';
                ids[i].eun = Util.printExtUserName(ids[i]);
                s += '<span>' + ids[i].eun + '</span>'
                s += '</li>';
              }
              that.$('.dropdown-menu').html(s).data('identities', ids);
            }

            that.updateIdentity(first);
          }
        }
      }

    }

  }

  // Identification 弹出窗口类
  var Identification = Dialog.extend({

    // 用户有效身份标志位，默认 false
    availability: false,

    init: function () {
      var that = this;

      // TODO: 后期优化掉
      Bus.off('widget-dialog-identification-auto');
      Bus.on('widget-dialog-identification-auto', function (data) {
        var $identityLabel = that.$('[for="identity"]'),
            $identityLabelSpan = $identityLabel.find('span');
        var $passwordLabel = that.$('[for="password"]'),
            $passwordLabelSpan = $passwordLabel.find('span');

        that.availability = false;
        that.identityFlag = null;

        var t;

        if (that.switchTabType === 'd24') {
          t = 'd01';
        }

        that.$('.help-subject')
          .removeClass('icon14-question')
          .addClass('icon14-clear')
          .parent()
          .find('.xalert-error')
          .addClass('hide');

        if (data) {
          // test
          $identityLabel.removeClass('label-error');
          $identityLabelSpan.text('');

          if (data.identity) {
            that._identity = data.identity;
            that.$('.user-identity').removeClass('hide')
              .find('img').attr('src', data.identity.avatar_filename)
              .next().attr('class', 'provider icon16-identity-' + data.identity.provider);
          } else {
            that._identity = null;
            that.$('.user-identity').addClass('hide');
          }

          that.identityFlag = data.registration_flag;
          // SIGN_IN
          if (data.registration_flag === 'SIGN_IN') {
            t = 'd01';
            that.$('.xbtn-forgotpwd').removeClass('hide');
            $passwordLabel.removeClass('label-error');
            $passwordLabelSpan.text('');
          }
          // SIGN_UP 新身份
          else if (data.registration_flag === 'SIGN_UP') {
            t = 'd02';
            $passwordLabel.removeClass('label-error');
            $passwordLabelSpan.text('');
          }
          // AUTHENTICATE
          else if (data.registration_flag === 'AUTHENTICATE') {
            t = 'd00';
            that.$('.help-subject')
              .removeClass('icon14-question')
              .addClass('icon14-clear');
            that.$('.authenticate').removeClass('hide');
          }
          // VERIFY
          else if (data.registration_flag === 'VERIFY') {
            t = 'd04';
          }
          that.availability = true;
        } else {
          that.$('.help-subject')
            .removeClass('icon14-clear')
            .addClass('icon14-question');
          //$identityLabel.addClass('label-error')
          //$identityLabelSpan.text('Invalid identity.');
        }

        t && (that.switchTabType !== t) && that.switchTab(t);

        that.$('.x-signin')[(that.availability ? 'remove' : 'add') + 'Class']('disabled');
        that.$('.xbtn-forgotpwd').data('source', data ? [data.identity] : data);
      });

      // TODO: 后期优化掉
      Bus.off('widget-dialog-identification-nothing');
      Bus.on('widget-dialog-identification-nothing', function () {
        that.$('.authenticate').addClass('hide');
        that.$('.user-identity').addClass('hide');
        that.$('[for="identity"]').removeClass('label-error')
          .find('span').text('');
        that.$('.xbtn-forgotpwd').addClass('hide').data('source', null);
        that.availability = false;
        //if (that.switchTabType !== 'd02') that.switchTab('d01');
        that.$('.x-signin')[(that.availability ? 'remove' : 'add') + 'Class']('disabled');
      });
    },

    resetInputs: function () {
      this.$('input').val('');
      this.$('.label-error').removeClass('label-error').find('span').text('');
      this.$('.icon16-pass-show').toggleClass('icon16-pass-show icon16-pass-hide').prev().prop('type', 'password');
      this.$('#identity').focusend();
    },

    setPasswordPlaceHolder: function (t) {
      // new user
      if (t === 'd02') {
        this.$('#password')
          .attr('placeholder', 'Set your EXFE Password');
      } else if (t === 'd01') {
        this.$('#password')
          .attr('placeholder', 'Your EXFE Password');
      }
    },

    getFormData: function (t) {
      var val = Util.trim(this.$('#identity').val());
      var identity = Util.parseId(val);
      if (t === 'd01' || t === 'd02') {
        identity.password = this.$('#password').val();
      }
      if (t === 'd01') {
        identity.auto_signin = this.$('#auto-signin').prop('checked');
      }
      if (t === 'd02') {
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

      //this.$('.xalert-info').addClass('hide');

      this.switchTabType = t;

      if (this.isShown
          && (this.switchTabType === 'd00' || this.switchTabType === 'd01' || this.switchTabType === 'd02')) {
        var $identity = this.$('#identity');
        $identity.focusend();
      }

      this.setPasswordPlaceHolder(t);
    }

  });

  exports.Identification = Identification;

});
