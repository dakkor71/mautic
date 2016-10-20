<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Entity\lead;

/**
 * Class InesApi
 */
class InesApi extends CrmApi
{

	/**
	 * Force la suppression / mise à jour de l'ID de session du web-service
	 * Utilisé par le bouton "Tester la connexion" de l'onglet de config
	 *
	 * @return	int (id de session)
	 * @throws 	ApiErrorException
	 */
    public function refreshSessionID()
	{
		$this->integration->unsetWebServiceCurrentSessionID();
		$newSessionID = $this->getSessionID();
		return $newSessionID;
	}


	/**
	 * Retourne la liste des champs disponibles chez INES
	 *
	 * @return array
	 */
	public function getLeadFields()
	{
		$inesFields = array();

		$fieldKeys = array('concept', 'inesKey', 'inesLabel', 'isCustomField', 'isMappingRequired', 'autoMapping', 'excludeFromEcrasableConfig');

		/** Champs inclus en dur **/

		// 1 : les champs spéciaux : email, société, contactID et societeID
		$defaultInesFields = array(
			array('contact', 'InternalContactRef', 'Réference contact chez INES', false, true, false, true),
			array('contact', 'InternalCompanyRef', 'Réference société chez INES', false, true, false, true),
			array('contact', 'PrimaryMailAddress', 'E-mail principal', false, true, 'email', true),
			array('client', 'CompanyName', 'Société', false, true, 'company', true)
		);

		// 2 : les champs de contact standards
		foreach(array(
			'LastName' => "Nom",
			'FirstName' => "Prénom",
			'BussinesTelephone' => "Téléphone bureau",
			'HomeTelephone' => "Téléphone domicile",
			'MobilePhone' => "Téléphone mobile",
			'Function' => "Fonction",
			'Service' => "Service",
			'DateOfBirth' => "Date de naissance",
			'Genre' => "Civilité",
			'Language' => "Langue"
		) as $inesKey => $inesLabel) {
			$defaultInesFields[] = array('contact', $inesKey, $inesLabel, false, false, false, false);
		}

		// 3 : les champs de société standards
		foreach(array(
			'Address1' => "Adresse ligne 1",
			'Address2' => "Adresse ligne 2",
			'ZipCode' => "Code postal",
			'City' => "Ville",
			'State' => "Région",
			'Country' => "Pays",
			'Fax' => "Fax",
			'Website' => "Site internet"
		) as $inesKey => $inesLabel) {
			$defaultInesFields[] = array('client', $inesKey, $inesLabel, false, false, false, false);
		}

		foreach($defaultInesFields as $field) {
			$inesFields[] = array_combine($fieldKeys, $field);
		}


		/** Champs custom, obtenus par un appel au WS INES **/

		// Lecture de la config définie chez INES
		$inesConfig = $this->getInesSyncConfig();

		// Ajout des champs customs de type société
		foreach($inesConfig['CompanyCustomFields'] as $companyField) {
			$field = array('client', $companyField['key'], $companyField['label'], true, false, false, false);
			$inesFields[] = array_combine($fieldKeys, $field);
		}

		// Ajout des champs customs de type contact
		foreach($inesConfig['ContactCustomFields'] as $contactField) {
			$field = array('contact', $contactField['key'], $contactField['label'], true, false, false, false);
			$inesFields[] = array_combine($fieldKeys, $field);
		}

		return $inesFields;
	}


	/**
	 * Appelé par le TRIGGER "push lead to integration", en sortie d'un FORM, d'une CAMPAIGN...
	 *
	 * @param 	array 							$mappedData		Ces données ne sont pas utilisées
	 * @param 	Mautic\LeadBundle\Entity\Lead	$lead
	 */
	public function createLead($mappedData, Lead $lead)
	{
		// Un lead n'est synchronisé que s'il possède au minimum un email et une société
		if ( !empty($lead->getEmail()) && !empty($lead->getCompany())) {

			try {
				$this->syncLeadToInes($lead);
			}
			catch (\Exception $e) {}
		}
	}


