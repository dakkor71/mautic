<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebcastBundle\Services;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class GtwcastSyncService
 *
 * Synchronise les leads avec les listes d'inscrits et de participants de GoToWebcast
 *
 */
class GtwcastSyncService
{
	private $gtwcastApiService;
	private $webcastModel;

	/**
	 * Injection de dépendances
	 */
	public function __construct(GtwcastApiService $gtwcastApiService, MauticFactory $factory)
	{
		$this->gtwcastApiService = $gtwcastApiService;
		$this->webcastModel = $factory->getModel('plugin.GoToWebcast.Webcast');
	}

	/**
	 * Synchronise une liste de webcasts, définis par leurs clés
	 *
	 * @param array( string ) 	Optionnel : si absent, synchronise tous les webcasts
	 * @return void
	 */
	public function sync($webcastsKeys = false)
	{
		if ($webcastsKeys === false) {
			$webcastlist = $this->gtwcastApiService->getWebcastList($onlyFutures=false);
			$webcastsKeys = array_keys($webcastlist);
		}
		else {
			$webcastlist = array();
		}

		foreach($webcastsKeys as $webcastKey) {

			// Lecture des listes d'emails via l'API
			list($registrants, $attendees, $webcastSlug) = $this->gtwcastApiService->getRegistrantsAndAttendees($webcastKey);

			// Lecture des listes d'emails connues en DB
			$knownRegistrants = $this->webcastModel->getEmailsByWebcast($webcastSlug, 'registered');
			$knownAttendees = $this->webcastModel->getEmailsByWebcast($webcastSlug, 'participated');

			// Mise à jour des inscriptions
			$this->webcastModel->batchAddAndRemove(
				$webcastSlug,
				'registered',
				array_diff($registrants, $knownRegistrants),
				array_diff($knownRegistrants, $registrants)
			);

			// Mise à jour des participations
			$this->webcastModel->batchAddAndRemove(
				$webcastSlug,
				'participated',
				array_diff($attendees, $knownAttendees),
				array_diff($knownAttendees, $attendees)
			);
		}
	}

}

?>
