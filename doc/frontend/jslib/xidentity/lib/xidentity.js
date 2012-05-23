define(function (require) {

  var $ = require('jquery');
  var Util = require('util');
  var Bus = require('bus');
  var $BODY = $(document.body);
  var Typeahead = require('typeahead');

  var IdentityPop = Typeahead.extend({

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
          , options = that.options;

        that.cache || (that.cache = {});

        if ((res = Util.parseId(q)).provider) {
          var identities = JSON.stringify([{
            provider: res.provider,
            external_id: res.external_identity
          }]);

          clearTimeout(that.timer);

          that.timer = setTimeout(function () {
            clearTimeout(that.timer);
            search(q);
          }, options.delay);

          function ajax(e) {
            that.ajaxDefer && that.ajaxDefer.readyState < 4 && that.ajaxDefer.abort();
            if (options.useCache && that.cache[e]) that.emit('autocomplete:finish', that.cache[e]);
            else {
              that.emit('autocomplete:beforesend');
              that.ajaxDefer = $.ajax({
                url: 'http://api.localexfe.me/v2/identities/get',
                type: 'POST',
                dataType: 'JSON',
                xhrFields: {withCredentials: true},
                data: {
                  identities: identities
                }
              })
                .done(function (data) {
                  if (data.meta.code === 200) {
                    if (e === that.target.val()) {
                      options.useCache && (that.cache[e] = data.response);
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
            Bus.emit('widget-dialog-identification-nothing');
          },

          'onAutocomplete:finish': function (data) {
            var identities = data.identities;
            if (identities.length) {
              if (identities[0]['avatar_filename'] === 'default.png') {
                identities[0]['avatar_filename'] = '/img/default_portraituserface_20.png';
              }
              this.target
                .prev()
                .attr('src', identities[0]['avatar_filename'])
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
