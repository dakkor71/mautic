/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */


;(function($) {
	"use strict";
	
	window.gtwPage = {
		
		init: function () {
			
			gtwPage.$wrapper = $("#gotowebinar-page-wrapper");

			// Clic sur le bouton de synchro d'un webinar
			gtwPage.$wrapper.find('table .sync').click(function () {
				var datas = {
						webinarKey: $(this).closest('tr').attr('data-webinarKey')
					};
					
				gtwPage.resetSync();
				gtwHelpers.ajax('/gotowebinar/cron/sync', datas, this, gtwPage.showSyncResponse);
			});
			
			// Clic sur le bouton de synchro d'un webinar
			gtwPage.$wrapper.find('.sync-all-wrapper > .btn').click(function () {
				gtwPage.resetSync();
				gtwHelpers.ajax('/gotowebinar/cron/sync', {}, this, gtwPage.showSyncResponse);
			});
		},
		
		resetSync: function () {
			gtwPage.$wrapper.find('.syncreport').html('');
			gtwPage.$wrapper.find('.syncerror').html('').hide();
		},
		
		// Affiche le rapport de synchronisation
		showSyncResponse: function (response) {
			
			if (response.success) {
				gtwPage.$wrapper.find('.syncreport').html(response.syncreport);
			}
			else {
				gtwPage.$wrapper.find('.syncerror').html(response.message).show();
			}
		}
	};

	// Appel au chargement de la page
	Mautic.gtwPageInitOnLoad = gtwPage.init;
	
})(mQuery);
