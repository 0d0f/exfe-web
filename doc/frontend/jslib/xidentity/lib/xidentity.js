define(function (require) {

  var $ = require('jquery');
  var Util = require('util');
  var Bus = require('bus');
  var $BODY = $(document.body);
  var Store = require('store');
  var Typeahead = require('typeahead');

  var IdentityPop = Typeahead.extend({

    focus: function () {
      var v = Util.trim(this.target.val());
      if (v) {
        //this.emit('search', v);
      } else {
        this.emit('nothing', v);
      }
    },

    options: {

      // ajax settings
      url: null,
      useCache: false,
      delay: 200,
      extraParams: {},
      autoClearResults: false,
      dataType: "JSON",
      minLength: 1,

      viewData: {

        item: ''
          + '<li>'
            + '<img class="pull-right" src="{{avatar_filename}}" alt="" width="20" height="20" />'
            + '<span>{{external_id}}</span>'
          + '</li>'
      },

      // 清楚数据缓存
      onClearCache: function () {
        delete this.cache;
      },

      onSearch: function (q) {
        var that = this
          , options = that.options
          , res;

        that.cache || (that.cache = {});

        if ((res = Util.parseId(q)).provider) {
          var identity = {
            provider: res.provider,
            external_id: res.external_identity
          };

          clearTimeout(that.timer);

          that.timer = setTimeout(function () {
            clearTimeout(that.timer);
            search(q);
          }, options.delay);

          // falg: SIGN_IN SIGIN_UP VERIFY RESET_PASSWORD
          function ajax(e) {
            that.ajaxDefer && that.ajaxDefer.readyState < 4 && that.ajaxDefer.abort();
            if (options.useCache && that.cache[e]) that.emit('autocomplete:finish', that.cache[e]);
            else {
              that.emit('autocomplete:beforesend');
              that.ajaxDefer = $.ajax({
                url: Util.apiUrl + '/users/getRegistrationFlag',
                type: 'GET',
                dataType: 'JSON',
                xhrFields: {withCredentials: true},
                data: identity
              })
                .done(function (data) {
                  if (data.meta.code === 200) {
                    if (e === that.target.val()) {
                      options.useCache && (that.cache[e] = data.response);
                      if (data.response.identity) {
                        Store.set('user', {'identities': [data.response.identity], 'flag': data.response.registration_flag});
                      }
                      that.emit('autocomplete:finish', data.response);
                    }
                  }
                });
            }
          }

          function search(a) {
            if (a.length >= that.options.minLength) {
              if (that.searchValue !== a) {
                ajax(a);
                that.searchValue = a;
              }
            } else {
              that.emit('autocomplete:clear');
            }
          }

        } else {
          that.emit('autocomplete:finish', null);
        }
      }

    }

  });

  $(function () {

    $BODY.on('focus.typeahead.data-api', '[data-typeahead-type="identity"]', function (e) {
      var $this = $(this);

      if ($this.data('typeahead')) return;
      e.preventDefault();
      $this.data('typeahead', new IdentityPop({

        options: {
          useCache: true,
          target: $this,
          // 当输入框没有值时，触发
          onNothing: function () {
            this.target.parent().removeClass('identity-avatar');
            Bus.emit('widget-dialog-identification-nothing');
          },

          'onAutocomplete:finish': function (data) {
            var identity;
            if (data && (identity = data.identity)) {
              if (identity['avatar_filename'] === 'default.png') {
                identity['avatar_filename'] = '/img/default_portraituserface_20.png';
              }
              this.target
                .prev()
                .attr('src', identity['avatar_filename'])
                .parent()
                .addClass('identity-avatar');
            } else {
              this.target.parent().removeClass('identity-avatar');
            }
            Bus.emit('widget-dialog-identification-auto', data);
          }
        }
      }));

    });

  });

});
