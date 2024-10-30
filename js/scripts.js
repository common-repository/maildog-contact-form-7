var jQuery = window.$ || window.jQuery
jQuery(document).on('ready', function () {
  (function () {
    var ev = new jQuery.Event('style')
    var orig = jQuery.fn.css
    jQuery.fn.css = function () {
      jQuery(this).trigger(ev)
      return orig.apply(this, arguments)
    }
  })()

  setTimeout(function () {
    jQuery('img.ajax-loader').bind('style', function (e) {
      var style = jQuery(this).attr('style')
      // console.log("style: " + style);
      if (style) {
        if (style.indexOf('hidden') > 1) {
        // enable button
        // console.log("Disable  button");
          jQuery('input.wpcf7-submit').attr('disabled', 'disabled')
        } else {
        // console.log("Enable  button");
          jQuery('input.wpcf7-submit').prop('disabled', false)
        }
      }
    })
  }, 2000)
})
