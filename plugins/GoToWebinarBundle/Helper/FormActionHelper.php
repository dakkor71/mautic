<?php
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebinarBundle\Helper;

use MauticPlugin\GoToWebinarBundle\Services\GtwApiService;
use MauticPlugin\GoToWebinarBundle\Services\GtwSyncService;
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
	private $gtwApiService;
	private $gtwSyncService;

	/**
	 * Injection de dépendance : le helper a besoin des service "GoToWebinar API" et "Sync"
	 */
	public function __construct(GtwApiService $gtwApiService, GtwSyncService $gtwSyncService)
	{
		$this->gtwApiService = $gtwApiService;
		$this->gtwSyncService = $gtwSyncService;
	}

	/**
	 * Accesseurs pour les méthodes statiques
	 */
	public function getGtwApiService()
	{
		return $this->gtwApiService;
	}

	public function getGtwSyncService()
	{
		return $this->gtwSyncService;
	}



	/**
	 * Validateur de l'action "inscription du lead courant à un webinar"
	 *
	 * @return array(bool, string)
	 */
	public static function onValidate($post, $fields, MauticFactory $factory)
	{
		$success = true;
		$errorMessage = "";

		$translator = $factory->getTranslator();

		// Lecture des webinaires à venir, via l'API
		// et test de l'API par la même occasion
		$gtwApiService = $factory->getHelper('gotowebinar.formaction')->getGtwApiService();
		try {
			$webinarlist = $gtwApiService->getWebinarList($onlyFutures = true);
			$allWebinarKeys = array_keys($webinarlist);

			// Lecture des webinaires sélectionnés dans le formulaire
			$subscribedWebinars = self::_getSubscribedWebinarsFromPost($fields, $post, $webinarlist);

			// Vérification de l'existence de ces webinaires
			foreach($subscribedWebinars as $webinar) {
				if ( !in_array($webinar['webinarKey'], $allWebinarKeys)) {
					$success = false;
					$errorMessage = $translator->trans('plugin.gotowebinar.field.webinarlist.nolongeravailable');
				}
			}
		}
		catch (BadRequestHttpException $e) {
			// En cas d'erreur d'accès à l'API :
			$success = false;
			$errorMessage = $translator->trans('plugin.gotowebinar.field.webinarlist.cantaccesstoapi');
			$errorMessage = $e->getMessage();
		}

		return array($success, $errorMessage);
	}

	/**
	 * Callback de l'action "inscription du lead courant à un webinar"
	 *
	 * @return void
	 */
	public static function onSubmit($post, $fields, Submission $submission, MauticFactory $factory)
    {
		$gtwApiService = $factory->getHelper('gotowebinar.formaction')->getGtwApiService();
		$gtwSyncService = $factory->getHelper('gotowebinar.formaction')->getGtwSyncService();

		// Lecture des webinaires à venir, via l'API
		try {
			$webinarlist = $gtwApiService->getWebinarList($onlyFutures = true);
		}
		catch (BadRequestHttpException $e) {
			$webinarlist = array();
		}

		// Lecture des webinars sélectionnés dans le formulaire
		$subscribedWebinars = self::_getSubscribedWebinarsFromPost($fields, $post, $webinarlist);

		if ( ! empty($subscribedWebinars)) {

			// Mise en forme des résultats destinés à ATMT pour une forme plus humaine :
			// Ajout du titre des webinars souscrits, en plus de leur ID
			$results = $submission->getResults();
			foreach($subscribedWebinars as $webinar) {
				$field_alias = $webinar['field_alias'];
				$results[$field_alias] = $webinar['webinar_title'] . ' #' . $webinar['webinarKey'];
			}
			$submission->setResults($results);

			// Lecture du lead courant : email, prénom, nom
			$leadModel = $factory->getModel('lead');
			$currentLead = $leadModel->getCurrentLead();
			if ($currentLead instanceof Lead) {

				$leadFields = $leadModel->flattenFields($currentLead->getFields());

				list($email, $firstname, $lastname) = array(
					isset($leadFields['email']) ? $leadFields['email'] : '',
					isset($leadFields['firstname']) ? $leadFields['firstname'] : '',
					isset($leadFields['lastname']) ? $leadFields['lastname'] : ''
				);

				// Ces trois champs sont obligatoires pour gérer l'inscription
				if ( !empty($email) && !empty($firstname) && !empty($lastname)) {

					// Inscription, via l'API, à (aux) webinaire(s) sélectionné(s)
					foreach($subscribedWebinars as $webinar) {
						$webinarKey = $webinar['webinarKey'];
						$isSubscribed = $gtwApiService->subscribeToWebinar($webinarKey, $email, $firstname, $lastname);

						// Si l'inscription a réussi, écriture d'un événement dans la timeline du lead
						if ($isSubscribed) {
							$webinarSlug = $gtwApiService->getWebinarSlug($webinarKey);
							$webinarModel = $factory->getModel('gotowebinar.webinar');
							$webinarModel->addEvent($email, $webinarSlug, 'registered');
						}
					}
				}
			}
		}
	}

	/**
	 * @return array( array(field_alias, webinarKey, webinar_title) )
	 */
	private static function _getSubscribedWebinarsFromPost($fields, $post, $webinarlist)
	{
		$subscribedWebinars = array();

		foreach($fields as $field) {
			if ($field['type'] === 'plugin.gotowebinar.formfield.webinarlist') {
				$alias = $field['alias'];
				$webinarKey = $post[$alias];

				$subscribedWebinars[] = array(
					'field_alias' => $alias,
					'webinarKey' => $webinarKey,
					'webinar_title' => array_key_exists($webinarKey, $webinarlist) ? $webinarlist[$webinarKey] : 'unknown'
				);
			}
		}
		return $subscribedWebinars;
	}
}

?>