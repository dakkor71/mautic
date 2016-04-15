<?php
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
return array(
    'name'        => 'GoToWebinar',
    'description' => 'plugin.gotowebinar.description',
    'author'      => 'Webmecanik',
    'version'     => '1.0.0',

	'routes' => array(
		'public' => array(
			'plugin.gotowebinar.route.public.oauthredirect' => array(
				'path' => '/gotowebinar/request-token',
				'controller' => 'GoToWebinarBundle:Public:requestToken'
			),
			'plugin.gotowebinar.route.public.cronsync' => array(
				'path' => '/gotowebinar/cron/sync',
				'controller' => 'GoToWebinarBundle:Cron:sync'
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
			),
			'plugin.gotowebinar.route.main.page' => array(
				'path' => '/gotowebinar',
				'controller' => 'GoToWebinarBundle:Page:index'
			)
		)
	),
	
	'menu' => array(
		'main' => array(
			'priority' => 60,
			'items' => array(
				'plugin.gotowebinar.menu.index' => array(
					'id' => 'plugin_gotowebinar_menu_index',
					'route' => 'plugin.gotowebinar.route.main.page',
					'iconClass' => 'fa-video-camera'
				)
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
				'arguments' => array('doctrine.orm.entity_manager', 'plugin.gotowebinar.service.gtwapi', 'kernel', 'translator')
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
			)
		),
		
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
				'arguments' => array('plugin.gotowebinar.service.gtwapi', 'plugin.gotowebinar.service.leads')
			),
			'plugin.gotowebinar.service.leads' => array(
				'class' => 'MauticPlugin\GoToWebinarBundle\Services\LeadsService',
				'alias' => 'gotowebinar_service_leads',
				'arguments' => array('doctrine.orm.entity_manager', 'database_connection', 'mautic.factory')
			)
		)
	),
	
	'parameters' => array(
		'gotowebinar_consumer_key' => '',
		'gotowebinar_access_token' => '',
		'gotowebinar_organizer_key' => ''
	)
);
