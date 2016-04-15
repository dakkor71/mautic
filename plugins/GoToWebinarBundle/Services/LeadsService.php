<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
namespace MauticPlugin\GoToWebinarBundle\Services;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;

/**
 * Class LeadsService
 *
 * Manipulation des leads
 */
class LeadsService
{
	private $entityManager;
	private $databaseConnection;
	private $factory;
	private $leadsTable;
	private $leadTagsTable;
	private $leadTagsXrefTable;
	
	/**
	 * Injection de dépendances
	 */
	public function __construct($entityManager, $databaseConnection, $factory) 
	{
		$this->entityManager = $entityManager;
		$this->databaseConnection = $databaseConnection;
		$this->factory = $factory;
		
		// Tables nécessaires aux requêtes en lecture seule
		$this->leadsTable = $this->entityManager->getClassMetadata('MauticLeadBundle:Lead')->getTableName();
		$this->leadTagsTable = $this->entityManager->getClassMetadata('MauticLeadBundle:Tag')->getTableName();
		$this->leadTagsXrefTable = $this->leadTagsTable.'_xref';
	}
	
	/**
	 * Recherche un lead d'après un email
	 *
	 * @param	string	$email
	 * @return 	Mautic\LeadBundle\Entity\Lead
	 */
	public function getLeadByEmail($email)
	{
		$lead = $this->entityManager->getRepository('MauticLeadBundle:Lead')->getLeadByEmail($email);
		return is_array($lead) ? $this->factory->getModel('lead.lead')->getEntity($lead['id']) : false;
	}
	
	/**
	 * Recherche des tags. Ceux qui n'existent pas sont créés.
	 *
	 * @param	array(string)	$tags
	 * @return 	array( tagName => Mautic\LeadBundle\Entity\Tag )
	 */
	/*public function getOrCreateTags($tags, $recursive = false)
	{
		$tagsToReturn = array();
		
		$tagsObjects = $this->entityManager->getRepository('MauticLeadBundle:Tag')->getTagsByName($tags);
		
		foreach ($tagsObjects as $tagObject) {
			$tagName = $tagObject->getTag();
			$tagsToReturn[$tagName] = $tagObject;
		}
		
		if ( !$recursive) {
			$tagsToCreate = array_diff($tags, array_keys($tagsToReturn));
			if ( !empty($tagsToCreate)) {
				foreach ($tagsToCreate as $tagName) {
					$tag = new Tag;
					$tag->setTag($tagName);
					$this->entityManager->persist($tag);
				}
				$this->entityManager->flush();
				
				return $this->getOrCreateTags($tags, true);
			}
		}
		
		return $tagsToReturn;
	}*/
	
	
	/**
	 * Retourne les leads ayant un tag donné
	 *
	 * @param	string	$tag
	 * @return 	array( int:leadID, string:email )
	 */
	public function getLeadsByTag ($tag)
	{
		// Requête directe, en SQL classique
		$query = $this->databaseConnection->prepare(
			"SELECT l.id, l.email FROM $this->leadsTable AS l ".
			"LEFT JOIN $this->leadTagsXrefTable AS tx ON l.id = tx.lead_id ".
			"LEFT JOIN $this->leadTagsTable AS t ON t.id = tx.tag_id ".
			"WHERE l.email IS NOT NULL ".
			"AND t.tag = ?"
		);
		$query->execute(array($tag));
		
		$leads = array();
		while($lead = $query->fetch()) {
			$leads[$lead['id']] = $lead['email'];
		}
		
		return $leads;
	}

	/**
	 * Modifie les tags d'un lead
	 *
	 * @param 	Mautic\LeadBundle\Entity\Lead	$lead
	 * @param 	Mautic\LeadBundle\Entity\Tag	$tag
	 */
	public function addAndRemoveTagsToLead (Lead $lead, $tagsToAdd, $tagsToRemove)
	{
		$tagsToUpdate = $tagsToAdd;
		if ($tagsToRemove) {
			foreach ($tagsToRemove as $tag) {
				$tagsToUpdate[] = '-'.$tag;
			}
		}
		$this->factory->getModel('lead.lead')->modifyTags($lead, $tagsToUpdate);
	}
	
}

?>