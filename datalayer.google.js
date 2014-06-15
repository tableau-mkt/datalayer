/**
 * @file
 * Connect the CMS data to the Google standard data layer.
 */
(function ($) {
  Drupal.behaviors.datalayer = {
    attach: function (context, settings) {
       if (context === document) {

	      var dataLayer = [];	

				if (typeof settings.dataLayer !== 'undefined') {
					dataLayer = settings.dataLayer;
				}

      }
    }
  };
})(jQuery);
