<?php
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebinarBundle\EventListener;

use Mautic\CoreBundle\Factory\MauticFactory;
use MauticPlugin\GoToWebinarBundle\Services\GtwApiService;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use Doctrine\ORM\EntityManager;
use AppKernel;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class FormSubscriber
 *
 * Gère les événements liés aux formulaires Automation :
 * 1. Ajoute un champ personnalisé "Liste des webinaires"
 * 2. Ajoute une action de formulaire pour inscrire un lead au webinaire choisi, via l'API GoToWebinar
 *
 */
class FormSubscriber extends CommonSubscriber
{
	protected $entityManager;
	protected $kernel;
	protected $gtwApiService;
	protected $translator;

	/**
	 * Injection de dépendances
	 */
	public function __construct(
		EntityManager $entityManager,
		GtwApiService $gtwApiService,
		AppKernel $kernel,
		TranslatorInterface $translator
	) {
		$this->entityManager = $entityManager;
		$this->kernel = $kernel;
		$this->gtwApiService = $gtwApiService;
		$this->translator = $translator;
	}

	/**
     * Retourne la liste des événements écoutés
     *
     * @return array
     */
	static public function getSubscribedEvents()
    {
        return array(
            FormEvents::FORM_ON_BUILD => array('onFormBuilder', 0),
            FormEvents::FORM_PRE_SAVE => array('onFormPreSave', 0)
        );
    }

	/**
     * @return void
     */
	public function onFormBuilder(Events\FormBuilderEvent $event)
    {
		// Hydratation initiale de la liste des webinaires
		// Sera remis à jour à chaque enregistrement du formulaire
		$webinarlist = $this->_getNextWebinarListFromApi();

		// Chemin vers le fichier 'field_helper.php', nécessaire au template
		$fieldHelperPath = $this->kernel->locateResource('@MauticFormBundle/Views/Field/field_helper.php');

		// Ajout du champ Automation personnalisé : "Liste des webinaires"
		// La liste sera hydratée à chaque enregistrement du formulaire
        $event->addFormField(
            'plugin.gotowebinar.formfield.webinarlist',
            array(
                'label' => 'plugin.gotowebinar.formfield.webinarlist.label',

                // Formulaire utilisé dans l'onglet "Properties" de la config du champ
                'formType' => 'gotowebinar_formtype_webinarlist',
				'formTypeOptions' => array(
					'data' => array(
						'webinarlist_serialized' => serialize($webinarlist),
						'mautic_field_helper_path' => $fieldHelperPath
					)
				),

                // Template qui affiche le champ "Liste des webinaires" dans le formulaire final
                'template' => 'GoToWebinarBundle:Field:webinarlist.html.php',

				// Validateur du champ : inutilisable car n'a accès à aucun contexte d'exécution
				// 'valueConstraints' => ''
            )
        );

		// Ajout d'une action de formulaire pour traiter les inscriptions aux webinaires
		$event->addSubmitAction(
            'plugin.gotowebinar.formaction.pushleadtoapi',
            array(
				'group' => 'plugin.gotowebinar.formaction.group',
                'label' => 'plugin.gotowebinar.formaction.label',
				'description' => 'plugin.gotowebinar.formaction.description',
				'formType' => 'gotowebinar_formtype_formaction',
				'template' => 'MauticFormBundle:Action:generic.html.php',
                'validator' => 'MauticPlugin\GoToWebinarBundle\Helper\FormActionHelper::onValidate',
                'callback' => 'MauticPlugin\GoToWebinarBundle\Helper\FormActionHelper::onSubmit'
            )
        );
    }

