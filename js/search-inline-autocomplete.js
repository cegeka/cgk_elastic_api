(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.searchInlineBehavior = {
    attach: function (context) {
      var searchForm = $('.cgk-search-form');
      $('.search-autocomplete-inline').off().on('click', '.autocomplete-keyword', function () {
        searchForm.find('input[name=keyword]').val($(this).attr('data-keyword'));
        $('.search-autocomplete-inline').removeClass('is-visible');
        searchForm.submit();
      });

      var delay = (function () {
        var timer = 0;
        return function (callback, ms) {
          clearTimeout(timer);
          timer = setTimeout(callback, ms);
        };
      })();

      searchForm.find('input[name=keyword]').off().on('keyup', function () {
        var q = $(this).val().trim();

        if (q.length > 2) {
          delay(function () {

            var prefix = drupalSettings.langcode;
            var url = '/' + prefix + '/zoek/autocomplete';
            $.ajax({
              url: url,
              data: {
                q: q,
                t: 1
              },
              success: function (data) {
                $('.search-autocomplete-inline').html(data).addClass('is-visible');
              }
            });
          }, 300);
        }
        else {
          $('.search-autocomplete-inline').html('');
        }
      });

      searchForm.find('input[name=keyword]').off('blur').on('blur', function () {
        setTimeout(function () {
          $('.search-autocomplete-inline').removeClass('is-visible');
        }, 200);

      });
    }
  };

})(jQuery, Drupal, drupalSettings);
