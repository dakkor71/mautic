<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

return array(
    'name'        => 'GoToWebcast',
    'description' => 'plugin.gotowebcast.description',
    'author'      => 'Webmecanik',
    'version'     => '1.0.0',

	'routes' => array(
		'main' => array(
			'plugin.gotowebcast.route.main.ajax.checkapi' => array(
				'path'       => '/gotowebcast/ajax/check-api',
				'controller' => 'GoToWebcastBundle:Ajax:checkAPI'
			)
		)
	),

	'services' => array(

		'events' => array(
			'plugin.gotowebcast.config.subscriber' => array(
				'class' => 'MauticPlugin\GoToWebcastBundle\EventListener\ConfigSubscriber'
			),
			'plugin.gotowebcast.event.formsubscriber' => array(
				'class' => 'MauticPlugin\GoToWebcastBundle\EventListener\FormSubscriber',
				'arguments' => array('doctrine.orm.entity_manager', 'plugin.gotowebcast.service.gtwapi', 'kernel', 'translator', '%mautic.gotowebcast_enable_plugin%')
			),
			'plugin.gotowebcast.lead.subscriber' => array(
				'class' => 'MauticPlugin\GoToWebcastBundle\EventListener\LeadSubscriber',
				'arguments' => array('%mautic.gotowebcast_enable_plugin%')
			),
			'plugin.gotowebcast.campaignevent.subscriber' => array(
				'class' => 'MauticPlugin\GoToWebcastBundle\EventListener\CampaignSubscriber',
				'arguments' => array('%mautic.gotowebcast_enable_plugin%')
			)
		),

		'forms' => array(
			'plugin.gotowebcast.formtype.config' => array(
				'class' => 'MauticPlugin\GoToWebcastBundle\Form\Type\ConfigType',
				'alias' => 'gotowebcast_formtype_config'
			),
			'plugin.gotowebcast.formtype.webcastlist' => array(
				'class' => 'MauticPlugin\GoToWebcastBundle\Form\Type\WebcastlistType',
				'alias' => 'gotowebcast_formtype_webcastlist'
			),
			'plugin.gotowebcast.formtype.pushleadtoapi' => array(
				'class' => 'MauticPlugin\GoToWebcastBundle\Form\Type\PushLeadToApiType',
				'alias' => 'gotowebcast_formtype_formaction'
			),
			'plugin.gotowebcast.formtype.campaignevent' => array(
				'class' => 'MauticPlugin\GoToWebcastBundle\Form\Type\CampaignEventGotowebcastType',
				'alias' => 'gotowebcast_formtype_campaignevent',
				'arguments' => array('translator', 'mautic.factory')
			)
		),
	    'models' =>  [
	        'mautic.gotowebcast.model.webcast' => [
	            'class' => 'MauticPlugin\GoToWebcastBundle\Model\WebcastModel',
	            'arguments' => ['mautic.factory']
	        ]
	    ],
		'others' => array(
			'mautic.helper.gotowebcast.formaction' => array(
				'class' => 'MauticPlugin\GoToWebcastBundle\Helper\FormActionHelper',
				'arguments' => array('plugin.gotowebcast.service.gtwapi', 'plugin.gotowebcast.service.gtwsync')
			),
			'plugin.gotowebcast.service.gtwapi' => array(
				'class' => 'MauticPlugin\GoToWebcastBundle\Services\GtwcastApiService',
				'alias' => 'gotowebcast_service_gtwapi',
				'arguments' => array('%mautic.gotowebcast_api_username%', '%mautic.gotowebcast_api_password%', 'translator')
			),
			'plugin.gotowebcast.service.gtwsync' => array(
				'class' => 'MauticPlugin\GoToWebcastBundle\Services\GtwcastSyncService',
				'alias' => 'gotowebcast_service_gtwsync',
				'arguments' => array('plugin.gotowebcast.service.gtwapi', 'mautic.factory')
			)
		)
	),

	'parameters' => array(
		'gotowebcast_api_username' => '',
		'gotowebcast_api_password' => '',
		'gotowebcast_enable_plugin' => false
	)
);
