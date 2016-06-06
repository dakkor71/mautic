<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
namespace MauticPlugin\GoToWebcastBundle\Model;

use Mautic\CoreBundle\Model\CommonModel;
use Mautic\CoreBundle\Factory\MauticFactory;
use MauticPlugin\GoToWebcastBundle\Entity\WebcastEvent;


/**
 * Class WebcastModel
 */
class WebcastModel extends CommonModel
{
	private $webcastEventRepo;
	
	/** 
	 * Lorsque le MauticFactory instancie pour la première fois ce modèle,
	 * la table liée à l'entité 'WebcastEvent' est créée 
	 */
	public function __construct(MauticFactory $factory)
	{
		parent::__construct($factory);
		$this->_createTableIfNotExists();
		
		$this->webcastEventRepo = $this->em->getRepository('GoToWebcastBundle:WebcastEvent');
	}
	
	/**
	 * Enregistre un événement lié à un webcast
	 *
	 * @param string $email
	 * @param string $webcastSlug
	 * @param string $eventType	 "registered" | "participated"
	 * @param \Datetime	$eventDatetime (option)
	 */
	public function addEvent($email, $webcastSlug, $eventType, $eventDatetime = false) 
	{
		$webcastEvent = new WebcastEvent;
		$webcastEvent->setEmail($email);
		$webcastEvent->setWebcastSlug($webcastSlug);
		$webcastEvent->setEventType($eventType);
		
		// Par défaut, l'événement est défini à l'heure courante
		if ($eventDatetime !== false) {
			$webcastEvent->setEventDatetime($eventDatetime);
		}
		
		// Enregistrement en DB
		$this->em->persist($webcastEvent);
		$this->em->flush();
		
	}
	
	/*
	 * Retourne les événements liés aux webcasts pour le lead donné
	 *
	 * @param	string	$email
	 * @return 	array( WebcastEvent ) 	liste des entités correspondantes
	 */
	public function getEventsByLeadEmail($email)
	{
		// Récupération des entités
		$webcastEvents = $this->webcastEventRepo->findByEmail($email);
		return $webcastEvents;
	}
	
	/*
	 * Retourne les emails des inscrits ou des participants à un webcast
	 *
	 * @param	string	$webcastSlug
	 * @param	string	$eventType		registered | participated
	 * @return 	array( string ) 	emails des inscrits
	 */
	public function getEmailsByWebcast($webcastSlug, $eventType)
	{
		$webcastEvents = $this->webcastEventRepo->findBy(array(
			'webcastSlug' => $webcastSlug,
			'eventType' => $eventType
		));
		
		if ( !empty($webcastEvents)) {
			$emails = array_map(function ($webcastEvent) {
				return $webcastEvent->getEmail();
			}, $webcastEvents);
		}
		else {
			$emails = array();
		}
		
		return $emails;
	}
	
	/*
	 * Retourne la liste des webcasts présents dans au moins une timeline
	 *
	 * @return 	array( string )		Liste des webcastSlug
	 */
	public function getDistinctWebcastSlugs()
	{		
		$query = $this->em->createQuery('SELECT DISTINCT(we.webcastSlug) FROM GoToWebcastBundle:WebcastEvent we');
		$items = $query->getResult();
		return array_map(
			function($item) {return array_pop($item);},
			$items
		);
	}
	
	/**
	 * Mise à jour (ajout et/ou suppression) d'un lot d'emails pour un webcast donné et un type d'événement donné
	 *
	 * @param	string	$webcastSlug
	 * @param	string	$eventType		registered | participated
	 * @param	array( string )	$emailsToAdd	liste des emails à ajouter
	 * @param	array( string )	$emailsToRemove	liste des emails à retirer
	 * @return	void
	 */
	public function batchAddAndRemove($webcastSlug, $eventType, $emailsToAdd = array(), $emailsToRemove = array())
	{
		// Insertion
		if ( !empty($emailsToAdd)) {
			foreach($emailsToAdd as $email) {
				$webcastEvent = new WebcastEvent;
				$webcastEvent->setEmail($email);
				$webcastEvent->setWebcastSlug($webcastSlug);
				$webcastEvent->setEventType($eventType);
				$this->em->persist($webcastEvent);
			}
		}
		
		// Suppression
		if ( !empty($emailsToRemove)) {
			
			$webcastEvents = $this->webcastEventRepo->findBy(array(
				'webcastSlug' => $webcastSlug,
				'eventType' => $eventType,
				'email' => $emailsToRemove
			));
			
			foreach($webcastEvents as $webcastEvent) {
				$this->em->remove($webcastEvent);
			}
		}
		
		// Flush si nécessaire
		if ( !empty($emailsToAdd) || !empty($emailsToRemove)) {
			$this->em->flush();
		}
	}

	/**
	 * Vérifie l'existence de la table qui contient les événements liés à GoToWebcast
	 * et la crée si besoin.
	 * 
     * @param void
	 * @return void
     */
	private function _createTableIfNotExists()
	{
		// Récupération du nom de la table associée à l'entité
		$tableName = $this->em->getClassMetadata('GoToWebcastBundle:WebcastEvent')->getTableName();
		
		// Vérification de son existence en DB
		$schemaManager = $this->em->getConnection()->getSchemaManager();
		if ( !$schemaManager->tablesExist(array($tableName)) == true) {
			
			// Si elle n'existe pas, déclenche une mise à jour du schéma Doctrine via la console
			$kernel = $this->factory->getKernel();
			$application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
			$application->setAutoExit(false);
			$options = array('command' => 'doctrine:schema:update',"--force" => true);
			$application->run(new \Symfony\Component\Console\Input\ArrayInput($options));
		}
	}
}
