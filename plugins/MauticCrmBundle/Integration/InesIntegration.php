<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\LeadBundle\Entity\lead;

/**
 * Class InesIntegration
 */
class InesIntegration extends CrmAbstractIntegration
{

	/**
	 * Array	Mapping retourné par l'intégration, mémorisé en attribut pour limiter les appels
	 */
	protected $mapping = false;


    /**
     * Nom de l'intégration (doit correspondre au nom du fichier et de la classe)
     *
     * @return string
     */
    public function getName()
    {
        return 'Ines';
    }


	/**
	 * Description de l'intégration, affichée en tête de l'onglet de config
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->getTranslator()->trans('mautic.ines.description');
	}


	/**
	 * L'intégration supporte la fonction "push lead to integration"
	 *
	 * return 	array(string)
	 */
	public function getSupportedFeatures()
    {
        return array('push_lead');
    }


	/**
	 * Liste des champs nécessaires à la configuration du compte INES, pour accéder aux Web-Services
	 * Les champs dans le formulaire de config, leur hydratation et sauvegarde sont gérés automatiquement
	 *
	 * @return array
	 */
	public function getRequiredKeyFields()
	{
		return array(
			'compte' => 'mautic.ines.form.account',
			'userName' => 'mautic.ines.form.user',
			'password' => 'mautic.ines.form.password'
		);
	}


	/**
     * Ajoute des champs dans les formulaires "config" et "features" du plugin
	 *
	 * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
		// Tant que l'utilisateur navigue entre les onglets de config, il est préférable de ne pas conserver
		// la config en provenance du WS INES : si elle est modifiée chez INES on doit le savoir tout de suite.
		// Par exemple l'ajout d'un custom field.
		$this->unsetCurrentSyncConfig();

		// Onglet Enabled/Auth
		if ($formArea == 'keys') {

			// Ajout d'un bouton pour tester la connexion
			$builder->add('check_api_button', 'standalone_button', array(
				'label' => 'mautic.ines.form.check.btn',
				'attr'     => array(
                    'class'   => 'btn btn-primary',
                    'onclick' => "
						var btn = mQuery(this);
						btn.next('.message').remove();
						Mautic.postForm(mQuery('form[name=\"integration_details\"]'), function (response) {
							if (response.newContent) {
						        Mautic.processModalContent(response, '#IntegrationEditModal');
						    } else {
								mQuery.ajax({
							        url: mauticAjaxUrl,
							        type: 'POST',
							        data: 'action=plugin:mauticCrm:inesCheckConnexion',
							        dataType: 'json',
							        success: function (response) {
										btn.after('<span class=\"message\" style=\"font-weight:bold; margin-left:10px;\">' + response.message + '</span>');
							        },
							        error: Mautic.processAjaxError,
							        complete: Mautic.stopIconSpinPostEvent
							    });
							}
						});",
					'icon' => 'fa fa-check',
                ),
				'required' => false
			));
		}

		// Onglet Features
		else if ($formArea == 'features') {

			// Case à cocher : synchro complète ?
			$builder->add(
                'full_sync',
                'choice',
                [
                    'choices'     => [
                        'is_full_sync' => 'mautic.ines.isfullsync'
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.ines.form.isfullsync',
                    'label_attr'  => ['class' => 'control-label'],
                    'empty_value' => false,
                    'required'    => false
                ]
            );


			// Liste des champs disponibles chez INES et disponibles pour l'option "champ écrasable"
			$inesFields = $this->getApiHelper()->getLeadFields();
			$choices = array();
			foreach($inesFields as $field) {
				if ($field['excludeFromEcrasableConfig']) {
					continue;
				}
				$key = $field['concept'].'_'.$field['inesKey'];
				$choices[$key] = $field['inesLabel'];
			}

			$builder->add(
				'not_ecrasable_fields',
				'choice',
				array(
					'choices'     => $choices,
					'expanded'    => true,
					'multiple'    => true,
					'label'       => 'mautic.ines.form.protected.fields',
					'label_attr'  => ['class' => ''],
					'empty_value' => false,
					'required'    => false
				)
			);
		}
	}


	/**
	 * Vérifie s'il existe un ID de session INES, ou, dans le cas contraire, si les codes d'accès aux WS sont valides
	 *
	 * @return bool
	 */
	public function isAuthorized()
	{
		if (!$this->isConfigured()) {
			return false;
		}

		$sessionID = $this->getWebServiceCurrentSessionID();
		return $sessionID ? true : $this->checkAuth();
	}