	/**
	 * Pousse n'importe quel lead, passé en paramètre, vers INES CRM
	 *
	 * @param 	Mautic\LeadBundle\Entity\Lead	$lead
	 *
	 * @return 	bool	Succès ou échec de l'opération
	 */
	public function syncLeadToInes(Lead $lead)
	{
		// Lecture de l'intégralité des champs du lead courant, et du format (int, string, date...) de chaque champ
		$rawFields = $lead->getFields();
		$fieldsValues = array();
		$fieldsTypes = array();
		foreach($rawFields as $fieldGroup => $localFields) {
			foreach($localFields as $fieldKey => $field) {
				$fieldsTypes[$fieldKey] = $field['type'];
				$fieldsValues[$fieldKey] = $field['value'];
			}
		}

		// Application du mapping au lead courant
		// En dissociant les informations du contact et de la société (= client)
		// ainsi que les champs standards et custom (chez INES)
		$mappedDatas = array(
			'contact' => array(
				'standardFields' => array(),
				'customFields' => array()
			),
			'client' => array(
				'standardFields' => array(),
				'customFields' => array()
			)
		);
		$inesProtectedFields = array(
			'contact' => array(),
			'client' => array()
		);
		$mapping = $this->integration->getMapping();
		foreach($mapping as $mappingItem) {

			// Valeur du lead pour le champ courant
			// Si non définie, on ne la mémorise pas dans les données mappées
			$leadValue = $fieldsValues[ $mappingItem['atmtFieldKey'] ];
			if ($leadValue == null) {
				continue;
			}

			// Clé du champ chez INES
			$inesFieldKey = $mappingItem['inesFieldKey'];

			// Concept chez INES à qui est rattaché le champ : contact ou client
			$concept = $mappingItem['concept'];

			// Si le champ n'est pas écrasable chez INES, on le mémorise : sera utile lors de l'UPDATE (voir plus bas)
			if ( !$mappingItem['isEcrasable']) {
				array_push($inesProtectedFields[$concept], $inesFieldKey);
			}

			// Champ de base ou custom (chez INES) ?
			$fieldCategory = ($mappingItem['isCustomField'] == 0) ? 'standardFields' : 'customFields';

			// Mémorisation et classement de la valeur du champ
			$mappedDatas[$concept][$fieldCategory][$inesFieldKey] = $leadValue;
		}


		// Lecture des valeurs des clés INES pour le contact et la société
		// Si c'est un nouveau lead, elles sont inconnues, sinon elles doivent avoir été mémorisées précédemment
		$internalContactRef = isset($mappedDatas['contact']['standardFields']['InternalContactRef']) ? $mappedDatas['contact']['standardFields']['InternalContactRef'] : 0;
		$internalCompanyRef = isset($mappedDatas['contact']['standardFields']['InternalCompanyRef']) ? $mappedDatas['contact']['standardFields']['InternalCompanyRef'] : 0;


		// CREATE
		// Si l'une de ces deux clé est inconnue, on crée la société et le contact chez INES
		if ( !$internalCompanyRef || !$internalContactRef) {

			// On utilise un modèle par défaut pour construire les données à synchroniser
			$datas = $this->getClientWithContactsTemplate();

			// Le champ "Type de société" est imposé par la config définie chez INES
			$inesConfig = $this->getInesSyncConfig();
			$datas['client']['Type'] = $inesConfig['SocieteType'];

			// Puis on hydrate les champs standards liés au concept 'client'
			foreach($mappedDatas['client']['standardFields'] as $inesFieldKey => $fieldValue) {
				$datas['client'][$inesFieldKey] = $fieldValue;
			}

			// Puis les champs standards liés au concept 'contact'
			foreach($mappedDatas['contact']['standardFields'] as $inesFieldKey => $fieldValue) {
				$datas['client']['Contacts']['ContactInfo'][0][$inesFieldKey] = $fieldValue;
			}

			// Requête SOAP : Création chez INES
			$response = $this->request('ws/wsicm.asmx', 'AddClientWithContacts', $datas, true, true);
			if ( !isset($response['AddClientWithContactsResult']['InternalRef'])) {
				return false;
			}

			// et récupération en retour d'une clé contact et client
			$internalCompanyRef = $response['AddClientWithContactsResult']['InternalRef'];
			$internalContactRef = $response['AddClientWithContactsResult']['Contacts']['ContactInfo']['InternalRef'];
			if ( !$internalCompanyRef || !$internalContactRef) {
				return false;
			}

			// Mémorisation dans le lead ATMT des clés contact et client
			$this->integration->setInesKeysToLead($lead, $internalCompanyRef, $internalContactRef);

			// Si un canal de lead a été configuré chez INES, la création du contact doit être suivie par l'écriture d'un lead (au sens INES du terme)
			if ($inesConfig['LeadRef']) {
				// TODO : en attente du WS dédié chez INES
			}
		}

		// UPDATE
		// Si les deux clés sont déjà connues, on met à jour la société et le contact chez INES
		else {

			// Avant tout update, on récupère les données existantes chez INES
			$clientWithContact = array(
				'contact' => $this->getContactFromInes($internalContactRef),
				'client' => $this->getClientFromInes($internalCompanyRef)
			);

			// Si le contact ou le client n'existent plus chez INES, c'est qu'ils ont été supprimés
			// Il faut donc effacer en local les clés connues et recommencer la synchro de ce lead
			if ($clientWithContact['contact'] === false || $clientWithContact['client'] === false) {
				$lead = $this->integration->setInesKeysToLead($lead, 0, 0);
				return $this->syncLeadToInes($lead);
			}

			// Mise à jour du contact, si nécessaire, puis du client, si nécessaire
			foreach($clientWithContact as $concept => $conceptDatas) {

				$updateNeeded = false;
				foreach($mappedDatas[$concept]['standardFields'] as $inesFieldKey => $fieldValue) {

					if ( !isset($conceptDatas[$inesFieldKey])) {
						continue;
					}

					$currentFieldValue = $conceptDatas[$inesFieldKey];

					$isProtectedField = in_array($inesFieldKey, $inesProtectedFields[$concept]);

					// Un champ est mis à jour s'il a changé, et à condition qu'il ne soit pas non-écrasable, sauf s'il est vide chez INES
					if ($currentFieldValue != $fieldValue &&
						( !$isProtectedField || empty($currentFieldValue))
					){
						$conceptDatas[$inesFieldKey] = $fieldValue;
						$updateNeeded = true;
					}
				}

				// Appel du WS seulement si nécessaire
				if ($updateNeeded) {

					$conceptDatas['ModifiedDate'] = date("Y-m-d\TH:i:s");

					$wsDatas = array($concept => $conceptDatas);
					$response = $this->request('ws/wsicm.asmx', 'Update'.ucfirst($concept), $wsDatas, true, true);

					if ( !isset($response['Update'.ucfirst($concept).'Result']['InternalRef'])) {
						return false;
					}
				}
			}
		}


		// Traitement des custom fields INES, s'il y en a
		/*
		TODO
		*/
	}


