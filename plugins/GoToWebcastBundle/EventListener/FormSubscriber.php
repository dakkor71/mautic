<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebcastBundle\EventListener;

use Mautic\CoreBundle\Factory\MauticFactory;
use MauticPlugin\GoToWebcastBundle\Services\GtwcastApiService;
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
 * 1. Ajoute un champ personnalisé "Liste des webcasts"
 * 2. Ajoute une action de formulaire pour inscrire un lead au webcast choisi, via l'API GoToWebcast
 *
 */
class FormSubscriber extends CommonSubscriber
{
	protected $entityManager;
	protected $kernel;
	protected $gtwcastApiService;
	protected $translator;

	/**
	 * Injection de dépendances
	 */
	public function __construct(
		EntityManager $entityManager,
		GtwcastApiService $gtwcastApiService,
		AppKernel $kernel,
		TranslatorInterface $translator
	) {
		$this->entityManager = $entityManager;
		$this->kernel = $kernel;
		$this->gtwcastApiService = $gtwcastApiService;
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
		// Hydratation initiale de la liste des webcasts
		// Sera remis à jour à chaque enregistrement du formulaire
		$webcastlist = $this->_getNextWebcastListFromApi();

		// Chemin vers le fichier 'field_helper.php', nécessaire au template
		$fieldHelperPath = $this->kernel->locateResource('@MauticFormBundle/Views/Field/field_helper.php');

		// Ajout du champ Automation personnalisé : "Liste des webcasts"
		// La liste sera hydratée à chaque enregistrement du formulaire
        $event->addFormField(
            'plugin.gotowebcast.formfield.webcastlist',
            array(
                'label' => 'plugin.gotowebcast.formfield.webcastlist.label',

                // Formulaire utilisé dans l'onglet "Properties" de la config du champ
                'formType' => 'gotowebcast_formtype_webcastlist',
				'formTypeOptions' => array(
					'data' => array(
						'webcastlist_serialized' => serialize($webcastlist),
						'mautic_field_helper_path' => $fieldHelperPath
					)
				),

                // Template qui affiche le champ "Liste des webcasts" dans le formulaire final
                'template' => 'GoToWebcastBundle:Field:webcastlist.html.php',

				// Validateur du champ : inutilisable car n'a accès à aucun contexte d'exécution
				// 'valueConstraints' => ''
            )
        );

		// Ajout d'une action de formulaire pour traiter les inscriptions aux webcasts
		$event->addSubmitAction(
            'plugin.gotowebcast.formaction.pushleadtoapi',
            array(
				'group' => 'plugin.gotowebcast.formaction.group',
                'label' => 'plugin.gotowebcast.formaction.label',
				'description' => 'plugin.gotowebcast.formaction.description',
				'formType' => 'gotowebcast_formtype_formaction',
				'template' => 'MauticFormBundle:Action:generic.html.php',
                'validator' => 'MauticPlugin\GoToWebcastBundle\Helper\FormActionHelper::onValidate',
                'callback' => 'MauticPlugin\GoToWebcastBundle\Helper\FormActionHelper::onSubmit'
            )
        );
    }

	/**
	 * Appelé lors de l'enregistrement d'un formulaire ATMT.
	 * Si le formulaire contient un ou plusieurs champs "webcast list" :
	 * - Mise à jour de la liste des webcasts à venir pour chacun
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

			$hasWebcastlistFields = false;

			// Mise à jour des listes de webcasts pour les champs concernés
			foreach($fields as $field) {
				if ($field->getType() == 'plugin.gotowebcast.formfield.webcastlist') {
					$properties = $field->getProperties();
					$properties['webcastlist_serialized'] = serialize($this->_getNextWebcastListFromApi());
					$field->setProperties($properties);
					$this->entityManager->persist($field);
					$hasWebcastlistFields = true;
				}
			}
			if ($hasWebcastlistFields) {
				$this->entityManager->flush();
			}

			// Vérification de la présence des champs obligatoires,
			// et ajout d'un marqueur dans le nom du formulaire en cas d'erreur
			$errors = $this->_checkWebcastFormValidity($form);
			$errorSeparator = '~ GoToWebcast';
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
	private function _getNextWebcastListFromApi()
	{
		// Lecture de la liste des webcasts à venir, directement depuis l'API
		try {
			$webcastlist = $this->gtwcastApiService->getWebcastList($onlyFutures = true);
		}
		catch (BadRequestHttpException $e) {
			$webcastlist = array();
		}
		return $webcastlist;
	}

	/**
	 * Vérifie si le formulaire contient une action "PUSH LEAD TO GOTOWEBCAST"
	 * Si oui, vérifie que :
	 * 1. le formulaire contient au moins un champ "WEBCAST LIST"
	 * 2. le formulaire contient le champ email obligatoire et liés aux champs email du lead
	 *
	 * @param Mautic\FormBundle\Entity\Form
	 * @return array( string )
	 */
	private function _checkWebcastFormValidity($form)
	{
		$errors = array();

		// Liste des traductions utiles
		$errorMessages = array(
			'lead_field_not_found' => $this->translator->trans('plugin.gotowebcast.formaction.validator.leadfieldnotfound'),
			'field_not_found' => $this->translator->trans('plugin.gotowebcast.formaction.validator.fieldnotfound'),
			'field_should_be_required' => $this->translator->trans('plugin.gotowebcast.formaction.validator.fieldshouldberequired')
		);

		// Le formulaire contient-il l'action "PUSH LEAD TO GTW" ?
		$hasWebcastAction = false;
		$actions = $form->getActions();
		if ($actions) {
			foreach($actions as $action) {
				if ($action->getType() == 'plugin.gotowebcast.formaction.pushleadtoapi') {
					$hasWebcastAction = true;
					break;
				}
			}
		}

		// Si oui, la vérification continue
		if ($hasWebcastAction) {

			// - Recherche des champs liés à un champ du lead et pour chacun détection du flag 'obligatoire' ?
			// - Recherche du(des) champ(s) de type 'webcast-list'
			$currentLeadFields = array();
			$hasFieldWebcastList = false;
			$fields = $form->getFields();
			if ($fields) {
				foreach($fields as $field) {

					$leadFieldSlug = $field->getLeadField();
					if ( !empty($leadFieldSlug)) {
						$currentLeadFields[$leadFieldSlug] = $field->getIsRequired();
					}

					if ($field->getType() == 'plugin.gotowebcast.formfield.webcastlist') {
						$hasFieldWebcastList = true;
						if ( !$field->getIsRequired()) {
							$errors[] = sprintf($errorMessages['field_should_be_required'], 'webcast-list');
						}
					}
				}
			}

			// Au moins un champ 'webcast-list' doit être présent
			if ( !$hasFieldWebcastList) {
				$errors[] = sprintf($errorMessages['field_not_found'], 'webcast-list');
			}

			// Vérification que les champs obligatoires s'y trouvent bien et sont obligatoires
			$mandatoryFields = array('email');
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