	/**
	 * Vérifie si les codes d'accès aux web-services sont valides
	 * La session INES est supprimée au préalable si elle existe
	 *
	 * @return bool
	 */
	public function checkAuth()
	{
		try {
			$this->getApiHelper()->refreshSessionID();
			return true;

		} catch (\Exception $e) {
			$this->logIntegrationError($e);
			return false;
		}
	}


	/**
	 * Respectivement : lit, écrit et supprime l'ID de session temporaire nécessaire aux appels aux web-services INES
	 */
	public function getWebServiceCurrentSessionID()
	{
		return $this->factory->getSession()->get('ines_session_id');
	}
	public function setWebServiceCurrentSessionID($sessionID)
	{
		$this->factory->getSession()->set('ines_session_id', $sessionID);
	}
	public function unsetWebServiceCurrentSessionID()
	{
		$this->factory->getSession()->set('ines_session_id', null);
	}


	/**
	 * Respectivement : lit, écrit et supprime en session PHP la config de la synchro définie chez INES
	 */
	public function getCurrentSyncConfig()
	{
		return $this->factory->getSession()->get('ines_sync_config');
	}
	public function setCurrentSyncConfig($syncConfig)
	{
		$this->factory->getSession()->set('ines_sync_config', $syncConfig);
	}
	public function unsetCurrentSyncConfig()
	{
		$this->factory->getSession()->set('ines_sync_config', null);
	}


	/**
	 * Prépare le formulaire de mapping des champs
	 *
	 * @return 	array
	 * @throws 	\Exception
	 */
	public function getAvailableLeadFields ($settings = array())
	{
		$inesFields = array();
		$silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

		try {
            if ($this->isAuthorized()) {
                $leadFields = $this->getApiHelper()->getLeadFields();

				foreach($leadFields as $field) {

					// Les champs dont le mapping est imposé en interne sont exclus du formulaire de mapping
					if ($field['autoMapping'] !== false) {
						continue;
					}

					$key = $field['concept'].'_'.$field['inesKey'];

					$inesFields[$key] = array(
						'type' => 'string',
						'label' => $field['inesLabel'],
						'required' => $field['isMappingRequired']
					);
				}
			}
		} catch (\Exception $e) {
			$this->logIntegrationError($e);

			if (!$silenceExceptions) {
				throw $e;
			}
		}

		return $inesFields;
	}


	/**
	 * Retourne le mapping brut, issu du formulaire de mapping, sous forme d'un tableau associatif : ines_key => atmt_key
	 * Ne prend pas en compte les champs mappé automatiquement
	 *
	 * @return 	array
	 */
	public function getRawMapping()
	{
		if (!$this->isConfigured()) {
			return array();
		}

		$featureSettings = $this->getIntegrationSettings()->getFeatureSettings();
		return $featureSettings['leadFields'];
	}


