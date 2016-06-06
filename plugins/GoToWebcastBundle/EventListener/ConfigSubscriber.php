<?php 
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebcastBundle\EventListener;

use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;

/**
 * Class ConfigSubscriber
 */
class ConfigSubscriber extends CommonSubscriber
{
	static public function getSubscribedEvents () 
	{
		return array(
			ConfigEvents::CONFIG_ON_GENERATE => array('onConfigGenerate', 0)
		);
	}
	
	public function onConfigGenerate (ConfigBuilderEvent $event) 
	{
		$event->addForm(
            array(
				'bundle' => 'GoToWebcastBundle',
                'formAlias'  => 'gotowebcast_formtype_config',
                'formTheme'  => 'GoToWebcastBundle:Config',
                'parameters' => $event->getParametersFromConfig('GoToWebcastBundle')
            )
        );
	}
	
}
