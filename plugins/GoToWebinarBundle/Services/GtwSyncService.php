<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
namespace MauticPlugin\GoToWebinarBundle\Services;

/**
 * Class GtwSyncService
 *
 * Synchronise les leads avec les listes d'inscrits et de participants de GoToWebinar
 *
 */
class GtwSyncService
{
	const WEBINAR_REGISTERED_PREFIX = 'webinar_registered_';
	const WEBINAR_PARTICIPATED_PREFIX = 'webinar_participated_';
	
	private $gtwApiService;
	private $leadsService;
	
	/**
	 * Injection de dépendances
	 */
	public function __construct(GtwApiService $gtwApiService, LeadsService $leadsService) 
	{
		$this->gtwApiService = $gtwApiService;
		$this->leadsService = $leadsService;
	}
	
	/**
	 * Synchronise une liste de webinaires, définis par leurs clés 
	 *
	 * @param array( string )
	 * @return array( email => tagsGroups => $tags )
	 */
	public function sync($webinarsKeys)
	{	
		// Recherche des leads qui ne sont pas à jour par rapport à l'API
		// Et pour chacun d'eux la liste des tags à ajouter ou retirer
		$tagsToAddOrRemoveByLead = $this->_getTagsToAddOrRemoveByLead($webinarsKeys);
		
		if ( !empty($tagsToAddOrRemoveByLead)) {
			
			// Application des mises à jour 
			foreach ($tagsToAddOrRemoveByLead as $email => $tagsToAddOrRemove) {
				
				// Existe-il un lead avec l'email obtenu via GTW ?
				// Il peut arriver que non, si l'inscription au webinar s'est faite ailleurs que via un formulaire ATMT
				// Dans ce cas on ignore l'opération car Mautic n'est pas intéressé par ces personnes là
				$lead = $this->leadsService->getLeadByEmail($email);
				
				if ($lead) {
					$this->leadsService->addAndRemoveTagsToLead($lead, $tagsToAddOrRemove['toAdd'], $tagsToAddOrRemove['toRemove']);
				}
			}
		}
		
		return $tagsToAddOrRemoveByLead;
	}
	
	
	/**
	 * Compare les leads taggés comme 'inscrits' et 'ayant participé' 
	 * Avec les listes réelles, issues de l'API GoToWebinar
	 * Retourne les différences, c'est à dire les mises à jour de tags à appliquer aux leads
	 *
	 * @param array(string) $webinarsKeys
	 * @return array( email => array(tagsToRemove, $tagsToAdd) )
	 */
	private function _getTagsToAddOrRemoveByLead($webinarsKeys)
	{
		if ($webinarsKeys) {
			
			$leadsToUpdateByTags = array();
			
			foreach ($webinarsKeys as $webinarKey) {
				
				// Slug du webinaire, de la forme webinarSubject_#webinarKey
				$webinarSlug = $this->gtwApiService->getWebinarSlug($webinarKey);
				
				// Tags associés aux leads inscrits ou ayant participé à un webinar
				$registeredTag = self::WEBINAR_REGISTERED_PREFIX.$webinarSlug;
				$participatedTag = self::WEBINAR_PARTICIPATED_PREFIX.$webinarSlug;
				
				// Liste des leads actuellement taggés
				$leadsRegistered = array_values($this->leadsService->getLeadsByTag($registeredTag));
				$leadsParticipated = array_values($this->leadsService->getLeadsByTag($participatedTag));
				
				// Liste des inscrits réels ? des participants réels ?
				$registrants = $this->gtwApiService->getRegistrants($webinarKey, $onlyEmails=true);
				$attendees = $this->gtwApiService->getAttendees($webinarKey, $onlyEmails=true);
				
				// Liste des leads auxquels il faut ajouter ou retirer des tags
				$leadsToUpdateByTags[] = array(
					$registeredTag => array(
						'toRemove' => array_diff($leadsRegistered, $registrants),
						'toAdd' => array_diff($registrants, $leadsRegistered)
					),
					$participatedTag => array(
						'toRemove' => array_diff($leadsParticipated, $attendees),
						'toAdd' => array_diff($attendees, $leadsParticipated)
					)
				);
			}
			
			// Inversion de la structure de données :
			// On veut, pour chaque lead, la liste des tags à ajouter ou retirer, indépendamment des webinars
			// L'objectif étant de minimiser le nombre de requêtes DB
			$tagsToAddOrRemoveByLead = array();
			foreach ($leadsToUpdateByTags as $tags) {
				foreach ($tags as $tag => $addOrRemove) {
					
					foreach ($addOrRemove['toRemove'] as $lead) {
						if ( !array_key_exists($lead, $tagsToAddOrRemoveByLead)) {
							$tagsToAddOrRemoveByLead[$lead] = array(
								'toRemove' => array(),
								'toAdd' => array()
							);
						}
						$tagsToAddOrRemoveByLead[$lead]['toRemove'][] = $tag;
					}
					
					foreach ($addOrRemove['toAdd'] as $lead) {
						if ( !array_key_exists($lead, $tagsToAddOrRemoveByLead)) {
							$tagsToAddOrRemoveByLead[$lead] = array(
								'toRemove' => array(),
								'toAdd' => array()
							);
						}
						$tagsToAddOrRemoveByLead[$lead]['toAdd'][] = $tag;
					}
				}
			}
			
		}
		
		return $tagsToAddOrRemoveByLead;
	}

	/**
	 * Ajoute un tag à un lead pour indiquer son inscription à un webinaire
	 * Appelé par l'action de formulaire "push lead to webinar"
	 */
	public function registerSingleLeadToWebinar($email, $webinarKey)
	{
		$lead = $this->leadsService->getLeadByEmail($email);
				
		if ($lead) {
			$webinarSlug = $this->gtwApiService->getWebinarSlug($webinarKey);
			$tag = self::WEBINAR_REGISTERED_PREFIX.$webinarSlug;
			$this->leadsService->addAndRemoveTagsToLead($lead, array($tag), array());
		}
	}
	
}

?>