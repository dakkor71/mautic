<?php
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

return array(
    'name'        => 'GoToWebinar',
    'author'      => 'Webmecanik',
    'version'     => '1.0.0',

	'routes' => array(
		'public' => array(
			'plugin.gotowebinar.route.public.oauthredirect' => array(
				'path' => '/gotowebinar/request-token',
				'controller' => 'GoToWebinarBundle:Public:requestToken'
			)
		),
		'main' => array(
			'plugin.gotowebinar.route.main.ajax.getoauthurl' => array(
				'path'       => '/gotowebinar/ajax/get-oauth-url',
				'controller' => 'GoToWebinarBundle:Ajax:getOauthUrl'
			),
			'plugin.gotowebinar.route.main.ajax.checkapi' => array(
				'path'       => '/gotowebinar/ajax/check-api',
				'controller' => 'GoToWebinarBundle:Ajax:checkAPI'
			)
		)
	),
	'services' => array(

		'events' => array(
			'plugin.gotowebinar.config.subscriber' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\EventListener\ConfigSubscriber'
			),
			'plugin.gotowebinar.event.formsubscriber' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\EventListener\FormSubscriber',
				'arguments' => array('doctrine.orm.entity_manager', 'plugin.gotowebinar.service.gtwapi', 'kernel', 'translator', '%mautic.gotowebinar_enable_plugin%')
			),
			'plugin.gotowebinar.lead.subscriber' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\EventListener\LeadSubscriber',
				'arguments' => array('%mautic.gotowebinar_enable_plugin%')
			),
			'plugin.gotowebinar.campaignevent.subscriber' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\EventListener\CampaignSubscriber',
				'arguments' => array('%mautic.gotowebinar_enable_plugin%')
			)
		),

		'forms' => array(
			'plugin.gotowebinar.formtype.config' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\Form\Type\ConfigType',
				'alias' => 'gotowebinar_formtype_config'
			),
			'plugin.gotowebinar.formtype.webinarlist' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\Form\Type\WebinarlistType',
				'alias' => 'gotowebinar_formtype_webinarlist'
			),
			'plugin.gotowebinar.formtype.pushleadtoapi' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\Form\Type\PushLeadToApiType',
				'alias' => 'gotowebinar_formtype_formaction'
			),
			'plugin.gotowebinar.formtype.campaignevent' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\Form\Type\CampaignEventGotowebinarType',
				'alias' => 'gotowebinar_formtype_campaignevent',
				'arguments' => array('translator', 'mautic.factory')
			)
		),
	    'models' =>  [
	        'mautic.gotowebinar.model.webinar' => [
	            'class' => 'MauticPlugin\GoToWebinarBundle\Model\WebinarModel',
	            'arguments' => ['mautic.factory']
	        ]
	    ],
		'others' => array(
			'mautic.helper.gotowebinar.formaction' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\Helper\FormActionHelper',
				'arguments' => array('plugin.gotowebinar.service.gtwapi', 'plugin.gotowebinar.service.gtwsync')
			),
			'plugin.gotowebinar.service.setparameter' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\Services\SetParameterService',
				'alias' => 'gotowebinar_service_setparameter',
				'arguments' => array('mautic.configurator', 'mautic.helper.cache')
			),
			'plugin.gotowebinar.service.gtwapi' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\Services\GtwApiService',
				'alias' => 'gotowebinar_service_gtwapi',
				'arguments' => array('%mautic.gotowebinar_access_token%', '%mautic.gotowebinar_organizer_key%', 'translator')
			),
			'plugin.gotowebinar.service.gtwsync' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\Services\GtwSyncService',
				'alias' => 'gotowebinar_service_gtwsync',
				'arguments' => array('plugin.gotowebinar.service.gtwapi', 'mautic.factory')
			)
		)
	),

	'parameters' => array(
		'gotowebinar_consumer_key' => '',
		'gotowebinar_access_token' => '',
		'gotowebinar_organizer_key' => '',
		'gotowebinar_enable_plugin' => false
	)
);
