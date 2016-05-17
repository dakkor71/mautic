<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
namespace MauticPlugin\GoToWebinarBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\Event\LeadListFilteringEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber
 *
 * Ajoute dans la timeline d'un lead les événements liés aux webinaires
 */
class LeadSubscriber extends CommonSubscriber
{
	
	/**
     * Retourne la liste des événements écoutés
     *
     * @return array
     */
	static public function getSubscribedEvents()
    {
        return array(
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0),
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => array('onListChoicesGenerate', 0),
            LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE => array('onListOperatorsGenerate', 0),
            LeadEvents::LIST_FILTERS_ON_FILTERING => array('onListFiltering', 0)
        );
    }
	
	/**
	 * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
		// Création de l'événement "Inscription à un webinaire"
		$eventTypeRegistered = 'webinar.registered';
		$eventTypeRegisteredName = $this->translator->trans('plugin.gotowebinar.timeline.registered');
        $event->addEventType($eventTypeRegistered, $eventTypeRegisteredName);
	
		// Création de l'événement "Participation à un webinaire"
		$eventTypeParticipated = 'webinar.participated';
		$eventTypeParticipatedName = $this->translator->trans('plugin.gotowebinar.timeline.participated');
        $event->addEventType($eventTypeParticipated, $eventTypeParticipatedName);
		
		// Détection des types d'événements exclus par l'utilisateur
		$isApplicable = array(
			'registered' => $event->isApplicable($eventTypeRegistered),
			'participated' => $event->isApplicable($eventTypeParticipated)
		);
		
		// Email du lead courant ? (requis pour rechercher les webinaires)
		$leadEmail = $event->getLead()->getEmail();		
		if ( empty($leadEmail)) {
			return;
		}
		
		// Recherche des événements liés aux webinaires
		$webinarModel = $this->factory->getModel('plugin.GoToWebinar.Webinar');
		$webinarEvents = $webinarModel->getEventsByLeadEmail($leadEmail);
		
		// Ajout de chaque événement à la timeline
		if ( !empty($webinarEvents)) {
			foreach($webinarEvents as $webinarEvent) {
				
				$eventType = $webinarEvent->getEventType();
				
				if ($eventType == 'registered') {
					$timelineEventType = $eventTypeRegistered;
					$timelineEventLabel = $eventTypeRegisteredName;
				}
				else if ($eventType == 'participated') {
					$timelineEventType = $eventTypeParticipated;
					$timelineEventLabel = $eventTypeParticipatedName;
				}
				else {
					continue;
				}
				
				// Si le type d'event est exclu par l'utilisateur, on passe au suivant
				if ( !$isApplicable[$eventType]) {
					continue;
				}
				
				$event->addEvent(array(
					'event'     => $timelineEventType,
					'eventLabel' => $timelineEventLabel,
					'timestamp' => $webinarEvent->getEventDatetime(),
					'extra'     => array(
						'webinarSlug' => $webinarEvent->getWebinarSlug()
					),
					'contentTemplate' => 'GoToWebinarBundle:SubscribedEvents\Timeline:webinar_event.html.php'
				));
			}
		}
	}

	/**
	 * Ajout d'un choix "Webinaire" dans la liste des choix
	 *
	 * @param LeadListFiltersChoicesEvent $event
     */	
	public function onListChoicesGenerate (LeadListFiltersChoicesEvent $event)
	{
		$choiceKey = 'webinar';
		$choiceLabel = $event->getTranslator()->trans('plugin.gotowebinar.event.webinar');
		
		$webinarModel = $event->getFactory()->getModel('plugin.GoToWebinar.Webinar');
		$webinarSlugs = array_merge(
			array('-'),
			$webinarModel->getDistinctWebinarSlugs()
		);
		$webinarSlugs = array_combine($webinarSlugs, $webinarSlugs);
		
		$event->addChoice($choiceKey, array(
			'label' => $choiceLabel,
			'properties' => array(
				'type' => 'select',
				'list' => $webinarSlugs
			),
			'operators'  => array(
				'include' => array(
					'registered',
					'!registered',
					'participated',
					'!participated',
					'registered but not participated'
				)
			)
		));
	}
	
	/**
	 * Ajout des opérateurs 'registered', 'participated', '!registered', '!participated', 'registered but not participated'
	 *
	 * @param LeadListFiltersOperatorsEvent $event
     */	
	public function onListOperatorsGenerate (LeadListFiltersOperatorsEvent $event)
	{
		$event->addOperator('registered', array(
			'label' => $event->getTranslator()->trans('plugin.gotowebinar.operators.registered'),
			'expr'  => 'registered',
			'negate_expr' => '!registered'
		));
		
		$event->addOperator('!registered', array(
			'label' => $event->getTranslator()->trans('plugin.gotowebinar.operators.not_registered'),
			'expr'  => 'notRegistered',
			'negate_expr' => 'registered'
		));
		
		$event->addOperator('participated', array(
			'label' => $event->getTranslator()->trans('plugin.gotowebinar.operators.participated'),
			'expr'  => 'participated',
			'negate_expr' => '!participated'
		));
		
		$event->addOperator('!participated', array(
			'label' => $event->getTranslator()->trans('plugin.gotowebinar.operators.not_participated'),
			'expr'  => 'notParticipated',
			'negate_expr' => 'participated'
		));
		
		$event->addOperator('registered but not participated', array(
			'label' => $event->getTranslator()->trans('plugin.gotowebinar.operators.registered_but_not_participated'),
			'expr'  => 'registeredButNotParticipated'
		));
	}
	
	/**
	 * Algorithme de filtrage
	 *
	 * @param LeadListFilteringEvent $event
     */	
	public function onListFiltering (LeadListFilteringEvent $event) 
	{
		$details = $event->getDetails();
		$leadId = $event->getLeadId();
		$em = $event->getEntityManager();
		$q = $event->getQueryBuilder();
		$alias = $event->getAlias();
		$func = $event->getFunc();
		
		if ($details['field'] == 'webinar') {
			
			// Specific lead
			if ( !empty($leadId)) {
				$lead = $em->getRepository('MauticLeadBundle:Lead')->getEntity($leadId);
				$leadEmail = $lead->getEmail();
			}
			
			// Pour chaque type d'événement Webinar, construction d'une sous-requête pour filtrer les leads
			$webinarEventsTable = $em->getClassMetadata('GoToWebinarBundle:WebinarEvent')->getTableName();
			$webinarSlug = $details['filter'];
			$subQueriesSQL = array();
			$eventTypes = array('registered', 'participated');
			foreach($eventTypes as $k => $eventType) {

				$query = $em->getConnection()->createQueryBuilder()
							->select('null')
							->from($webinarEventsTable, $alias.$k)
							->where(
								$q->expr()->andX(
									$q->expr()->eq($alias.$k.'.event_type', "'" . $eventType . "'"),
									$q->expr()->eq($alias.$k.'.webinar_slug', "'" . $webinarSlug . "'"),
									$q->expr()->eq($alias.$k.'.email', 'l.email')
								)
							);
							
				// Specific lead
				if ( !empty($leadId)) {
					$query->andWhere(
						$query->expr()->eq($alias.$k.'.email', $leadEmail)
					);
				}
				
				$subQueriesSQL[$eventType] = $query->getSQL();
			}
			
			// Utilisation des sous-requêtes obtenues précédemment pour construire la sous-requête finale
			// en fonction de l'opérande sélectionnée
			switch ($func) {
				case 'registered':
					$subQuery = 'EXISTS (' . $subQueriesSQL['registered'] . ')';
				break;
				case 'notRegistered':
					$subQuery = 'NOT EXISTS (' . $subQueriesSQL['registered'] . ')';
				break;
				case 'participated':
					$subQuery = 'EXISTS (' . $subQueriesSQL['participated'] . ')';
				break;
				case 'notParticipated':
					$subQuery = 'NOT EXISTS (' . $subQueriesSQL['participated'] . ')';
				break;
				case 'registeredButNotParticipated':
					$subQuery1 = 'EXISTS (' . $subQueriesSQL['registered'] . ')';
					$subQuery2 = 'NOT EXISTS (' . $subQueriesSQL['participated'] . ')';
					$subQuery = sprintf('( %s AND %s )', $subQuery1, $subQuery2);
				break;
			}
			
			$event->setSubQuery($subQuery);
			$event->setFilteringStatus(true);
		}
	}
	
}