	/**
	 * Retourne le mapping complet, avec tous les détails connus sur chaque champ
	 *
	 * @return 	array
	 */
	public function getMapping()
	{
		// Si déjà généré dans le même runtime, le retourne immédiatement
		if ($this->mapping !== false) {
			return $this->mapping;
		}

		$mappedFields = array();

		// Liste de tous les champs INES disponibles
		$leadFields = $this->getApiHelper()->getLeadFields();

		// Liste des champs non écrasables ? (sous la forme : concept_inesKey)
		// 1 : ceux cochés dans le formulaire par l'utilisateur
		$featureSettings = $this->getIntegrationSettings()->getFeatureSettings();
		$notEcrasableFields = isset($featureSettings['not_ecrasable_fields']) ? $featureSettings['not_ecrasable_fields'] : array();
		// 2 : les champs exclus du formulaire
		foreach($leadFields as $field) {
			if ($field['excludeFromEcrasableConfig']) {
				$notEcrasableFields[] = $field['concept'].'_'.$field['inesKey'];
			}
		}

		// Liste des champs custom
		$customFields = array();
		foreach($leadFields as $field) {
			if ($field['isCustomField']) {
				$customFields[] = $field['concept'].'_'.$field['inesKey'];
			}
		}

		// Lecture et enrichissement des champs auto-mappés
		foreach($leadFields as $field) {

			if ($field['autoMapping'] !== false) {

				$internalKey = $field['concept'].'_'.$field['inesKey'];

				$mappedFields[] = array(
					'concept' => $field['concept'],
					'inesFieldKey' => $field['inesKey'],
					'isCustomField' => $field['isCustomField'] ? 1 : 0,
					'atmtFieldKey' => $field['autoMapping'],
					'isEcrasable' => in_array($internalKey, $notEcrasableFields) ? 0 : 1
				);
			}
		}

		// Lecture et enrichissement des champs mappés dans le formulaire, par l'utilisateur
		$rawMapping = $this->getRawMapping();
		foreach($rawMapping as $internalKey => $atmtKey) {

			list($concept, $inesKey) = explode('_', $internalKey);

			$mappedFields[] = array(
				'concept' => $concept,
				'inesFieldKey' => $inesKey,
				'isCustomField' => in_array($internalKey, $customFields) ? 1 : 0,
				'atmtFieldKey' => $atmtKey,
				'isEcrasable' => in_array($internalKey, $notEcrasableFields) ? 0 : 1
			);
		}

		// Mémorisation en cas d'appel dans la même runtime
		$this->mapping = $mappedFields;

		return $mappedFields;
	}

	/**
	 * Mémorise les clés INES de contact et de société (=client) dans les champs d'un lead (définis par le mapping)
	 *
	 * @param 	Mautic\LeadBundle\Entity\Lead	$lead
	 * @param 	int 							$internalCompanyRef 	Clé INES d'une société
	 * @param 	int 							$internalContactRef 	Clé INES d'un contact
	 *
	 * @return 	Mautic\LeadBundle\Entity\Lead
	 */
	public function setInesKeysToLead($lead, $internalCompanyRef, $internalContactRef)
	{
		$fieldsToUpdate = array();

		// Recherche des champs ATMT choisis pour mémoriser ces clés
		$mapping = $this->getMapping();
		foreach($mapping as $mappingItem) {

			if ($mappingItem['inesFieldKey'] == 'InternalContactRef') {
				$fieldsToUpdate[ $mappingItem['atmtFieldKey'] ] = $internalContactRef;
			}
			if ($mappingItem['inesFieldKey'] == 'InternalCompanyRef') {
				$fieldsToUpdate[ $mappingItem['atmtFieldKey'] ] = $internalCompanyRef;
			}
		}

		// Enregistrement des champs du lead
		$model = $this->factory->getModel('lead.lead');
		$model->setFieldValues($lead, $fieldsToUpdate, true);
		$model->saveEntity($lead);

		return $lead;
	}


	/**
	 * Requêtes SOAP d'un webservice
	 * Si la requête doit contenir un entête, le spécifier dans $settings['soapHeader']
	 *
	 * @param 	string 	$url 			URL complète de la requête SOAP
	 * @param 	array 	$parameters 	Paramètres à transmettre à la méthode $method
	 * @param 	string 	$method 		Méthode à appeler sur l'objet SOAP
	 * @param 	array 	$settings 		Configuration de la requête
	 *
	 * @return 	Object (réponse de l'API)
	 */
	public function makeRequest($url, $parameters = array(), $method = '', $settings = array())
	{
		$client = new \SoapClient($url);

		// Header SOAP, si demandé
		$soapHeader = isset($settings['soapHeader']) ? $settings['soapHeader'] : false;
		if ($soapHeader !== false) {
			$client->__setSoapHeaders(
				new \SoapHeader($soapHeader['namespace'], $soapHeader['name'], $soapHeader['datas'])
			);
		}

		// Appel d'une méthode du web-service, avec ou sans paramètres
		if ( !empty($parameters)) {
			$response = $client->$method($parameters);
		}
		else {
			$response = $client->$method();
		}

		return $response;
	}


	/**
	 * Pour le DEBUG : écrit une ligne dans le log de Mautic
	 *
	 * @param 	Object 	$object
	 */
	public function log($object)
	{
		$this->factory->getLogger()->log('info', var_export($object, true));
	}
}
