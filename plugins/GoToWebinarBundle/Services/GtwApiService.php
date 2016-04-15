<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
namespace MauticPlugin\GoToWebinarBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class GtwApiService
 *
 * Gère l'API GoToWebinar : connection oAuth, lecture, écriture
 *
 */
class GtwApiService
{
	const API_INIT_OAUTH_BASE_URL = 'https://api.citrixonline.com/oauth/authorize';
	const API_GET_TOKEN_BASE_URL = 'https://api.citrixonline.com/oauth/access_token';
	const API_BASE_URL = 'https://api.citrixonline.com:443/G2W/rest';
	
	private $accessToken;
	private $organizerKey;
	private $translator;
	private $webinars;
	
	/**
	 * Injection des paramètres gotowebinar_access_token et gotowebinar_organizer_key
	 * et du service de traduction
	 */
	public function __construct($accessToken, $organizerKey, $translator) 
	{
		$this->accessToken = $accessToken;
		$this->organizerKey = $organizerKey;
		$this->translator = $translator;
	}
	
	/**
	 * Construit et retourne l'URL permettant d'obtenir un token
	 *
	 * @param string $consumerKey
	 * @return string
	 */
	public function getOauthUrl($consumerKey)
	{
		$baseUrl = self::API_INIT_OAUTH_BASE_URL;
		$query = http_build_query(array(
			'client_id' => $consumerKey
		));
		return $baseUrl . '?' . $query;
	}
	
	/**
	 * Grâce au code à usage unique fourni par Citrix, obtention et stockage du token
	 *
	 * @param string $code
	 * @param string $consumerKey
	 * @return jsonObject
	 */
	public function requestToken($code, $consumerKey)
	{
		$baseUrl = self::API_GET_TOKEN_BASE_URL;
		$query = http_build_query(array(
			'grant_type' => 'authorization_code',
			'code' => $code,
			'client_id' => $consumerKey
		));
		
		// Requête HTTP
		$jsonResult = $this->_curl($baseUrl . '?' . $query);
		
		if (property_exists($jsonResult, 'Error')) {
			$this->_throwError($jsonResult->Error, $jsonResult->ErrorCode);
		}
		
		return $jsonResult;
	}
	
	/**
	 * Teste le bon fonctionnement de l'API et la validité du token
	 *
	 * @return bool
	 */
	public function checkApi()
	{
		try {
			$this->_callAPI('/organizers/' . $this->organizerKey . '/upcomingWebinars');
		}
		catch (BadRequestHttpException $e) {
			return false;
		}
		return true;
	}
	
	/**
     * Retourne la liste des webinaires : tous ou ceux à venir
     *
	 * @param	bool 	$onlyFutures	Ture pour restreindre aux webinaires à venir
	 * @param	bool 	$onlySubjects	[optionnel] Utiliser false pour obtenir les infos détaillées sur les webinaires.
     * @return 	array(string|object)
	 * @throws  BadRequestHttpException
     */
	public function getWebinarList($onlyFutures, $onlySubjects = true) 
	{
		$webinarlist = array();
		
		$action = $onlyFutures ? 'upcomingWebinars' : 'webinars';
		
		$this->webinars = $this->_callAPI('/organizers/' . $this->organizerKey . '/' . $action);
		$this->_fixWebinarsKeys();
		$this->_formatWebinarsTimes();
		
		if (is_array($this->webinars)) {
			if ($onlySubjects) {
				foreach ($this->webinars as $webinar) {
					$webinarlist[$webinar->webinarKey] = $webinar->subject;
				}
			} 
			else {
				$webinarlist = $this->webinars;
			}
		}
		
		return $webinarlist;
	}
	
	/**
	 * Inscrit une personne à un webinaire
	 * @return bool 
	 * @throws  BadRequestHttpException
	 */
	public function subscribeToWebinar($webinarKey, $email, $firstname, $lastname)
	{
		$response = $this->_callAPI(
			'/organizers/' . $this->organizerKey . '/webinars/' . $webinarKey . '/registrants?resendConfirmation=true',
			array(				
				'email' => $email,
				'firstName' => $firstname,
				'lastName' => $lastname
			)
		);
		
		$success = (is_object($response) && property_exists($response, 'status') && $response->status == 'APPROVED');
		return $success;
	}
	
	/**
	 * Retourne la liste des INSCRITS à un webinaire
	 *
	 * @param  string	$webinarKey
	 * @param  bool		$onlyEmails
	 * @return array( jsonObject | string) 
	 * @throws  BadRequestHttpException
	 */
	public function getRegistrants($webinarKey, $onlyEmails=true)
	{
		$response = $this->_callAPI(
			'/organizers/' . $this->organizerKey . '/webinars/' . $webinarKey . '/registrants'
		);
		if ($onlyEmails) {
			$emails = array();
			if (is_array($response)) {
				foreach($response as $registrant) {
					$emails[] = $registrant->email;
				}
			}
			return $emails;
		}
		else {
			return $response;
		}
	}
	
	/**
	 * Retourne la liste des PARTICIPANTS, toutes sessions confondues, à un webinaire
	 *
	 * @param  string	$webinarKey
	 * @param  bool		$onlyEmails
	 * @return array( jsonObject | string) 
	 * @throws  BadRequestHttpException
	 */
	public function getAttendees($webinarKey, $onlyEmails=true)
	{
		$response = $this->_callAPI(
			'/organizers/' . $this->organizerKey . '/webinars/' . $webinarKey . '/attendees'
		);
		if ($onlyEmails) {
			$emails = array();
			if (is_array($response)) {
				foreach($response as $registrant) {
					$emails[] = $registrant->email;
				}
			}
			return $emails;
		}
		else {
			return $response;
		}
	}
	