	/**
	 * Recherche chez INES un contact d'après son ID
	 *
	 * @param 	int 			$internalContactRef
	 *
	 * @return 	array | false
	 */
	public function getContactFromInes($internalContactRef)
	{
		$response = $this->request('ws/wsicm.asmx', 'GetContact', array(
			'reference' => $internalContactRef
		), true, true);

		if (isset($response['GetContactResult']['InternalRef']) &&
			$response['GetContactResult']['InternalRef'] == $internalContactRef
		){
			return $response['GetContactResult'];
		}

		return false;
	}


	/**
	 * Recherche chez INES d'une société (=client) d'après son ID
	 *
	 * @param 	int 			$internalCompanyRef
	 *
	 * @return 	array | false
	 */
	public function getClientFromInes($internalCompanyRef)
	{
		$response = $this->request('ws/wsicm.asmx', 'GetClient', array(
			'reference' => $internalCompanyRef
		), true, true);

		if (isset($response['GetClientResult']['InternalRef']) &&
			$response['GetClientResult']['InternalRef'] == $internalCompanyRef
		){
			return $response['GetClientResult'];
		}

		return false;
	}






	/**
	 * Retourne un ID de session, nécessaire aux requêtes aux web-services
	 * Utilise celui stocké en session PHP s'il existe
	 * Sinon en demande un à INES (via le WS)
	 *
	 * @return	int (id de session)
	 * @throws 	ApiErrorException
	 */
	protected function getSessionID()
	{
		// Si une session existe déjà, on l'utilise
		$sessionID = $this->integration->getWebServiceCurrentSessionID();
		if ( !$sessionID) {

			// Sinon on en demande un
			$args = array(
				'request' => $this->integration->getDecryptedApiKeys()
			);
			$response = $this->request('wslogin/login.asmx', 'authenticationWs', $args, false);

			if (
				is_object($response) &&
				isset($response->authenticationWsResult->codeReturn) &&
				$response->authenticationWsResult->codeReturn == 'ok'
			){
				$sessionID = $response->authenticationWsResult->idSession;

				// Et on le mémorise pour plus tard
				$this->integration->setWebServiceCurrentSessionID($sessionID);
			}
			else {
				throw new ApiErrorException("INES WS : Can't get session ID");
			}
		}

		return $sessionID;
	}