	/**
	 * Si le formulaire contient un ou plusieurs champs "webinar list" :
	 * - Mise à jour de la liste des webinaires à venir pour chacun
	 * - Vérification de la validité du formulaire
	 *
     * @return void
     */
	public function onFormPreSave (Events\FormEvent $event)
	{
		// Liste des champs du formulaire ATMT
		$form = $event->getForm();
		$fields = $form->getFields()->getValues();

		if ($fields) {

			$hasWebinarlistFields = false;

			// Mise à jour des listes de webinaires pour les champs concernés
			foreach($fields as $field) {
				if ($field->getType() == 'plugin.gotowebinar.formfield.webinarlist') {
					$properties = $field->getProperties();
					$properties['webinarlist_serialized'] = serialize($this->_getNextWebinarListFromApi());
					$field->setProperties($properties);
					$this->entityManager->persist($field);
					$hasWebinarlistFields = true;
				}
			}
			if ($hasWebinarlistFields) {
				$this->entityManager->flush();
			}

			// Vérification de la présence des champs obligatoires,
			// et ajout d'un marqueur dans le nom du formulaire en cas d'erreur
			$errors = $this->_checkWebinarFormValidity($form);
			$errorSeparator = '~ GoToWebinar';
			$formName = $form->getName();
			$newFormName = trim(explode($errorSeparator, $formName)[0]); /* nettoyage des erreurs passées */
			if ( !empty($errors)) {
				$newFormName .= ' ' . $errorSeparator . ' ' . $errors[0];
			}
			if ($newFormName != $formName) {
				$form->setName($newFormName);
				$this->entityManager->persist($form);
				$this->entityManager->flush();
			}
		}
	}

	/**
	 * @return array
	 */
	private function _getNextWebinarListFromApi()
	{
		// Lecture de la liste des webinaires à venir, directement depuis l'API
		try {
			$webinarlist = $this->gtwApiService->getWebinarList($onlyFutures = true);
		}
		catch (BadRequestHttpException $e) {
			$webinarlist = array();
		}
		return $webinarlist;
	}

	/**
	 * Vérifie si le formulaire contient une action "PUSH LEAD TO GOTOWEBINAR"
	 * Si oui, vérifie que :
	 * 1. le formulaire contient l'action 'push lead to GTW'
	 * 2. le formulaire contient au moins un champ "WEBINAR LIST"
	 * 3. le formulaire contient 3 champs obligatoires et liés aux champs "email, firstname et lastname" du lead
	 *
	 * @param Mautic\FormBundle\Entity\Form
	 * @return array( string )
	 */
	private function _checkWebinarFormValidity($form)
	{
		$errors = array();

		// Liste des traductions utiles
		$errorMessages = array(
			'lead_field_not_found' => $this->translator->trans('plugin.gotowebinar.formaction.validator.leadfieldnotfound'),
			'field_not_found' => $this->translator->trans('plugin.gotowebinar.formaction.validator.fieldnotfound'),
			'field_should_be_required' => $this->translator->trans('plugin.gotowebinar.formaction.validator.fieldshouldberequired')
		);

		// Le formulaire contient-il l'action "PUSH LEAD TO GTW" ?
		$hasWebinarAction = false;
		$actions = $form->getActions();
		if ($actions) {
			foreach($actions as $action) {
				if ($action->getType() == 'plugin.gotowebinar.formaction.pushleadtoapi') {
					$hasWebinarAction = true;
					break;
				}
			}
		}

		// Si oui, la vérification continue
		if ($hasWebinarAction) {

			// - Recherche des champs liés à un champ du lead et pour chacun détection du flag 'obligatoire' ?
			// - Recherche du(des) champ(s) de type 'webinar-list'
			$currentLeadFields = array();
			$hasFieldWebinarList = false;
			$fields = $form->getFields();
			if ($fields) {
				foreach($fields as $field) {

					$leadFieldSlug = $field->getLeadField();
					if ( !empty($leadFieldSlug)) {
						$currentLeadFields[$leadFieldSlug] = $field->getIsRequired();
					}

					if ($field->getType() == 'plugin.gotowebinar.formfield.webinarlist') {
						$hasFieldWebinarList = true;
						if ( !$field->getIsRequired()) {
							$errors[] = sprintf($errorMessages['field_should_be_required'], 'webinar-list');
						}
					}
				}
			}

			// Au moins un champ 'webinar-list' doit être présent
			if ( !$hasFieldWebinarList) {
				$errors[] = sprintf($errorMessages['field_not_found'], 'webinar-list');
			}

			// Vérification que les champs obligatoires s'y trouvent bien et sont obligatoires
			$mandatoryFields = array('email', 'firstname', 'lastname');
			foreach($mandatoryFields as $mandatoryField) {
				if ( !array_key_exists($mandatoryField, $currentLeadFields)) {
					$errors[] = sprintf($errorMessages['lead_field_not_found'], $mandatoryField);
				}
				else if ( !$currentLeadFields[$mandatoryField]) {
					$errors[] = sprintf($errorMessages['field_should_be_required'], $mandatoryField);
				}
			}
		}

		return $errors;
	}
}
