<?php
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
namespace MauticPlugin\GoToWebinarBundle\Model;

use Mautic\CoreBundle\Model\CommonModel;
use Mautic\CoreBundle\Factory\MauticFactory;
use MauticPlugin\GoToWebinarBundle\Entity\WebinarEvent;


/**
 * Class WebinarModel
 */
class WebinarModel extends CommonModel
{
	private $webinarEventRepo;
	
	/** 
	 * Lorsque le MauticFactory instancie pour la première fois ce modèle,
	 * la table liée à l'entité 'WebinarEvent' est créée 
	 */
	public function __construct(MauticFactory $factory)
	{
		parent::__construct($factory);
		$this->_createTableIfNotExists();
		
		$this->webinarEventRepo = $this->em->getRepository('GoToWebinarBundle:WebinarEvent');
	}
	
	/**
	 * Enregistre un événement lié à un webinaire
	 *
	 * @param string $email
	 * @param string $webinarSlug
	 * @param string $eventType	 "registered" | "participated"
	 * @param \Datetime	$eventDatetime (option)
	 */
	public function addEvent($email, $webinarSlug, $eventType, $eventDatetime = false) 
	{
		$webinarEvent = new WebinarEvent;
		$webinarEvent->setEmail($email);
		$webinarEvent->setWebinarSlug($webinarSlug);
		$webinarEvent->setEventType($eventType);
		
		// Par défaut, l'événement est défini à l'heure courante
		if ($eventDatetime !== false) {
			$webinarEvent->setEventDatetime($eventDatetime);
		}
		
		// Enregistrement en DB
		$this->em->persist($webinarEvent);
		$this->em->flush();
		
	}
	
	/*
	 * Retourne les événements liés aux webinaires pour le lead donné
	 *
	 * @param	string	$email
	 * @return 	array( WebinarEvent ) 	liste des entités correspondantes
	 */
	public function getEventsByLeadEmail($email)
	{
		// Récupération des entités
		$webinarEvents = $this->webinarEventRepo->findByEmail($email);
		return $webinarEvents;
	}
	
	/*
	 * Retourne les emails des inscrits ou des participants à un webinaire
	 *
	 * @param	string	$webinarSlug
	 * @param	string	$eventType		registered | participated
	 * @return 	array( string ) 	emails des inscrits
	 */
	public function getEmailsByWebinar($webinarSlug, $eventType)
	{
		$webinarEvents = $this->webinarEventRepo->findBy(array(
			'webinarSlug' => $webinarSlug,
			'eventType' => $eventType
		));
		
		if ( !empty($webinarEvents)) {
			$emails = array_map(function ($webinarEvent) {
				return $webinarEvent->getEmail();
			}, $webinarEvents);
		}
		else {
			$emails = array();
		}
		
		return $emails;
	}
	
	/*
	 * Retourne la liste des webinaires présents dans au moins une timeline
	 *
	 * @return 	array( string )		Liste des webinarSlug
	 */
	public function getDistinctWebinarSlugs()
	{		
		$query = $this->em->createQuery('SELECT DISTINCT(we.webinarSlug) FROM GoToWebinarBundle:WebinarEvent we');
		$items = $query->getResult();
		return array_map(
			function($item) {return array_pop($item);},
			$items
		);
	}
	
	/**
	 * Mise à jour (ajout et/ou suppression) d'un lot d'emails pour un webinaire donné et un type d'événement donné
	 *
	 * @param	string	$webinarSlug
	 * @param	string	$eventType		registered | participated
	 * @param	array( string )	$emailsToAdd	liste des emails à ajouter
	 * @param	array( string )	$emailsToRemove	liste des emails à retirer
	 * @return	void
	 */
	public function batchAddAndRemove($webinarSlug, $eventType, $emailsToAdd = array(), $emailsToRemove = array())
	{
		// Insertion
		if ( !empty($emailsToAdd)) {
			foreach($emailsToAdd as $email) {
				$webinarEvent = new WebinarEvent;
				$webinarEvent->setEmail($email);
				$webinarEvent->setWebinarSlug($webinarSlug);
				$webinarEvent->setEventType($eventType);
				$this->em->persist($webinarEvent);
			}
		}
		
		// Suppression
		if ( !empty($emailsToRemove)) {
			
			$webinarEvents = $this->webinarEventRepo->findBy(array(
				'webinarSlug' => $webinarSlug,
				'eventType' => $eventType,
				'email' => $emailsToRemove
			));
			
			foreach($webinarEvents as $webinarEvent) {
				$this->em->remove($webinarEvent);
			}
		}
		
		// Flush si nécessaire
		if ( !empty($emailsToAdd) || !empty($emailsToRemove)) {
			$this->em->flush();
		}
	}

	/**
	 * Vérifie l'existence de la table qui contient les événements liés à GoToWebinar
	 * et la crée si besoin.
	 * 
     * @param void
	 * @return void
     */
	private function _createTableIfNotExists()
	{
		// Récupération du nom de la table associée à l'entité
		$tableName = $this->em->getClassMetadata('GoToWebinarBundle:WebinarEvent')->getTableName();
		
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
