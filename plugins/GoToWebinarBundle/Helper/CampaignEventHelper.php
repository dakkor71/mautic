<?php
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebinarBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class CampaignEventHelper
 */
class CampaignEventHelper
{

	/**
	 * Appelé lorsqu'un événement lié à GTW est inscrit dans la timeline d'un lead (voir la méthode 'triggerEvent' du modèle Campaign)
	 * Retourne TRUE si le lead correspond au critère de la décision de la campagne
	 * Ce qui déclenche immédiatement l'action connectée au point vert.
	 * Dans le cas contraire, ne fait rien. C'est le cron 'campaigns:trigger' qui suivra le chemin rouge si besoin
	 *
	 * @param 	$config		array 	Paramètres définis dans la boîte de dialogue de la décision
	 * @param 	$lead 		Lead	Entité : lead courant
	 * @param	$factory 	MauticFactory
	 *
	 * @return bool
	 */
	public static function onWebcastDecisionTriggered(array $config, Lead $lead, MauticFactory $factory)
	{
		$criteria = $config['webinar-criteria'];
		$webinarsList = $config['webinars'];
		$isAny = in_array('ANY', $webinarsList);
		$email = $lead->getEmail();

		$webinarModel = $factory->getModel('plugin.GoToWebcast.Webcast');

		if ( !in_array($criteria, array('participatedInAtLeast', 'notParticipatedInAny'))) {
			$registeredCounter = $webinarModel->countEventsBy($email, 'registered', $isAny ? false : $webinarsList);
		}
		if ( !in_array($criteria, array('registeredInAtLeast', 'notRegisteredInAny'))) {
			$participatedCounter = $webinarModel->countEventsBy($email, 'participated', $isAny ? false : $webinarsList);
		}

		switch($criteria) {
			case 'registeredInAtLeast':
				return ($registeredCounter > 0);
			case 'notRegisteredInAny':
				return ($registeredCounter == 0);
			case 'participatedInAtLeast':
				return ($participatedCounter > 0);
			case 'notParticipatedInAny':
				return ($participatedCounter == 0);
			case 'registeredButNotParticipatedInAtLeast':
				return ($participatedCounter < $registeredCounter);
			default:
				return false;
		}
	}

}