	/**
	 * Retourne un identifiant unique mais compréhensible (incluant une partie du titre) d'un webinaire
	 *
	 * @param	string	$webinarKey
	 * @return 	string	De la forme : webinarSubject_#webinarKey
	 * @throws  BadRequestHttpException
	 */
	public function getWebinarSlug ($webinarKey) 
	{
		// Lecture du titre du webinaire
		$response = $this->_callAPI(
			'/organizers/' . $this->organizerKey . '/webinars/' . $webinarKey
		);
		$subject = $response->subject;
		
		// Nettoyage du titre pour créer un slug
		$subjectSlug = $this->_getSlugFromString($subject);
		
		return $subjectSlug . '_#' . $webinarKey;
	}
	
	/**
	 * Requête GET ou POST à l'API
	 *
	 * @param  string  $request  Requête relative à la racine de l'API. Exemple : /organizers/{key}/upcomingWebinars
	 * @param  string  $method  'GET'|'POST'  Optionnel : type de requête
	 * @param  array   $datas    Optionnel : Liste des variables à transmettre pour une requête POST
	 * @return array
	 * @throws  BadRequestHttpException
	 */
	private function _callAPI($request, $postDatas = array())
	{
		$jsonResult = $this->_curl(
			self::API_BASE_URL . $request,
			$postDatas,
			array(
				"Content-type: application/json",
				"Accept: application/json",
				"Authorization: OAuth oauth_token=".$this->accessToken
			)
		);
		
		// Erreur retournée par l'API ? (token invalide, ...)
		if (is_object($jsonResult) && property_exists($jsonResult, 'int_err_code')) {
			$this->_throwError($jsonResult->msg, $jsonResult->int_err_code);
		}
		
		// Autre erreur retournée par l'API ? (quota dépassé, ...)
		if (is_object($jsonResult) && property_exists($jsonResult, 'err')) {
			$this->_throwError($jsonResult->message, $jsonResult->err);
		}

		return $jsonResult;
	}
	
	
	/**
	 * Requête HTTP avec CURL, auprès d'un service qui retourne du JSON
	 *
	 * @param string $url
	 * @param array $postDatas
	 * @param array $headers
	 * @return jsonObject
	 * @throw  BadRequestHttpException
	 */
	private function _curl($url, $postDatas = array(), $headers = array())
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		if ( !empty($headers)) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
		
		if ( !empty($postDatas)) {
			curl_setopt($curl, CURLOPT_POST, count($postDatas));
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postDatas));
		}
		
		$rawResult = curl_exec($curl);
		
		// Echec de la requête CURL ?
		if ($rawResult === false) {
			$this->_throwError(curl_error($curl) . ' : ' . curl_errno($curl));
		}
		curl_close($curl);
		
		$jsonResult = json_decode($rawResult);
		
		// Echec du décodage JSON ?
		if ($jsonResult === null) {
			$this->_throwError('Failed to decode JSON response : ' . $rawResult);
		}
		
		return $jsonResult;
	}
	
	/**
	 * Corrige les clés 'webinarKeys', mal décodées sur un système 32 bits
	 * @param  array  $this->webinars  une liste de webinaires telle que retournée par l'API
	 * @return void
	 */
	private function _fixWebinarsKeys()
	{
		if ($this->webinars) {
			foreach($this->webinars as $k => $webinar) {
				$segments = explode('/',$webinar->registrationUrl);
				$this->webinars[$k]->webinarKey = array_pop($segments);
			}
		}
	}
	
	/**
	 * Corrige les clés 'webinarKeys', mal décodées sur un système 32 bits
	 * @param  array  $this->webinars  une liste de webinaires telle que retournée par l'API
	 * @return void
	 */
	private function _formatWebinarsTimes()
	{
		if ($this->webinars) {
			foreach ($this->webinars as $k => $webinar) {
				$timesTxt = array();
				foreach($webinar->times as $timesItem) {
					$timesTxt[] = $this->_timesToString($timesItem);
				}

				$this->webinars[$k]->timesTxt = implode('<br/>', $timesTxt);
			}
		}
	}
	
	/**
	 * Convertit ceci :
	 * @praram object( startTime->"2016-09-30T09:25:00Z", endTime->"2016-09-30T10:30:00Z")
	 * en ceci :
	 * @return string "30.09.2016 de 09:25 à 10:30"
	 */
	private function _timesToString($times)
	{
		$de = ' '.$this->translator->trans('plugin.gotowebinar.from').' ';
		$a = ' '.$this->translator->trans('plugin.gotowebinar.to').' ';
		
		$startTs = strtotime($times->startTime);
		$startDMY = date('d.m.Y', $startTs);
		$startHi = date('H:i', $startTs);
		
		$endTs = strtotime($times->endTime);
		$endDMY = date('d.m.Y', $endTs);
		$endHi = date('H:i', $endTs);
		
		if ($startDMY == $endDMY) {
			$timesTxt = $startDMY . $de . $startHi . $a . $endHi;
		}
		else {
			$timesTxt = $de . $startDMY . ' ' . $startHi . $a . $endDMY . ' ' . $endHi;
		}
		
		return $timesTxt;
	}
	
	/**
	 * Transforme "Une chaîne de caractères !" 
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
			'[GoToWebinar plugin] '.
			$message.
			(( !empty($complement)) ? ' : '.$complement : '')
		);
	}
	
}

?>