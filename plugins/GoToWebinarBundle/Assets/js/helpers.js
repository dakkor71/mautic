/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
;(function($) {
	"use strict";

	if (window.gtwHelpers === undefined) {
		window.gtwHelpers = {
			
			isProcessing: function ($spinner) {
				if ($spinner.length > 0) {
					return ! $spinner.hasClass('hide');
				}
				else {
					return false;
				}
			},
			
			spinnerON: function ($spinner) {						
				if ($spinner.length > 0) {
					$spinner.removeClass('hide');
				}
			},
			
			spinnerOFF: function ($spinner) {						
				if ($spinner.length > 0) {
					$spinner.addClass('hide');
				}
			},
			
			ajax: function (route, datas, thisParent, callback) {
				
				var $spinner = thisParent ? $(thisParent).parent().find('.fa-spinner') : '';
				
				// Si une requête est déjà en cours : STOP
				if (gtwHelpers.isProcessing($spinner)) {
					return;
				}
				
				gtwHelpers.spinnerON($spinner);
				$.ajax({
					url: route,
					data: datas,
					type: "POST",
					dataType: "json",
					success: function (response) {
						gtwHelpers.spinnerOFF($spinner);
				
						if (response.flashes) {
							Mautic.setFlashes(response.flashes);
						}
						
						callback(response);
					},
					error: function (response, textStatus, errorThrown) {
						gtwHelpers.spinnerOFF($spinner);
						Mautic.processAjaxError(response, textStatus, errorThrown, true);
					}
				});
			}
		};
	}
	
})(mQuery);
