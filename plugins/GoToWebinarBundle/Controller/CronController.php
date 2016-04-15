<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebinarBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class CronController
 */
class CronController extends CommonController
{

	/**
	 * Synchronise Mautic avec GoToWebinar
	 * Pour chaque webinar, récupère via l'API les listes d'inscrits et de participants
	 * Puis met à jour les tags des leads concernés, en les identifiant par leur email
	 *
	 * Cette méthode peut être appelée via un CRON ou en AJAX, au choix 
	 *
	 * @param void | $_POST['webinarKey']	Si 'void', traite tous les webinaires, sinon celui spécifié
	 * @return jsonObject
	 */
	public function syncAction () {
		$gtwApiService = $this->get('plugin.gotowebinar.service.gtwapi');
		$gtwSyncService = $this->get('plugin.gotowebinar.service.gtwsync');
		
		try {
			// Si un webinarKey est envoyé en $_POST, c'est le seul qui sera traité
			$post = $this->getRequest()->request;
			$webinarKey = $post->get('webinarKey');
			if ($webinarKey !== null) {
				$webinarsKeysToSync = array($webinarKey);
			}
			else {
				// Sinon, tous les webinaires seront traités
				$webinarsKeysToSync = array_keys( $gtwApiService->getWebinarList($onlyFutures=false, $onlySubjects=true) );
			}
			
			// Lance la synchronisation de tous les webinaires à traiter
			$tagsAddedOrRemovedByLead = $gtwSyncService->sync($webinarsKeysToSync);

			
			if ($this->getRequest()->isXmlHttpRequest()) {
				
				// Rapport de synchro, en HTML
				$syncreport = $this->renderView('GoToWebinarBundle:Page:syncreport.html.php', array(
					'tagsAddedOrRemovedByLead' => $tagsAddedOrRemovedByLead
				));
				
				return new JsonResponse(array(
					'success' => 1,
					'syncreport' => $syncreport
				));
			}
			else {
				return new JsonResponse(array(
					'success' => 1,
					'tagsAddedOrRemovedByLead' => $tagsAddedOrRemovedByLead
				));
			}
			
		}
		catch (BadRequestHttpException $e) {
			
			// En cas d'erreur de connexion à l'API
			return new JsonResponse(array(
				'success' => 0,
				'message' => $this->get('translator')->trans('plugin.gotowebinar.page.apiunavailable') .
							 ' : ' . $e->getMessage()
			));
		}
	}
	
}

?>