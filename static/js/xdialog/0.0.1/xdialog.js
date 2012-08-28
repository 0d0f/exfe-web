define('xdialog', function (require, exports, module) {
  var $ = require('jquery');
  var R = require('rex');
  var Bus = require('bus');
  var Api = require('api');
  var Util = require('util');
  var Store = require('store');
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
            .find('.avatar')
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
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();

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
              dataType: 'JSON',
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
                  //Bus.emit('xapp:usertoken', data.token, data.user_id, 2);
                  Bus.emit('app:user:signin', data.token, data.user_id);
                  Bus.emit('xapp:usersignin');
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
                      + '<img class="avatar" src="" alt="" width="40" height="40" />'
                      + '<i class="provider"></i>'
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
          + '<p><span class="x-sign">EXFE</span> [ˈɛksfi] is still in <span class="pilot">pilot</span> stage (with <span class="sandbox">SANDBOX</span> tag). We’re building up blocks, consequently some bugs or unfinished pages may happen. Our apologies for any trouble you may encounter. Any feedback, please email <span class="feedback">feedback@exfe.com</span>. Much appreciated.</p>'
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

        if (identity.provider === 'email') {
          this.$('.provider-email').removeClass('hide');
          title.text('Hi, ' + identity.name + '.');
        } else {
          this.$('.provider-other').removeClass('hide');
          title.text('Hi, ' + Util.printExtUserName(identity) + '.');
        }
      },

      onHideAfter: function () {
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
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
      var $identity = this.$('.user-identity');
      this.$('.tab').addClass('hide');
      if (provider === 'email') {
        this.$('.tab1').removeClass('hide');
        this.$('.xbtn-send').data('identity', identity);
      }
      else {
        this.$('.tab2').removeClass('hide');
        this.$('.authenticate').data('identity', identity);
      }
      $identity.find('img.avatar').attr('src', identity.avatar_filename);
      $identity.find('i').addClass('icon16-identity-' + identity.provider);
      $identity.next().find('.identity').text(identity.eun);
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

        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
      },

      events: {
        'click .authenticate': function (e) {
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

        'click .xbtn-send': function (e) {
          var that = this;
          var $e = $(e.currentTarget);
          if ($e.hasClass('disabled')) {
            return;
          }
          if ($e.hasClass('success')) {
            this.hide(e);
            var $t = this.element;
            //this.offSrcNode();
            //this.destory();
            //$t.remove();
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
                  $e.text('Done').addClass('success');
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
          if (l >1 ) {
            that.$('.caret-outer').removeClass('hide');
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
          + '<div class="pull-right user-identity">'
            + '<img class="avatar" src="" alt="" width="40" height="40" />'
            + '<i class="provider"></i>'
          + '</div>'
          + '<div class="clearfix dropdown-toggle" data-toggle="dropdown">'
            + '<div class="pull-left identity disabled"></div>'
            + '<ul class="dropdown-menu"></ul>'
            + '<div class="pull-left caret-outer hide"><b class="caret"></b></div>'
          + '</div>'
          + '<div class="send-before tab tab1 hide">Confirm sending reset token to your mailbox?</div>'
          + '<div class="send-after tab hide">Verification sent, it should arrive in minutes. Please check your mailbox and follow the instruction.</div>'
          + '<div class="xalert-error tab hide">'
            + '<p>Requested too much, hold on awhile.</p>'
            + '<p>Receive no verification email? It might be mistakenly filtered as spam, please check and un-spam. Alternatively, use ‘Manual Verification’.</p>'
          + '</div>'

          + '<div class="authenticate-before tab tab2 hide">You will be directed to Twitter website to authenticate identity above, you can reset password then.</div>',

        footer: ''
          + '<button class="pull-right xbtn-white xbtn-send tab tab1 hide">Send</button>'
          + '<button class="pull-right xbtn-blue authenticate tab tab2 hide">Authenticate</button>'
          + '<a class="pull-right xbtn-cancel">Cancel</a>'

      }
    }

  }

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
          if (0 === ids.length) ids.push(user.default_identity);
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
              if (data.meta.code === 403) {
                var errorType = data.meta.errorType;
                if (errorType === 'invalid_current_password') {
                  alert('Invalid current password.');
                }
              }
            }
          );

        }
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
              + '<legend>Enter your current <span class="x-sign">EXFE</span> password, and set new one. All your identities share the same password for sign-in and account management.</legend>'

              + '<div class="identity">'
                + '<img class="avatar" src="" width="40" height="40" />'
                + '<span></span>'
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
          var authorization = Store.get('authorization');
          var user_id = authorization.user_id;
          var token = authorization.token;
          var that = this;

          var identity = Util.parseId(new_identity);

          if (identity.provider) {
            Api.request('addIdentity', {
                type: 'POST',
                params: { token: token },
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
            + '<form class="modal-form">'
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
                      + '<i class="help-inline icon16-pass-hide pointer" id="password-eye"></i>'
                      + '<div class="xalert-error hide"></div>'
                    + '</div>'
                  + '</div>'

              + '</fieldset>'
            + '</form>',

        footer: ''
          + '<button class="xbtn-white xbtn-forgotpwd" data-dialog-from="identification" data-widget="dialog" data-dialog-type="forgotpassword">Forgot Password...</button>'
          + '<button class="pull-right xbtn-blue xbtn-success disabled">Add</button>'
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
            that.$('.xbtn-forgotpwd').removeClass('hide').data('source', [data.identity]);
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
          + '<div class="identity disabled"></div>'
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
        var identity_id = $e.parents('li').data('identity-id');
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
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
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
            , authorization = Store.get('authorization')
            , user_id = authorization.user_id
            , token = authorization.token;

          Api.request('setPassword'
            , {
              type: 'POST',
              params: { token: token },
              resources: { user_id: user_id },
              data: { new_password: stpwd },
              beforeSend: function (xhr) {
                $e.addClass('disabled loading');
              },
              complete: function (xhr) {
                $e.removeClass('disabled loading');
              }
            }
            , function (data) {

              // 设置密码, 刷新本地缓存，及 `user-menu`
              // TODO: 先简单处理，后面再细想
              var user = Store.get('user');
              user.password = true;
              Store.set('user', user);
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

              + '<div class="identity">'
                + '<img class="avatar" src="" width="40" height="40" />'
                + '<span></span>'
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

      onShowBefore: function () {
        var user = Store.get('user');
        this.$('.identity > img').attr('src', user.avatar_filename);
        this.$('.identity > span').text(user.name);
      }

    }

  };


  // Set Up Account
  // --------------------------------------------------------------------------
  dialogs.setup_email = {

    options: {

      onHideAfter: function () {
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
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
              + '<legend>For easier further use, please set up account of your identity underneath.<span class="hide"> Otherwise, <span class="underline">sign in</span> your existing account to merge with this identity.</span></legend>'

                + '<div class="clearfix control-group">'
                  + '<div class="pull-right user-identity">'
                    + '<img class="avatar" src="" alt="" width="40" height="40" />'
                    + '<i class="provider"></i>'
                  + '</div>'
                  + '<div class="identity disabled"></div>'
                + '</div>'

                + '<div class="control-group">'
                  + '<label class="control-label" for="name">Display name: <span></span></label>'
                  + '<div class="controls">'
                    + '<input type="text" class="input-large" id="name" autocomplete="off" placeholder="Desired recognizable name" />'
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
          + '<button class="xbtn-white xbtn-siea hide" data-widget="dialog" data-dialog-type="identification" data-dialog-tab="d00">Sign In to Merge…</button>'
          + '<button class="pull-right xbtn-blue xbtn-success">Done</button>'
          + '<a class="pull-right xbtn-discard" data-dismiss="dialog">Discard</a>'
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
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
      },

      events: {
        'click .authorize': function (e) {
            this._oauth_ = $.ajax({
              url: '/OAuth/twitterAuthenticate',
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
                  + '<div class="identity disabled"></div>'
                + '</div>'

                + '<div class="clearfix">'
                  + '<button class="pull-right xbtn-blue authorize">Authorize</button>'
                  + '<a class="pull-right underline pointer cancel" data-dismiss="dialog">Cancel</a>'
                + '</div>'

                + '<div class="spliterline"></div>'

                + '<div>'
                  + 'Got existing <span class="x-sign">EXFE</span> account already?<br /> Just <span class="underline">sign in to add</span> this identity into your account.'
                + '</div>'

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
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
      },

      events: {

        'click .xbtn-go': function (e) {
          window.location.href = '/';
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
            + '<div class="identity">'
              + '<img class="avatar" src="" width="40" height="40" />'
              + '<span></span>'
            + '</div>'
            + '<div class="clearfix">'
              + '<button class="pull-right xbtn-white xbtn-go">Go</button>'
              + '<a class="pull-right xbtn-cancel" data-dismiss="dialog">Cancel</a>'
            + '</div>'
            + '<div class="spliterline"></div>'
          + '</div>'
          + '<div class="browsing-tips"><span class="hide">Otherwise, you’re</span><span class="hide">You’re currently</span> browsing this page as identity underneath, please choose an option to continue.</div>'
          + '<div class="pull-right user-identity browsing-identity">'
            + '<img class="avatar" src="" alt="" width="40" height="40">'
            + '<i class="provider"></i>'
          + '</div>'
          + '<div class="identity disabled bidentity"></div>',

        footer: ''
          //+ '<button class="pull-right xbtn-blue xbtn-merge hide">Merge with account above</button>'
          //+ '<button class="xbtn-white xbtn-sias hide" data-widget="dialog" data-dialog-type="identification" data-dialog-tab="d00">Sign in and switch</button>'
          //+ '<button class="xbtn-white xbtn-sui hide" data-widget="dialog" data-dialog-type="setup">Set up identity</button>'
          // note: 暂时没有`merge` 功能，按钮放右边
          + '<button class="pull-right xbtn-white xbtn-sias hide" data-widget="dialog" data-dialog-type="identification" data-dialog-tab="d00">Sign in and switch</button>'
          + '<button class="pull-right xbtn-white xbtn-sui hide" data-widget="dialog" data-dialog-type="setup_email">Set up identity</button>'

      },

      onShowBefore: function (e) {
        var settings = $(e.currentTarget).data('settings');
        if (!settings) return;
        var user = settings.normal
          , browsing_user = settings.browsing
          , setup = settings.setup
          , action = settings.action;

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
            .next().text(user.name || user.nickname);

        }

        this.$('browsing-tips').find('span').eq(this._user ? 0 : 1).removeClass('hide')

        var beun = Util.printExtUserName(browsing_user.default_identity);

        this.$('.browsing-identity')
          .next().text(beun)
        .end()
          .find('img')
          .attr('src', browsing_user.default_identity.avatar_filename)
          .next().addClass('icon16-identity-' + browsing_user.default_identity.provider)

        //if (!this._setup) { // test
        if (this._setup) {
          this.$('.xbtn-sui')
            .removeClass('hide')
            .attr('data-dialog-type', 'setup_' + browsing_user.default_identity.provider)
            .data('source', {
              identity: browsing_user.default_identity,
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
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
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
                  + '<div class="identity disabled"></div>'
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
        var beun = Util.printExtUserName(settings.default_identity);
        this.$('legend span').eq(0).text(isBrowsing ? 'identity' : 'user');
        this.$('.xbtn-blue').data('source', beun);
        if (isBrowsing) {
          var bi = this.$('.browsing-identity').removeClass('hide');
          bi.find('.identity').text(beun);
          bi.find('.avatar').attr('src', settings.default_identity.avatar_filename);
          bi.find('.provider').addClass('icon16-identity-' + settings.default_identity.provider)
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
        var $e = this.element;
        this.offSrcNode();
        this.destory();
        $e.remove();
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
              .find('.avatar').attr('src', data.identity.avatar_filename)
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