	/**
	 * Lecture de la configuration de la synchro définie dans le CRM INES : champs customs à mapper, canal de leads à utiliser, type de société à utiliser
	 */
	protected function getInesSyncConfig()
	{
		$syncConfig = $this->integration->getCurrentSyncConfig();
		if ( !$syncConfig) {

			// Appel du WS
			$response = $this->request('Ws/WSAutomationSync.asmx', 'GetSyncInfo', array(), true);
			$results = isset($response->GetSyncInfoResult) ? $response->GetSyncInfoResult : false;
			if ($results === false) {
				throw new ApiErrorException("INES WS : Can't get sync config");
			}

			// Canal de lead et type de société
			$syncConfig = array(
				'LeadRef' => isset($infos->LeadRef) ? $infos->LeadRef : 0,
				'SocieteType' => isset($infos->SocieteType) ? $infos->SocieteType : 0
			);

			// Liste des champs de société custom
			$syncConfig['CompanyCustomFields'] = array();
			if (isset($infos->CompanyCustomFields->CustomFieldToAuto)) {
				foreach($infos->CompanyCustomFields->CustomFieldToAuto as $field) {
					$syncConfig['CompanyCustomFields'][] = array(
						'key' => $field->InesName,
						'label' => $field->InesID
					);
				}
			}

			// Liste des champs de contact custom
			$syncConfig['ContactCustomFields'] = array();
			if (isset($infos->ContactCustomFields->CustomFieldToAuto)) {
				foreach($infos->ContactCustomFields->CustomFieldToAuto as $field) {
					$syncConfig['ContactCustomFields'][] = array(
						'key' => $field->InesName,
						'label' => $field->InesID
					);
				}
			}

			// On mémorise la config obtenue pour les appels suivants
			$this->integration->setCurrentSyncConfig($syncConfig);
		}

		return $syncConfig;
	}


	/**
	 * Requête aux web-services INES
	 *
	 * @param 	string	$ws_relative_url	Exemple : wslogin/login.asmx
	 * @param	string	$method				Méthode à appeler sur l'objet SOAP. Exemple : 'authenticationWs'
	 * @param	array 	$args				Paramètres à transmettre à la méthode
	 * @param 	bool 	$auth_needed		Mettre true si un ID de session est requis
	 * @param 	bool	$return_as_array	Mettre true pour convertir l'Object de retour en Array
	 *
	 * @return 	Object 	(réponse de l'API)
	 *
	 * @throws Exception
	 */
    protected function request($ws_relative_url, $method, $args, $auth_needed = true, $return_as_array = false)
	{
		// URL du client
		$client_url = ($auth_needed ? 'https' : 'http') . '://webservices.inescrm.com/';
		$client_url .= ltrim($ws_relative_url, '/') . '?wsdl';

		// Entête SOAP avec un ID de session si cette requête exige une authentification
		if ($auth_needed) {

			$sessionID = $this->getSessionID();

			$settings = array(
				'soapHeader' => array(
					'namespace' => 'http://webservice.ines.fr',
					'name' => 'SessionID',
					'datas' => array('ID' => $sessionID)
				)
			);
		}
		else {
			$settings = false;
		}

		// Appel SOAP au web-service
		try {
			$response = $this->integration->makeRequest($client_url, $args, $method, $settings);
		} catch (\Exception $e) {

			// En cas d'échec d'une requête nécessitant un sessionID, il est possible que celui-ci ait expiré
			// Donc on tente de rafraîchir cet ID avant un 2ème essai
			if ($auth_needed) {
				try {
					$this->refreshSessionID();
					$response = $this->integration->makeRequest($client_url, $args, $method, $settings);
				} catch (\Exception $e) {
					throw $e;
				}
			}
			else {
				throw $e;
			}
		}

		// Conversion en Array si demandé
		if ($return_as_array) {
			$response = json_decode(json_encode($response), true);
		}

		return $response;
	}


