<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebcastBundle\Helper;

use MauticPlugin\GoToWebcastBundle\Services\GtwcastApiService;
use MauticPlugin\GoToWebcastBundle\Services\GtwcastSyncService;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\Field;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class FormActionHelper
 */
class FormActionHelper
{
	private $gtwcastApiService;
	private $gtwcastSyncService;

	/**
	 * Injection de dépendance : le helper a besoin des service "GoToWebcast API" et "Sync"
	 */
	public function __construct(GtwcastApiService $gtwcastApiService, GtwcastSyncService $gtwcastSyncService)
	{
		$this->gtwcastApiService = $gtwcastApiService;
		$this->gtwcastSyncService = $gtwcastSyncService;
	}

	/**
	 * Accesseurs pour les méthodes statiques
	 */
	public function getGtwcastApiService()
	{
		return $this->gtwcastApiService;
	}

	public function getGtwcastSyncService()
	{
		return $this->gtwcastSyncService;
	}


	/**
	 * Validateur de l'action "inscription du lead courant à un webcast"
	 *
	 * @return array(bool, string)
	 */
	public static function onValidate($post, $fields, MauticFactory $factory)
	{
		$success = true;
		$errorMessage = "";

		$translator = $factory->getTranslator();

		// Lecture des webcasts à venir, via l'API
		// et test de l'API par la même occasion
		$gtwcastApiService = $factory->getHelper('gotowebcast.formaction')->getGtwcastApiService();
		try {
			$webcastlist = $gtwcastApiService->getWebcastList($onlyFutures = true);
			$allWebcastKeys = array_keys($webcastlist);

			// Lecture des webcasts sélectionnés dans le formulaire
			$subscribedWebcasts = self::_getSubscribedWebcastsFromPost($fields, $post, $webcastlist);

			// Vérification de l'existence de ces webcasts
			foreach($subscribedWebcasts as $webcast) {
				if ( !in_array($webcast['webcastKey'], $allWebcastKeys)) {
					$success = false;
					$errorMessage = $translator->trans('plugin.gotowebcast.field.webcastlist.nolongeravailable');
				}
			}
		}
		catch (BadRequestHttpException $e) {
			// En cas d'erreur d'accès à l'API :
			$success = false;
			$errorMessage = $translator->trans('plugin.gotowebcast.field.webcastlist.cantaccesstoapi');
		}

		return array($success, $errorMessage);
	}


	/**
	 * Callback de l'action "inscription du lead courant à un webcast"
	 *
	 * @return void
	 */
	public static function onSubmit($post, $fields, Submission $submission, MauticFactory $factory)
    {
		$gtwcastApiService = $factory->getHelper('gotowebcast.formaction')->getGtwcastApiService();
		$gtwcastSyncService = $factory->getHelper('gotowebcast.formaction')->getGtwcastSyncService();

		// Lecture des webcasts à venir, via l'API
		try {
			$webcastlist = $gtwcastApiService->getWebcastList($onlyFutures = true);
		}
		catch (BadRequestHttpException $e) {
			$webcastlist = array();
		}

		// Lecture des webcasts sélectionnés dans le formulaire
		$subscribedWebcasts = self::_getSubscribedWebcastsFromPost($fields, $post, $webcastlist);

		if ( ! empty($subscribedWebcasts)) {

			// Mise en forme des résultats destinés à ATMT pour une forme plus humaine :
			// Ajout du titre des webcasts souscrits, en plus de leur ID
			$results = $submission->getResults();
			foreach($subscribedWebcasts as $webcast) {
				$field_alias = $webcast['field_alias'];
				$results[$field_alias] = $webcast['webcast_title'] . ' #' . $webcast['webcastKey'];
			}
			$submission->setResults($results);

			// Lecture du lead courant : email, prénom, nom, si disponibles
			$leadModel = $factory->getModel('lead');
			$currentLead = $leadModel->getCurrentLead();
			if ($currentLead instanceof Lead) {

				$leadFields = $leadModel->flattenFields($currentLead->getFields());

				list($email, $firstname, $lastname) = array(
					isset($leadFields['email']) ? $leadFields['email'] : '',
					isset($leadFields['firstname']) ? $leadFields['firstname'] : '',
					isset($leadFields['lastname']) ? $leadFields['lastname'] : ''
				);

				// L'email est obligatoires pour gérer l'inscription
				if ( !empty($email)) {

					// Inscription, via l'API, à (aux) webcast(s) sélectionné(s)
					foreach($subscribedWebcasts as $webcast) {
						$webcastKey = $webcast['webcastKey'];
						$isSubscribed = $gtwcastApiService->subscribeToWebcast($webcastKey, $email, $firstname, $lastname);

						// Si l'inscription a réussi, écriture d'un événement dans la timeline du lead
						if ($isSubscribed) {
							$webcastTitle = $webcastlist[$webcastKey];
							$webcastSlug = $gtwcastApiService->getWebcastSlug($webcastKey, $webcastTitle);
							$webcastModel = $factory->getModel('gotowebcast.webcast');
							$webcastModel->addEvent($email, $webcastSlug, 'registered');
						}
					}
				}
			}
		}
	}

	/**
	 * @return array( array(field_alias, webcastKey, webcast_title) )
	 */
	private static function _getSubscribedWebcastsFromPost($fields, $post, $webcastlist)
	{
		$subscribedWebcasts = array();

		foreach($fields as $field) {
			if ($field['type'] === 'plugin.gotowebcast.formfield.webcastlist') {
				$alias = $field['alias'];
				$webcastKey = $post[$alias];

				$subscribedWebcasts[] = array(
					'field_alias' => $alias,
					'webcastKey' => $webcastKey,
					'webcast_title' => array_key_exists($webcastKey, $webcastlist) ? $webcastlist[$webcastKey] : 'unknown'
				);
			}
		}
		return $subscribedWebcasts;
	}
}

?>
