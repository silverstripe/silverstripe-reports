/**
 * File: ReportAdmin.js
 */

(function ($) {
  $.entwine("ss", function ($) {
    $(".ReportAdmin .cms-edit-form").entwine({
      onsubmit: function (e) {
        let url = $.path.parseUrl(document.location.href).hrefNoSearch;
        let params = this.find(":input[name^=filters]").serializeArray();

        try {
          params = $.grep(params, function (param) {
            // filter out empty
            return param.value;
          });

          // convert params to a query string
          params = $.param(params);

          // append query string to url
          url += "?" + params;

          $(".cms-container").loadPanel(url);
        } catch (err) {
          console.error(err);
        }

        return false;
      },
    });
  });
})(jQuery);
