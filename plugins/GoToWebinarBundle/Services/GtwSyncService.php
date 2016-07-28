<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
namespace MauticPlugin\GoToWebinarBundle\Services;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class GtwSyncService
 *
 * Synchronise les leads avec les listes d'inscrits et de participants de GoToWebinar
 *
 */
class GtwSyncService
{
	private $gtwApiService;
	private $webinarModel;
	
	/**
	 * Injection de dépendances
	 */
	public function __construct(GtwApiService $gtwApiService, MauticFactory $factory) 
	{
		$this->gtwApiService = $gtwApiService;
		$this->webinarModel = $factory->getModel('plugin.GoToWebinar.Webinar');
	}
	
	/**
	 * Synchronise une liste de webinaires, définis par leurs clés 
	 *
	 * @param array( string ) 	Optionnel : si absent, synchronise tous les webinaires
	 * @return void
	 */
	public function sync($webinarsKeys = false)
	{
		if ($webinarsKeys === false) {
			$webinarsKeys = array_keys( $this->gtwApiService->getWebinarList($onlyFutures=false, $onlySubjects=true) );
		}
		
		foreach($webinarsKeys as $webinarKey) {
			
			$webinarSlug = $this->gtwApiService->getWebinarSlug($webinarKey);
			
			// Mise à jour des inscriptions
			$registrants = $this->gtwApiService->getRegistrants($webinarKey, $onlyEmails=true);
			$knownRegistrants = $this->webinarModel->getEmailsByWebinar($webinarSlug, 'registered');
			
			$this->webinarModel->batchAddAndRemove(
				$webinarSlug, 
				'registered', 
				array_diff($registrants, $knownRegistrants),
				array_diff($knownRegistrants, $registrants)
			);
			
			// Mise à jour des participations
			$attendees = $this->gtwApiService->getAttendees($webinarKey, $onlyEmails=true);
			$knownAttendees = $this->webinarModel->getEmailsByWebinar($webinarSlug, 'participated');
			
			$this->webinarModel->batchAddAndRemove(
				$webinarSlug, 
				'participated',
				array_diff($attendees, $knownAttendees),
				array_diff($knownAttendees, $attendees)
			);
		}
	}
	
}

?>