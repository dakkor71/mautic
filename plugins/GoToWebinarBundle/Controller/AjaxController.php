<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebinarBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;


/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{
	/** 
	 * Mémorise le ConsumerKey dans la config du plugin puis retourne l'URL à appeler pour la connexion oAuth
	 *
	 * @param string $_POST['consumerKey']
	 * @return json(success, url, flashes)
	 */
	public function getOauthUrlAction () {
		$success = 0;
		$url = false;
		
		$consumerKey = $this->getRequest()->request->get('consumerKey');
		
		if ( !empty($consumerKey)) {
			
			// Mémorise le consumer key dans la config du plugin
			$setParameterService = $this->get('plugin.gotowebinar.service.setparameter');
			if ( ! $setParameterService->set('gotowebinar_consumer_key', $consumerKey)) {
				$this->addFlash('plugin.gotowebinar.error.confignotwritable', array(), 'error', null, false);
			}

			// Puis construit l'URL pour appeler oAuth
			$gtwApiService = $this->get('plugin.gotowebinar.service.gtwapi');
			$url = $gtwApiService->getOauthUrl($consumerKey);
			$success = 1;
		}
		else {
			$this->addFlash('plugin.gotowebinar.error.emptyconsumerkey', array(), 'error', null, false);
		}

		return $this->sendJsonResponse(array(
			'success' => $success,
			'url' => $url,
			'flashes' => $this->getFlashContent()
		));
	}
	
	/** 
	 * @return json(success)
	 */
	public function checkApiAction() 
	{
		$gtwApiService = $this->get('plugin.gotowebinar.service.gtwapi');
		$success = $gtwApiService->checkApi();
		
		return $this->sendJsonResponse(array(
			'success' => $success ? 1 : 0
		));
	}
	
}

?>