	/**
	 * Retourne les paramètres minimaux pour requêter un WS INES utilisant la notion de client
	 *
	 * @return 	Array 	Liste des champs
	 */
	protected function getClientTemplate()
	{
		return array(
			'Confidentiality' => 'Public',
			'CompanyName' => '',
			'CreationDate' => date("Y-m-d\TH:i:s"),
			'ModifiedDate' => date("Y-m-d\TH:i:s"),
			'InternalRef' => 0,
			'Type' => 0,
			'Manager' => 0,
			'SalesResponsable' => 0,
			'TechnicalResponsable' => 0,
			'Origin' => 0,
			'CustomerNumber' => 0,
			'Discount' => 0,
			'HeadQuarter' => 0,
			'Remainder' => 0,
			'MaxRemainder' => 0,
			'Moral' => 0,
			'Folder' => 0,
			'BankReference' => 0,
			'TaxType' => 0,
			'VatTaxValue' => 0,
			'Creator' => 0,
			'Delivery' => 0,
			'Billing' => 0,
			'Service' => "",
			'Address1' => "",
			'Address2' => "",
			'ZipCode' => "",
			'City' => "",
			'State' => "",
			'Country' => "",
			'Phone' => "",
			'Fax' => "",
			'Website' => "",
			'Comments' => "",
			'CompanyTaxCode' => "",
			'VatTax' => "",
			'Bank' => "",
			'BankAccount' => "",
			'PaymentMethod' => "",
			'PaymentMethodRef' => 1, /* OBLIGATOIRE ET NON NUL, SINON ERROR */
			'Language' => "",
			'Activity' => "",
			'AccountingCode' => "",
			'Scoring' => "",
			'Currency' => ""
		);
	}


	/**
	 * Retourne les paramètres minimaux pour requêter un WS INES utilisant la notion de contact
	 *
	 * @return 	Array 	Liste des champs
	 */
	protected function getContactTemplate()
	{
		return array(
			'InternalRef' => 0,
			'LastName' => "",
			'FirstName' => "",
			'BussinesTelephone' => "",
			'HomeTelephone' => "",
			'MobilePhone' => "",
			'Fax' => "",
			'BusinessAddress' => "",
			'HomeAddress' => "",
			'Country' => "",
			'State' => "",
			'City' => "",
			'ZipCode' => "",
			'Comment' => "",
			'Function' => "",
			'Service' => "",
			'CompanyRef' => "",
			'CreationDate' => date("Y-m-d\TH:i:s"),
			'ModificationDate' => date("Y-m-d\TH:i:s"),
			'DateOfBirth' => "0001-01-01T00:00:00",
			'PrimaryMailAddress' => "",
			'SecondaryMailAddress' => "",
			'Rang' => "Principal",
			'Genre' => "",
			'Author' => "",
			'Confidentiality' => "Public",
			'Language' => "",
			'Type' => 0
		);
	}


	/**
	 * Retourne les paramètres minimaux pour requêter un WS INES utilisant la notion de client avec des contact
	 *
	 * @param 	int		$nbContacts			Nombre de contacts à créer pour le client
	 *
	 * @return 	Array 	Liste des champs
	 */
	protected function getClientWithContactsTemplate($nbContacts = 1)
	{
		// Structure pour un client
		$datas = array(
			'client' => $this->getClientTemplate()
		);

		// Préparation de la liste des contacts de ce client
		$datas['client']['Contacts'] = array(
			'ContactInfo' => array()
		);

		// Remplissage du nombre de contact demandés
		$contactTemplate = $this->getContactTemplate();
		for($i=0; $i<$nbContacts; $i++) {
			array_push(
				$datas['client']['Contacts']['ContactInfo'],
				$contactTemplate
			);
		}

		return $datas;
	}
}
