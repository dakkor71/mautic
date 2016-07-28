<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebinarBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PublicController
 */
class PublicController extends CommonController
{

	/**
	 * Appelé par CITRIX lors d'une demande de token
	 * Permet de récupérer un code via $_GET, nécessaire à l'obtention du token
	 */
	public function requestTokenAction () {
		
		$request = $this->getRequest();
		$scope = $request->query->get('scope');
		$code = $request->query->get('code');
		
		if ($code === null || $scope === null) {
			throw new AccessDeniedHttpException();
		}
		
		$consumerKey = $this->factory->getParameter('gotowebinar_consumer_key');
		
		$gtwApiService = $this->get('plugin.gotowebinar.service.gtwapi');
		$response = $gtwApiService->requestToken($code, $consumerKey);
		
		$setParameterService = $this->get('plugin.gotowebinar.service.setparameter');
		$setParameterService->set('gotowebinar_access_token', $response->access_token);
		$setParameterService->set('gotowebinar_organizer_key', $response->organizer_key);
		
		return $this->redirect('/s/config/edit#gotowebinar_formtype_config');
	}
	
}

?>