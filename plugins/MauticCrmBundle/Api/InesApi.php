<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Entity\Lead;

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
			'LastName' => "Nom (contact)",
			'FirstName' => "Prénom (contact)",
			'Function' => "Fonction (contact)",
			'BussinesTelephone' => "Téléphone bureau (contact)",
			'HomeTelephone' => "Téléphone domicile (contact)",
			'MobilePhone' => "Téléphone mobile (contact)",
			'BusinessAddress' => "Adresse bureau (contact)",
			'HomeAddress' => "Adresse domicile (contact)",
			'Country' => "Pays (contact)",
			'State' => "Région (contact)",
			'City' => "Ville (contact)",
			'ZipCode' => "Code postal (contact)",
			'Genre' => "Civilité (contact)",
			'Language' => "Langue (contact)",
            'Author' => "Auteur (contact : nombre)",
            'Comment' => "Commentaire (contact)",
            'Confidentiality' => 'Confidentialité (contact)',
            'DateOfBirth' => "Date d'anniversaire (contact)",
            'Fax' => 'Fax (contact)',
            'Rang' => 'Rang (contact)',
            'SecondaryMailAddress' => 'Email secondaire (contact)',
            'Service' => 'Service (contact)',
            'Type' => 'Type (contact : nombre)'
		) as $inesKey => $inesLabel) {
			$defaultInesFields[] = array('contact', $inesKey, $inesLabel, false, false, false, false);
		}

		// 3 : les champs de société INES standards
		foreach(array(
			'Address1' => "Adresse ligne 1 (société)",
			'Address2' => "Adresse ligne 2 (société)",
			'ZipCode' => "Code postal (société)",
			'City' => "Ville (société)",
			'State' => "Région (société)",
			'Country' => "Pays (société)",
			'Phone' => "Téléphone (société)",
			'Website' => "Site internet (société)",
            'Confidentiality' => 'Confidentialité (société)',
            'Service' => 'Service (société)',
            'Comments' => 'Commentaires (société)',
            'Manager' => 'Manager (société : nombre)',
            'SalesResponsable' => 'Responsable des ventes (société : nombre)',
            'TechnicalResponsable' => 'Responsable technique (société : nombre)',
            'Origin' => 'Origine (société : nombre)',
            'CustomerNumber' => 'N° de client (société : nombre)',
            'CompanyTaxCode' => 'Code taxe (société)',
            'VatTax' => 'TVA (société : nombre)',
            'Bank' => 'Banque (société)',
            'BankAccount' => 'Compte bancaire (société)',
            'PaymentMethod' => 'Méthode de paiement (société)',
            'PaymentMethodRef' => 'Référence méthode de paiement (société : nombre)',
            'Discount' => 'Remise (société : nombre)',
            'HeadQuarter' => 'Quartier général (société : nombre)',
            'Language' => 'Langue (société)',
            'Activity' => 'Activité (société)',
            'AccountingCode' => 'Code comptabilité (société)',
            'Scoring' => 'Score (société)',
            'Remainder' => 'Reste (société : nombre)',
            'MaxRemainder' => 'Reste maximum (société : nombre)',
            'Moral' => 'Moral (société : nombre)',
            'Folder' => 'Dossier (société : nombre)',
            'Currency' => 'Monnaie (société)',
            'BankReference' => 'Référence banque (société : nombre)',
            'TaxType' => 'Type de taxe (société : nombre)',
            'VatTaxValue' => 'Valeur TVA (société : nombre)',
            'Creator' => 'Créateur (société : nombre)',
            'Delivery' => 'Livraison (société : nombre)',
            'Billing' => 'Facturation (société : nombre)'
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
		foreach($inesConfig['CompanyCustomFields'] as $companyFieldsGroup) {
			foreach($companyFieldsGroup as $companyField) {
				$field = array('client', $companyField['InesID'], $companyField['InesName'].' (société)', true, false, false, false);
				$inesFields[] = array_combine($fieldKeys, $field);
			}
		}

		// Ajout des champs customs de type contact
		foreach($inesConfig['ContactCustomFields'] as $contactFieldsGroup) {
			foreach($contactFieldsGroup as $contactField) {
				$field = array('contact', $contactField['InesID'], $contactField['InesName'].' (contact)', true, false, false, false);
				$inesFields[] = array_combine($fieldKeys, $field);
			}
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
		$leadId = $lead->getId();
		$company = $this->integration->getLeadMainCompany($leadId);

		// Un lead n'est synchronisé que s'il possède au minimum un email et une société
		if ( !empty($lead->getEmail()) && !empty($company)) {

			try {
				$this->syncLeadToInes($lead);

				// Si un lead est synchronisé par une action directe "push contact to integaration",
				// on le retire d'une éventuelle file d'attente, dédiée aux synchro asynchrones via un CRONJOB
				$this->integration->dequeuePendingLead($lead->getId());
			}
			catch (\Exception $e) {
				$this->integration->logIntegrationError($e);
			}
		}
	}


	/**
	 * Pousse n'importe quel lead, passé en paramètre, vers INES CRM
	 * Optimisé pour enchaîner les appels si nécessaire
	 *
	 * @param 	Mautic\LeadBundle\Entity\Lead	$lead
	 *
	 * @return 	bool	Succès ou échec de l'opération
	 */
	public function syncLeadToInes(Lead $lead)
	{
		$leadId = $lead->getId();
		$leadPoints = $lead->getPoints();
		$company = $this->integration->getLeadMainCompany($leadId, false);
		$dontSyncToInes = $this->integration->getDontSyncFlag($lead);

		if ( !isset($company['companyname']) || empty($company['companyname']) || $dontSyncToInes) {
			return false;
		}

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
		// Structure pour mémoriser les champs non-écrasables
		$inesProtectedFields = array(
			'contact' => array(),
			'client' => array()
		);
		$mapping = $this->integration->getMapping();
		foreach($mapping as $mappingItem) {

			// Valeur du lead pour le champ courant
			// Si non définie, on ne la mémorise pas dans les données mappées
			if ($mappingItem['atmtFieldKey'] != 'company') {
				$leadValue = $fieldsValues[ $mappingItem['atmtFieldKey'] ];
				if ($leadValue == null) {
					continue;
				}
			}
			else {
				$leadValue = $company['companyname'];
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

			// Puis on hydrate les champs standards liés au concept 'client'
			foreach($mappedDatas['client']['standardFields'] as $inesFieldKey => $fieldValue) {
				$datas['client'][$inesFieldKey] = $fieldValue;
			}

			// Puis les champs standards liés au concept 'contact'
			foreach($mappedDatas['contact']['standardFields'] as $inesFieldKey => $fieldValue) {
				$datas['client']['Contacts']['ContactInfoAuto'][0][$inesFieldKey] = $fieldValue;
			}

            // Champs spécifiques, renseignés à part :
            // Le champ "Type de société" est imposé par la config définie chez INES
			$inesConfig = $this->getInesSyncConfig();
			$datas['client']['Type'] = $inesConfig['SocieteType'];
			$datas['client']['Contacts']['ContactInfoAuto'][0]['AutomationRef'] = $leadId; /* référence contact ATMT */
			$datas['client']['Contacts']['ContactInfoAuto'][0]['Scoring'] = $leadPoints; /* scoring ATMT */

			// Requête SOAP : Création chez INES
			$response = $this->request('ws/wsAutomationsync.asmx', 'AddClientWithContacts', $datas, true, true);

			if ( !isset($response['AddClientWithContactsResult']['InternalRef']) ||
				 !isset($response['AddClientWithContactsResult']['Contacts']['ContactInfoAuto']['InternalRef'])
			) {
				return false;
			}

			// et récupération en retour d'une clé contact et client
			$internalCompanyRef = $response['AddClientWithContactsResult']['InternalRef'];
			$internalContactRef = $response['AddClientWithContactsResult']['Contacts']['ContactInfoAuto']['InternalRef'];
			if ( !$internalCompanyRef || !$internalContactRef) {
				return false;
			}

			// Mémorisation dans le lead ATMT des clés contact et client
			$this->integration->setInesKeysToLead($lead, $internalCompanyRef, $internalContactRef);

			// Si un canal de lead a été configuré chez INES, la création du contact doit être suivie par l'écriture d'un lead (au sens INES du terme)
			$inesConfig = $this->getInesSyncConfig();
			if ($inesConfig['LeadRef']) {
				$addLeadResponse = $this->addLeadToInesContact(
					$internalContactRef,
					$internalCompanyRef,
					$datas['client']['Contacts']['ContactInfoAuto'][0]['PrimaryMailAddress'],
					$inesConfig['LeadRef']
				);
				if (!$addLeadResponse) {
					return false;
				}
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

					// Données à transmettre au web-service
					$wsDatas = array($concept => $conceptDatas);

					// Update du concept "client / société"
					if ($concept == 'client') {

						$wsDatas['client']['ModifiedDate'] = date("Y-m-d\TH:i:s");

						// Appel du WS officiel
						$response = $this->request('ws/wsicm.asmx', 'UpdateClient', $wsDatas, true, true);

						if ( !isset($response['UpdateClientResult']['InternalRef'])) {
							return false;
						}
					}
					// Update du concept "contact"
					else {
                        $wsDatas['contact']['ModificationDate'] = date("Y-m-d\TH:i:s");
						$wsDatas['contact']['AutomationRef'] = $leadId;
						$wsDatas['contact']['Scoring'] = $leadPoints;
						$wsDatas['contact']['IsNew'] = false;

						// Filtrage des champs : on ne conserve que ceux demandés par le WS spécifique ATMT
						$contactDatas = $this->getContactTemplate();
						foreach($contactDatas as $key => $value) {
							if (isset($wsDatas['contact'][$key])) {
								$contactDatas[$key] = $wsDatas['contact'][$key];
							}
						}
						$wsDatas['contact'] = $contactDatas;

						// Appel du WS spécifique Automation
						$response = $this->request('ws/wsAutomationsync.asmx', 'UpdateContact', $wsDatas, true, true);

						// En cas de succès, il doit retourner l'identifiant INES du contact
						if ( !isset($response['UpdateContactResult']) || $response['UpdateContactResult'] != $wsDatas['contact']['InternalRef']) {
							return false;
						}
					}
				}
			}
		}

		// Traitement des custom fields INES, s'il y en a
		$concepts = array('contact', 'client');
		foreach($concepts as $concept) {

			if (empty($mappedDatas[$concept]['customFields'])) {
				continue;
			}

			$inesRef = ($concept == 'contact') ? $internalContactRef : $internalCompanyRef;
			$inesRefKey = ($concept == 'contact') ? 'ctRef' : 'clRef';
			$wsConcept = ($concept == 'contact') ? 'Contact' : 'Company';

			// Lecture, via WS, des champs actuels chez INES
			$currentCustomFields = $this->getCurrentCustomFields($concept, $inesRef);

			// Parcours des champs Automation à mettre à jour
			foreach($mappedDatas[$concept]['customFields'] as $inesFieldKey => $fieldValue) {

				$datas = array(
					$inesRefKey => $inesRef,
					'chdefRef' => $inesFieldKey,
					'chpValue' => $fieldValue
				);

				$ws = false;

				// Si le champ n'exite pas encore chez INES : INSERT
				if ( !isset($currentCustomFields[$inesFieldKey])) {
					$ws = 'Insert'.$wsConcept.'CF';
					$datas['chvLies'] = 0;
					$datas['chvGroupeAssoc'] = 0;
				}
				// Si le champ existe déjà et que la valeur a changé : UPDATE
				else if ($currentCustomFields[$inesFieldKey]['chpValue'] != $fieldValue) {

					// La mise à jour est ignorée dans le cas d'un champ non écrasable
					$isProtectedField = in_array($inesFieldKey, $inesProtectedFields[$concept]);
					if ( !$isProtectedField) {
						$ws = 'Update'.$wsConcept.'CF';

						// La mise à jour d'un champ fait référence à son identifiant INES, lu précédemment
						$datas['chpRef'] = $currentCustomFields[$inesFieldKey]['chpRef'];
					}
				}

				if ($ws) {
					// Appel WS pour créer ou mettre à jour un champ custom
					$response = $this->request('ws/wscf.asmx', $ws, $datas, true, true);
					if ( !isset($response[$ws.'Result'])) {
						return false;
					}
				}
			}
		}

		return true;
	}


	/**
	 * Création chez INES d'un weblead lié à un contact
	 *
	 * @param 	int 			$internalContactRef
	 * @param 	int 			$internalCompanyRef
	 * @param 	string 			$email
	 * @param 	int 			$LeadRef	// Canal de lead, défini dans la config INES
	 *
	 * @return 	bool
	 */
	public function addLeadToInesContact($internalContactRef, $internalCompanyRef, $email, $leadRef)
	{
		$response = $this->request('ws/wsAutomationsync.asmx', 'AddLead', array(
			'info' => array(
				'ClRef' => $internalCompanyRef,
				'CtRef' => $internalContactRef,
				'MailExpe' => $email,
				'DescriptionCourte' => "Lead créé par Automation",
				'ReclaDescDetail' => '',
				'FileRef' => $leadRef,
				'CriticiteRef' => 0,
				'TypeRef' => 0,
				'EtatRef' => 0,
				'OrigineRef' => 0,
				'DossierRef' => 0,
				'CampagneRef' => 0,
				'ArticleRef' => 0,
				'ReclaMere' => 0,
				'Propietaire' => 0,
				'Gestionnaire' => 0
			)
		), true, true);

		return isset($response['AddLeadResult']);
	}


    /**
     * Supprime un contact chez INES (en réalité le flag comme "ne plus synchroniser")
     *
     * @param 	int  $inesRef    // Référence INES d'un contact
     *
     * @return 	bool
     */
    public function deleteContact($inesRef)
    {
        $response = $this->request('ws/wsAutomationsync.asmx', 'DeleteAutomationContact', array(
            'InesRef' => $inesRef
        ), true, true);

        return (isset($response['DeleteAutomationContactResult']) && $response['DeleteAutomationContactResult'] == 'Success');
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
	 * Recherche, à partir du mapping des champs, les champs Automation qui correspondent à une liste de champs INES
	 *
	 * @param 	array 	$inesFieldsKeys
	 *
	 * @return 	array 	Liste des identifiants des champs ATMT trouvés
	 */
	public function getAtmtFieldsKeysFromInesFieldsKeys($inesFieldsKeys)
	{
		$atmtFields = array();
		$mapping = $this->integration->getMapping();

		foreach($mapping as $mappingItem) {

			$inesFieldKey = $mappingItem['inesFieldKey'];

			if (in_array($inesFieldKey, $inesFieldsKeys)) {
				$atmtFields[$inesFieldKey] = $mappingItem['atmtFieldKey'];
			}
		}
		return $atmtFields;
	}


	/**
	 * Retourne les champs custom déjà présent chez INES pour un contact ou un client/société
	 *
	 * @param 	array 	$concept 	// contact | client alias company
	 * @param 	array 	$inesRef	// contactID ou clientID
	 *
	 * @return 	array | false
	 */
	public function getCurrentCustomFields($concept, $inesRef)
	{
		$concept = ucfirst($concept);
		if ($concept == 'Client') {
			$concept = 'Company';
		}

		// Appel WS : Lecture des champs
		$response = $this->request('ws/wscf.asmx', 'Get'.$concept.'CF', array('reference' => $inesRef), true, true);

		if ( !isset($response['Get'.$concept.'CFResult']['Values'])) {
			return false;
		}

		$customFields = array();
		$values = $response['Get'.$concept.'CFResult']['Values'];
		if ( !empty($values)) {
			foreach($values as $value_item) {

				// Dans le cas où plusieurs valeurs existent pour un seul champ, on s'intéresse au dernier élément uniquement
				if ( !isset($value_item['DefinitionRef'])) {
					$value_item = end($value_item);
				}

				$chdefRef = $value_item['DefinitionRef'];

				$customFields[$chdefRef] = array(
					'chpRef' => $value_item['Ref'],
					'chpValue' => $value_item['Value']
				);
			}
		}

		return $customFields;
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
				'LeadRef' => isset($results->LeadRef) ? $results->LeadRef : 0,
				'SocieteType' => isset($results->SocieteType) ? $results->SocieteType : 0,
				'CompanyCustomFields' => json_decode(json_encode($results->CompanyCustomFields), true),
				'ContactCustomFields' => json_decode(json_encode($results->ContactCustomFields), true)
			);

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
            'Confidentiality' => 'Undefined',
            'CompanyName' => '',
            'Type' => 0, /* à renseigner d'après config INES : type de société */
            'Service' => '',
            'Address1' => '',
            'Address2' => '',
            'ZipCode' => '',
            'City' => '',
            'State' => '',
            'Country' => '',
            'Phone' => '',
            'Fax' => '',
            'Website' => '',
            'Comments' => '',
            'Manager' => 0,
            'SalesResponsable' => 0,
            'TechnicalResponsable' => 0,
            'CreationDate' => date("Y-m-d\TH:i:s"),
            'ModifiedDate' => date("Y-m-d\TH:i:s"),
            'Origin' => 0,
            'CustomerNumber' => 0,
            'CompanyTaxCode' => '',
            'VatTax' => 0,
            'Bank' => '',
            'BankAccount' => '',
            'PaymentMethod' => '',
            'PaymentMethodRef' => 1, /* OBLIGATOIRE ET NON NUL, SINON ERROR */
            'Discount' => 0,
            'HeadQuarter' => 0,
            'Language' => '',
            'Activity' => '',
            'AccountingCode' => '',
            'Scoring' => '',
            'Remainder' => 0,
            'MaxRemainder' => 0,
            'Moral' => 0,
            'Folder' => 0,
            'Currency' => '',
            'BankReference' => 0,
            'TaxType' => 0,
            'VatTaxValue' => 0,
            'Creator' => 0,
            'Delivery' => 0,
            'Billing' => 0,
            'IsNew' => true,
            'AutomationRef' => 0, /* ne pas renseigner car le concept de société ATMT n'est pas géré */
            'InternalRef' => 0
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
            'Author' => 0,
            'BusinessAddress' => '',
            'BussinesTelephone' => '',
            'City' => '',
            'Comment' => "",
            'CompanyRef' => 0,
            'Confidentiality' => 'Undefined',
            'Country' => '',
            'CreationDate' => date("Y-m-d\TH:i:s"),
            'DateOfBirth' => date("Y-m-d\TH:i:s"),
            'Fax' => '',
            'FirstName' => '',
            'Function' => '',
            'Genre' => '',
            'HomeAddress' => '',
            'HomeTelephone' => '',
            'IsNew' => true,
            'Language' => '',
            'LastName' => '',
            'MobilePhone' => '',
            'ModificationDate' => date("Y-m-d\TH:i:s"),
            'PrimaryMailAddress' => '',
            'Rang' => 'Principal',
            'SecondaryMailAddress' => '',
            'Service' => '',
            'Type' => 0,
            'State' => '',
            'ZipCode' => '',
            'InternalRef' => 0,
            'AutomationRef' => 0,
            'Scoring' => 0
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
			'ContactInfoAuto' => array()
		);

		// Remplissage du nombre de contact demandés
		$contactTemplate = $this->getContactTemplate();
		for($i=0; $i<$nbContacts; $i++) {
			array_push(
				$datas['client']['Contacts']['ContactInfoAuto'],
				$contactTemplate
			);
		}

		return $datas;
	}
}
