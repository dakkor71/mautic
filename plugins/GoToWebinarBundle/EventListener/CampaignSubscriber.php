<?php
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebinarBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class CampaignSubscriber
 */
class CampaignSubscriber extends CommonSubscriber
{

	protected $isPluginEnabled;

	/**
	 * Injection de dépendances
	 */
	public function __construct(MauticFactory $factory, $isPluginEnabled)
	{
	    parent::__construct($factory);
		$this->isPluginEnabled = $isPluginEnabled;
	}

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => array('onCampaignBuild', 0)
        );
    }

    /**
     * Ajout d'une décision dans le campaign-builder
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
		if ( !$this->isPluginEnabled) {
			return;
		}

        $event->addLeadDecision(
			'gotowebinar.decision',
			array(
				'label' => 'plugin.gotowebinar.campaign.decision.label',
				'formType'    => 'gotowebinar_formtype_campaignevent',
				'callback'    => 'MauticPlugin\GoToWebinarBundle\Helper\CampaignEventHelper::onWebinarDecisionTriggered'
			)
		);
    }

}
