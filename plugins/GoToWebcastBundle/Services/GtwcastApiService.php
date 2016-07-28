<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebcastBundle\Services;

use \SimpleXMLElement;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class GtwcastApiService
 *
 * Gère l'API GoToWebcast : authentification, lecture, écriture
 *
 */
class GtwcastApiService
{
	const API_BASE_URL = 'https://api.webcasts.com/api/';
	const API_REGISTER_BASE_URL = 'https://goto.webcasts.com/viewer/regserver.jsp';

	// Codes d'accès à l'API
	private $apiUsername;
	private $apiPassword;

	// L'authentification à l'API retourne un user et une session temporaires
	private $sessionStatus; /* UNKNOWN, NOK ou OK */
	private $sessionId;
	private $userId;

	private $translator;

	/**
	 * Injection des paramètres gotowebcast_api_username et gotowebcast_api_password
	 * et du service de traduction
	 */
	public function __construct($apiUsername, $apiPassword, $translator)
	{
		$this->apiUsername = $apiUsername;
		$this->apiPassword = $apiPassword;
		$this->sessionStatus = 'UNKNOW';
		$this->translator = $translator;
	}

	/**
	 * Teste le bon fonctionnement de l'API
	 *
	 * @return bool
	 */
	public function checkApi()
	{
		$this->_auth(true);
		return ($this->_getAuthStatus() == 'OK');
	}

	/**
	 * Retourne la liste des webcasts : tous ou ceux à venir
	 *
	 * @param	bool 	$onlyFutures	True pour restreindre aux webcasts à venir
	 * @return 	array( $eventId(int) => $eventTitle(string) )
	 * @throws  BadRequestHttpException
	 */
	public function getWebcastList($onlyFutures)
	{
		$url = '/event/allevents/core?si=SESSION_ID&ui=USER_ID';
		$xml = $this->_callAPI($url);

		$nbEvents = count($xml->result->success->event_list->core_event->event_info);
		$currentDate = gmdate('Y-m-d H:i:s');
		$webcastlist = array();
		for($i=0; $i < $nbEvents; $i++) {

			// Restriction aux webcasts futurs, si demandé
			if ($onlyFutures) {
				$eventEnding = (string)$xml->result->success->event_list->core_event->event_info[$i]['sched_end'];
				if ($eventEnding < $currentDate) {
					continue;
				}
			}

			$eventId = (int)$xml->result->success->event_list->core_event->event_info[$i]['id'];
			$eventTitle = (string)$xml->result->success->event_list->core_event->event_info[$i];
			$webcastlist[$eventId] = $eventTitle;
		}

		return $webcastlist;
	}

	/**
	 * Inscrit une personne à un webcast
	 * @return bool
	 * @throws  BadRequestHttpException
	 */
	public function subscribeToWebcast($webcastKey, $email, $firstname, $lastname)
	{
 		$url = rtrim(self::API_REGISTER_BASE_URL, '/') . '?ei=' . $webcastKey . '&email=' . $email;
		if ( !empty($firstname)) {
			$url .= "&fname=" . rawurlencode($firstname);
		}
		if ( !empty($lastname)) {
			$url .= "&lname=" . rawurlencode($lastname);
		}
		$url .= '&pass=citr003';
		$url .= '&tp_regconfemail=1';

		// Appel d'une API dédiée à l'inscription des membres
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$rawResult = curl_exec($curl);

		if ($rawResult === false) {
			return false;
		}

		// Si l'API retourne une de ces valeurs, l'inscription est bien prise en compte
		$successMessages = array("SUCCESS!", "User already registered.");
		return in_array(trim($rawResult), $successMessages);
	}

	/**
	 * Retourne un identifiant unique mais compréhensible (incluant une partie du titre) d'un webcast
	 *
	 * @param	string	$webcastKey
	 * @param	string	$webcastTitle 	[Optionnel] S'il est connu, fournir le titre du Webcats, cela évite un appel API
	 * @return 	string	De la forme : webcastSubject_#webcastKey
	 * @throws  BadRequestHttpException
	 */
	public function getWebcastSlug ($webcastKey, $webcastTitle = false)
	{
		if ( !$webcastTitle) {

			// Lecture du titre du webcast
			$xml = $this->_callAPI('/reporting/audience_reports?ui=USER_ID&si=SESSION_ID&ei=' . $webcastKey);
			$webcastTitle = (string)$xml->result->success->eventtitle;
		}

		// Nettoyage du titre pour créer un slug
		$subjectSlug = $this->_getSlugFromString($webcastTitle);

		return $subjectSlug . '_#' . $webcastKey;
	}

