/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

;(function($) {
	"use strict";

	window.gtwctConfig = {

		init: function () {

			gtwctConfig.$wrapper = $("#gotowebcast_formtype_config");

			// Clic sur le bouton "tester l'API"
			gtwctConfig.$wrapper.find('.btn.check-api').click(function () {
				var $btn = $(this),
					$messages = $btn.closest('.row').find('.message');

				$messages.addClass('hide');
				gtwctHelpers.ajax('/s/gotowebcast/ajax/check-api', {}, this, function (response) {
					if (response.success) {
						$messages.filter('.success').removeClass('hide');
					}
					else {
						$messages.filter('.failed').removeClass('hide');
					}
				});
			});

		}
	};

	$(document).ready(gtwctConfig.init);

})(mQuery);
