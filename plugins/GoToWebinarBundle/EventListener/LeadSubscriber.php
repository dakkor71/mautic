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
	protected $isPluginEnabled;

	/**
	 * Injection de dépendances
	 */
	public function __construct($isPluginEnabled)
	{
		$this->isPluginEnabled = $isPluginEnabled;
	}

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
            /*LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE => array('onListOperatorsGenerate', 0),*/
            LeadEvents::LIST_FILTERS_ON_FILTERING => array('onListFiltering', 0)
        );
    }

	/**
	 * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
		if ( !$this->isPluginEnabled) {
			return;
		}

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
		$webinarModel = $this->factory->getModel('GoToWebinar.Webinar');
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
		if ( !$this->isPluginEnabled) {
			return;
		}

		// Liste des webinaires connus de ATMT ?
		$webinarModel = $event->getFactory()->getModel('GoToWebinar.Webinar');
		$webinarSlugs = $webinarModel->getDistinctWebinarSlugs();
		$webinarSlugs = array_combine($webinarSlugs, $webinarSlugs);

		// Avec l'option vierge uniquement
		$webinarSlugsWithoutAny = array_merge(
			array(
				'-' => '-',
			),
			$webinarSlugs
		);

		// Ajout de l'option vierge ET l'option "n'importe lequel" (any)
		$webinarSlugsWithAny = array_merge(
			array(
				'-' => '-',
				'any' => $event->getTranslator()->trans('plugin.gotowebinar.event.webinar.any')
			),
			$webinarSlugs
		);

		// Filtre : est inscrit à un webinaire
		$event->addChoice('webinar-subscription', array(
			'label' => $event->getTranslator()->trans('plugin.gotowebinar.event.webinar.subscription'),
			'properties' => array(
				'type' => 'select',
				'list' => $webinarSlugsWithAny
			),
			'operators'  => array(
				'include' => array('in', '!in')
			)
		));

		// Filtre : a participé à un webinaire
		$event->addChoice('webinar-participation', array(
			'label' => $event->getTranslator()->trans('plugin.gotowebinar.event.webinar.participation'),
			'properties' => array(
				'type' => 'select',
				'list' => $webinarSlugsWithAny
			),
			'operators'  => array(
				'include' => array('in', '!in')
			)
		));

		// Filtre : est inscrit mais n'a pas participé à un webinaire
		$event->addChoice('webinar-no-participation', array(
			'label' => $event->getTranslator()->trans('plugin.gotowebinar.event.webinar.no.participation'),
			'properties' => array(
				'type' => 'select',
				'list' => $webinarSlugsWithoutAny
			),
			'operators'  => array(
				'include' => array('in')
			)
		));
	}

	/**
	 * Algorithme de filtrage
	 *
	 * @param LeadListFilteringEvent $event
     */
	public function onListFiltering (LeadListFilteringEvent $event)
	{
		if ( !$this->isPluginEnabled) {
			return;
		}

		$details = $event->getDetails();
		$leadId = $event->getLeadId();
		$em = $event->getEntityManager();
		$q = $event->getQueryBuilder();
		$alias = $event->getAlias();
		$func = $event->getFunc();
		$currentFilter = $details['field'];

		$webinarFilters = array('webinar-subscription', 'webinar-participation', 'webinar-no-participation');

		if (in_array($currentFilter, $webinarFilters)) {

			// Table qui contient les inscriptions / participations aux webinaires
			$webinarEventsTable = $em->getClassMetadata('GoToWebinarBundle:WebinarEvent')->getTableName();

			// Webinaires à rechercher (arrav)
			$webinarSlugs = $details['filter'];
			$webinarSlugsForQuery = array_map(function($slug){return "'".$slug."'";}, $webinarSlugs);

			// ou ANY (n'imoporte quel webinaire)
			$isAnyWebinar = in_array('any', $webinarSlugs);

			// Restriction à un seul lead ? => récupération de son email, utile plus loin
			if ( !empty($leadId)) {
				$lead = $em->getRepository('MauticLeadBundle:Lead')->getEntity($leadId);
				$leadEmail = $lead->getEmail();
			}

			// Préparation de sous-sous-requêtes utilisées plus loin pour construire la sous-requête finale
			$subQueriesSQL = array();
			$eventTypes = array('registered', 'participated');
			foreach($eventTypes as $k => $eventType) {

				$query = $em->getConnection()->createQueryBuilder()
							->select('null')
							->from($webinarEventsTable, $alias.$k);

				// Recherche d'une liste de webinaires
				if ( !$isAnyWebinar) {
					$query->where(
						$q->expr()->andX(
							$q->expr()->eq($alias.$k.'.event_type', "'" . $eventType . "'"),
							$q->expr()->in($alias.$k.'.webinar_slug', $webinarSlugsForQuery),
							$q->expr()->eq($alias.$k.'.email', 'l.email')
						)
					);
				// Recherche de n'importe quel webinaire
				} else {
					$query->where(
						$q->expr()->andX(
							$q->expr()->eq($alias.$k.'.event_type', "'" . $eventType . "'"),
							$q->expr()->eq($alias.$k.'.email', 'l.email')
						)
					);
				}

				// Restriction à un seul lead, d'après son email
				if ( !empty($leadId)) {
					$query->andWhere(
						$query->expr()->eq($alias.$k.'.email', $leadEmail)
					);
				}

				$subQueriesSQL[$eventType] = $query->getSQL();
			}

			// Si opérateur "INCLUDING"
			if ($func == 'in') {

				if ($currentFilter == 'webinar-subscription') {
					// Est inscrit à W1 ou W2 ou...
					// C'est à dire : il existe au moins une inscription qui matche
					$subQuery = 'EXISTS (' . $subQueriesSQL['registered'] . ')';
				}
				else  if ($currentFilter == 'webinar-participation') {
					$subQuery = 'EXISTS (' . $subQueriesSQL['participated'] . ')';
				}
				else if ($currentFilter == 'webinar-no-participation') {

					// Nombre d'inscriptions qui matchent
					$queryNbRegistered = $em->getConnection()->createQueryBuilder()
							->select('count(*)')
							->from($webinarEventsTable, $alias.'sub1')
							->where(
								$q->expr()->andX(
									$q->expr()->eq($alias.'sub1.event_type', "'registered'"),
									$q->expr()->in($alias.'sub1.webinar_slug', $webinarSlugsForQuery),
									$q->expr()->eq($alias.'sub1.email', $alias.'.email')
								)
							)->getSQL();

					// Nombre de participations qui matchent
					$queryNbParticipated = $em->getConnection()->createQueryBuilder()
							->select('count(*)')
							->from($webinarEventsTable, $alias.'sub2')
							->where(
								$q->expr()->andX(
									$q->expr()->eq($alias.'sub2.event_type', "'participated'"),
									$q->expr()->in($alias.'sub2.webinar_slug', $webinarSlugsForQuery),
									$q->expr()->eq($alias.'sub2.email', $alias.'.email')
								)
							)->getSQL();

					// Le nombre de participations qui matchent est inférieur strict au nombre d'inscriptins qui matchent
					$subQuery = "((".$queryNbRegistered.") > (".$queryNbParticipated.")) AND ".$alias.".email = l.email";

					// Restriction à un seul lead, d'après son email
					if ( !empty($leadId)) {
						$subQuery .= " AND ".$alias.".email='".$leadEmail."'";
					}

					// Il doit exister au moins une entrée qui répond aux conditions précédentes
					$subQuery = "EXISTS ( SELECT null FROM ".$webinarEventsTable." AS ".$alias." WHERE ( ".$subQuery."))";
				}
			}
			// Si opérateur "EXCLUDING"
			else if ($func == 'notIn') {

				if ($currentFilter == 'webinar-subscription') {
					// N'est inscrit ni à W1, ni à W2, ...
					// C'est à dire : la requête "est inscrit à W1 ou W2, ..." ne retourne aucun résultat
					$subQuery = 'NOT EXISTS (' . $subQueriesSQL['registered'] . ')';
				}
				else if ($currentFilter == 'webinar-participation') {
					$subQuery = 'NOT EXISTS (' . $subQueriesSQL['participated'] . ')';
				};
			}

			$event->setSubQuery($subQuery);
			$event->setFilteringStatus(true);
		}
	}

}