	/**
	 * Retourne la liste des emails des INSCRITS et des PARTICIPANTS à un webcast, ainsi que le slug du webcast
	 *
	 * @param  string	$webcastKey
	 * @return array( array($email(string)) , array($email(string)) , $webcastSlug(string) )
	 * @throws  BadRequestHttpException
	 */
	public function getRegistrantsAndAttendees($webcastKey)
 	{
		// Appel API : toutes les infos concernant le webcast demandé
		$xml = $this->_callAPI('/reporting/audience_reports?ui=USER_ID&si=SESSION_ID&ei=' . $webcastKey);

		// Slug du webcast ?
		$webcastTitle = (string)$xml->result->success->eventtitle;
		$webcastSlug = $this->getWebcastSlug($webcastKey, $webcastTitle);

		$nbSubscriptions = count($xml->result->success->attendee_list->attendee);
		$registrants = array();
		$attendees = array();
		for ($i=0; $i<$nbSubscriptions; $i++) {

			// Un inscrit
			$item = $xml->result->success->attendee_list->attendee[$i];
			$email = (string)$item['email'];
			$registrants[] = $email;

			// Ajout à la liste des participants s'il y a eu au moins une session
			if ( !empty($item->session->live_sessions_list) || !empty($item->session->od_sessions_list)) {
				$attendees[] = $email;
			}
		}

		return array($registrants, $attendees, $webcastSlug);
	}




	/**
	 * Tente se s'authentifier à l'API
	 * Puis met à jour le status de la connexion, la sessionid et le userid temporaires
	 *
	 * @param 	bool	$force 	[Optionnel] indiquer TRUE pour forcer la vérification
	 * @return 	void
	 */
	private function _auth($force = false)
	{
		if ($this->sessionStatus != 'OK' || $force) {

			$request = '/login?username=' . $this->apiUsername . '&password=' . $this->apiPassword;

			$xml = $this->_callAPI($request, false);

			if ((int)$xml->result->returnCode == 1) {
				$this->sessionStatus = 'OK';
				$this->sessionId = (string)$xml->result->success->sessionid->value;
				$this->userId = (string)$xml->result->success->userid->value;
			}
			else {
				$this->sessionStatus = 'NOK';
			}
		}
	}

	/**
	 * Retourne l'état de la connexion à l'API : inconnu, ok ou pas ok ?
	 *
	 * @param void
	 * @return string 'UNKNOWN' | 'NOK' | 'OK'
	 */
	private function _getAuthStatus()
	{
		return $this->sessionStatus;
	}

	/**
	 * Requête GET ou POST à l'API
	 *
	 * @param  	string  $request  Requête relative à la racine de l'API. Utiliser les tags SESSION_ID et USER_ID pour faire références à ces données
	 * @param 	bool 	$checkAuth	[Optionnel] Indiquer FALSE pour ne pas vérifier l'authentification ni le code de retour. (true par défaut)
	 * @return 	array
	 * @throws  BadRequestHttpException
	 */
	private function _callAPI($request, $checkAuth = true)
	{
		// Authentification, si nécessaire et si demandé
		if ($checkAuth) {
			$this->_auth();
			if ($this->_getAuthStatus() != 'OK') {
				$this->_throwError('Unable to connect to Webcast API');
			}
		}

		$url = rtrim(self::API_BASE_URL, '/') . '/' . ltrim($request, '/');

		// Inclusion si nécessaire de la session et du user dans la requête
		$url = str_replace(
			array('SESSION_ID', 'USER_ID'),
			array($this->sessionId, $this->userId),
			$url
		);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$rawResult = curl_exec($curl);

		// Echec de la requête CURL ?
		if ($rawResult === false) {
			$this->_throwError(curl_error($curl) . ' : ' . curl_errno($curl));
		}
		curl_close($curl);

		// Décodage de la réponse XML
		try {
			$xmlResult = new SimpleXMLElement($rawResult);
		} catch (BadRequestHttpException $e){
			$this->_throwError('Failed to decode XML response : ' . $e->getMessage());
		}

		// La réponse de l'API doit être de 1
		if ($checkAuth) {
			$returnCode = (int)$xmlResult->result->returnCode;
			if ($returnCode != 1) {
				$this->_throwError('API error, return code is : ' . $returnCode);
			}
		}

		return $xmlResult;
	}

	/**
	 * Crée un slug normalisé à partir d'une chaine de caractères
	 *
	 * @param string 	$str
	 * @param int 		$limit	longueur maxi du slug
	 * @return string
	 */
	private function _getSlugFromString($str, $limit = 20)
	{
		// Première étape : suppression des accents
		$str = htmlentities(strtolower($str), ENT_NOQUOTES, 'utf-8');
		$str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
		$str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
		$str = preg_replace('#&[^;]+;#', '', $str);

		// Deuxième étape : restriction à un alphabet donné
		$availableChars = explode(' ', "0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o p q r s t u v w x y z");
		$safeStr = '';
		$safeChar = '';
		for($i=0; $i < strlen($str); $i++) {
			$char = substr($str,$i,1);
			if ( ! in_array($char, $availableChars)) {
				if ($safeChar != '-') {
					$safeChar = '-';
				}
				else {
					continue;
				}
			} else {
				$safeChar = $char;
			}
			$safeStr .= $safeChar;
		}

		return trim(substr($safeStr, 0, $limit), '-');
	}

	/**
	 * @throw  BadRequestHttpException
	 */
	private function _throwError($message, $complement = '')
	{
		throw new BadRequestHttpException(
			'[GoToWebcast plugin] '.
			$message.
			(( !empty($complement)) ? ' : '.$complement : '')
		);
	}


}

?>
