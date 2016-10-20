<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'name'        => 'CRM',
    'description' => 'Enables integration with Mautic supported CRMs.',
    'version'     => '1.0',
    'author'      => 'Mautic',

	'routes' => array(

		/** Pour le debug : à supprimer
		'main' => array(
			'ines_logs' => array(
				'path' => '/ines/logs',
				'controller' => 'MauticCrmBundle:Test:logs'
			)
		),
		*/

		/** Ajout d'un End-Point dans l'API Mautic pour récupérer le mapping et la config du plugin INES **/
		'api' => array(
			'plugin_crm_bundle_ines_get_mapping_api' => array(
				'path' => '/ines/getMapping',
				'controller' => 'MauticCrmBundle:Api:inesGetMapping',
				'method' => 'GET'
			)
		)
	)
);
