<?php
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebinarBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class CampaignEventHelper
 */
class CampaignEventHelper
{
	
    /**
     * @param MauticFactory $factory
     * @param $eventDetails
     * @param $event
     *
     * @return bool
     */
    public static function onWebinarDecisionTriggered($factory, $eventDetails, $event)
    {
		// Si la décision est OK : renvoyer TRUE pour suivre le chemin vert
		return true;
		
		// Sinon renvoyer FALSE pour suivre le chemin rouge
        return false;
		
		// BUG : pour l'instant cela ne fonctionne pas, cette fonction de callback n'est jamais appelée... :(
    }
}
