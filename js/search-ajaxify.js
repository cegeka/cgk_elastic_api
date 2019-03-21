(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.cgkSearch = Drupal.cgkSearch || {};
  Drupal.cgkSearch.dataLayer = window.dataLayer || [];
  const url = drupalSettings.cgk_elastic_api.ajaxify.filter_url;

  Drupal.behaviors.ajaxifySiteSearch = {
    attach: function (context, settings) {
      // Bind on facets.
      let facetWrap = $('.cgk-ajax .facets');
      if (facetWrap.length) {
        // Handle inputs, textfields, selects, with the data-facet attribute.
        facetWrap.find('[data-facet]').off().on('ifToggled change', function (e) {
          let without;

          if ($(e.currentTarget).attr('data-facet-hierarchy')) {
            let clickedElement = e.target;
            if (!clickedElement.checked) {
              $(clickedElement).siblings('.facet-child-facets-wrapper').find('input:checked').each(function(idx, child) {
                $(child).attr('checked', false);
              });
            } else {
              without = getWithoutForSingleValueFacet(e.target);
            }
          } else {
            // If a facet only supports one selected value,
            // create a without object with the already selected values.
            if ($(this).attr('data-facet-single')) {
              without = getWithoutForSingleValueFacet(e.target);
            }
          }

          filter(without);
        });
      }

      let searchForm = $('[data-ajax-search-form]');
      searchForm.off().on('submit', function (e) {
        e.preventDefault();

        let without = drupalSettings.cgk_elastic_api.retainFilter ? undefined : '*';
        filter(without);
      });

      // TODO add support for infinite pager
      let pager = $('.cgk-results-wrapper').find('nav.pager');
      pager.find('a').off().on('click', function (e) {
        e.preventDefault();
        filter({}, $(this).attr('data-page'));
      });

      $('.did-you-mean').find('a').off().on('click', function(e) {
        e.preventDefault();
        searchForm.find('input').val($(this).text());
        filter();
      });

      /**
       * Block ui, collect facets, apply filtering.
       *
       * @param {string|object} without
       *   Optionally filter out a facet value, or all values with '*'.
       * @param {string} page
       *   Optionally page.
       */
      function filter(without, page) {
        $.blockUI({
          message: $('#block-ui-spinner'),
          css: {
            border: 'none',
            background: 'none',
            opacity: 1,
            color: '#fff'
          },
          overlayCSS: {
            backgroundColor: '#fff'
          }
        });

        let data = {
          keyword: $('[data-ajax-search-form]').find('input').val()
        };

        if (typeof page !== 'undefined') {
          data['page'] = page;
        }

        $.each(settings.cgk_elastic_api.ajaxify.facets, function (idx, facetName) {
          data[facetName] = getSelectedFacets(facetName, without);
        });

        // Update the url after using facets, so the correct results are shown
        // when using the back button.
        if (typeof history.pushState === 'function') {
          history.pushState({}, '', '?' + $.param(data));
        }

        $.post(url, data, function (data) {
          // Simulate a drupal.ajax response to correctly parse data.
          let ajaxObject = Drupal.ajax({
            url: '',
            base: false,
            element: false,
            progress: false
          });

          ajaxObject.success(data, 'success');
        }).always(function () {
          $.unblockUI();
        });
      }

      /**
       * Get facet values.
       *
       * @param {string} facet
       *   Facet name.
       * @param {string|object} without
       *   Optionally filter out a facet value, or all values with '*'.
       * @param {bool} limitToSingleValue
       *   Boolean indicating if only one value should be returned, or multiple.
       *
       * @return {Array}
       *   Array of facet values.
       */
      function getSelectedFacets(facet, without, limitToSingleValue) {
        limitToSingleValue = typeof limitToSingleValue === "undefined" ? true : limitToSingleValue;

        if (without === '*') {
          return [];
        }

        let ids = [];

        facetWrap.find('[data-facet="' + facet + '"]').each(function (idx, element) {
          const id = $(element).attr('data-drupal-facet-item-value') || $(element).val();

          if ($(element).attr('data-facet-list')) {
            $(element).find('input:checked').each(function (i, e) {
              const id = $(e).attr('data-drupal-facet-item-value');

              conditionallyPushId(facet, ids, id, without);
            });
          } else if ($(element).attr('data-facet-is-composite')) {
            if (Array.isArray(ids)) {
              ids = {};
            }
            let id = $(element).val();
            if (id !== "") {
              const key = $(element).attr('data-facet-composite-key');

              if (!ids.hasOwnProperty(key)) {
                ids[key] = [];
              }

              conditionallyPushId(facet, ids[key], id, without);
            }
          }
          else {
            conditionallyPushId(facet, ids, id, without);
          }
        });

        // If the facet is hierarchical facet,
        // only send a single value to the backend.
        if (limitToSingleValue && facetWrap.find('[data-facet="' + facet + '"]').attr('data-facet-hierarchy')) {
          ids = [ids.pop()];
        }

        return ids;
      }

      /**
       * Conditionally push an id to an array.
       *
       * @param {string} facet
       *   Facet id of the facet getting selected values for.
       * @param {array} ids
       *   Array to push to id into.
       * @param id
       *   Id to push.
       * @param {string|object} without
       *   Filter options.
       */
      function conditionallyPushId(facet, ids, id, without) {
        if (id === "") {
          // Don't push empty facets.
          return;
        }
        // Check if we should filter.
        if (typeof without !== 'undefined' && without.facet === facet) {
          if (!includes(without.values, id)) {
            ids.push(id);
          }
        } else {
          ids.push(id);
        }
      }

      /**
       * Get a withoust for a selected value.
       *
       * @param element
       *   Selected facet value.
       * @returns {{facet: *, value: *}|undefined}
       *   Without object or undefined if there are no active values.
       */
      function getWithoutForSingleValueFacet(element) {
        const facetId = $(element).attr('data-drupal-facet-item-id');
        const facetItemId = $(element).attr('data-drupal-facet-item-value');

        let activeFacetValues = getSelectedFacets(facetId, {}, false).filter(function(item) {
          return item !== facetItemId;
        });

        if (activeFacetValues.length) {
          return {facet: facetId, values: activeFacetValues};
        }
      }

      /**
       * Check if an array contains a value.
       *
       * @param array
       *   The array to check.
       * @param value
       *   The value to check for.
       * @returns {boolean}
       *   True if the array contains the value, false otherwise.
       */
      function includes(array, value) {
        let i = array.length;
        while (i--) {
          if (array[i] === value) {
            return true;
          }
        }
        return false;
      }

    }
  };

})(jQuery, Drupal, drupalSettings);
