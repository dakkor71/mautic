/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
;(function($) {
	"use strict";
	
	window.gtwConfig = {
		
		init: function () {
			
			gtwConfig.$wrapper = $("#gotowebinar_formtype_config");
			
			// Clic sur le bouton "oAuth connect"
			gtwConfig.$wrapper.find('.btn.oauth').click(function () {
				var datas = {
						consumerKey: $.trim($('#config_gotowebinar_formtype_config_gotowebinar_consumer_key').val())
					};

				// Appel serveur pour obtenir l'URL de connexion
				gtwHelpers.ajax('/s/gotowebinar/ajax/get-oauth-url', datas, this, function (response) {
					if (response.success) {
						window.open(response.url);
					}
				});
			});
			
			// Clic sur le bouton "tester l'API"
			gtwConfig.$wrapper.find('.btn.check-api').click(function () {
				var $btn = $(this),
					$messages = $btn.closest('.row').find('.message');
				
				$messages.addClass('hide');
				gtwHelpers.ajax('/s/gotowebinar/ajax/check-api', {}, this, function (response) {
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
	
	$(document).ready(gtwConfig.init);
	
})(mQuery);
