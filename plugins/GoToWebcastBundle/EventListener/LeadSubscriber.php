<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebcastBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\Event\LeadListFilteringEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class LeadSubscriber
 *
 * Ajoute dans la timeline d'un lead les événements liés aux webcasts
 */
class LeadSubscriber extends CommonSubscriber
{
	protected $isPluginEnabled;

	/**
	 * Injection de dépendances
	 */
	public function __construct(MauticFactory $factory, $isPluginEnabled)
	{
	    parent::__construct($factory);
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

		// Création de l'événement "Inscription à un webcast"
		$eventTypeRegistered = 'webcast.registered';
		$eventTypeRegisteredName = $this->translator->trans('plugin.gotowebcast.timeline.registered');
        $event->addEventType($eventTypeRegistered, $eventTypeRegisteredName);

		// Création de l'événement "Participation à un webcast"
		$eventTypeParticipated = 'webcast.participated';
		$eventTypeParticipatedName = $this->translator->trans('plugin.gotowebcast.timeline.participated');
        $event->addEventType($eventTypeParticipated, $eventTypeParticipatedName);

		// Détection des types d'événements exclus par l'utilisateur
		$isApplicable = array(
			'registered' => $event->isApplicable($eventTypeRegistered),
			'participated' => $event->isApplicable($eventTypeParticipated)
		);

		// Email du lead courant ? (requis pour rechercher les webcasts)
		$leadEmail = $event->getLead()->getEmail();
		if ( empty($leadEmail)) {
			return;
		}

		// Recherche des événements liés aux webcasts
		$webcastModel = $this->factory->getModel('gotowebcast.webcast');
		$webcastEvents = $webcastModel->getEventsByLeadEmail($leadEmail);

		// Ajout de chaque événement à la timeline
		if ( !empty($webcastEvents)) {
			foreach($webcastEvents as $webcastEvent) {

				$eventType = $webcastEvent->getEventType();

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
					'timestamp' => $webcastEvent->getEventDatetime(),
					'extra'     => array(
						'webcastSlug' => $webcastEvent->getWebcastSlug()
					),
					'contentTemplate' => 'GoToWebcastBundle:SubscribedEvents\Timeline:webcast_event.html.php'
				));
			}
		}
	}

	/**
	 * Ajout d'un choix "Webcast" dans la liste des choix
	 *
	 * @param LeadListFiltersChoicesEvent $event
     */
	public function onListChoicesGenerate (LeadListFiltersChoicesEvent $event)
	{
		if ( !$this->isPluginEnabled) {
			return;
		}

		// Liste des webcasts connus de ATMT ?
		$webcastModel = $event->getFactory()->getModel('gotowebcast.webcast');
		$webcastSlugs = $webcastModel->getDistinctWebcastSlugs();
		$webcastSlugs = array_combine($webcastSlugs, $webcastSlugs);

		// Avec l'option vierge uniquement
		$webcastSlugsWithoutAny = array_merge(
			array(
				'-' => '-',
			),
			$webcastSlugs
		);

		// Ajout de l'option vierge ET l'option "n'importe lequel" (any)
		$webcastSlugsWithAny = array_merge(
			array(
				'-' => '-',
				'any' => $event->getTranslator()->trans('plugin.gotowebcast.event.webcast.any')
			),
			$webcastSlugs
		);

		// Filtre : est inscrit à un webcast
		$event->addChoice('webcast-subscription', array(
			'label' => $event->getTranslator()->trans('plugin.gotowebcast.event.webcast.subscription'),
			'properties' => array(
				'type' => 'select',
				'list' => $webcastSlugsWithAny
			),
			'operators'  => array(
				'include' => array('in', '!in')
			)
		));

		// Filtre : a participé à un webcast
		$event->addChoice('webcast-participation', array(
			'label' => $event->getTranslator()->trans('plugin.gotowebcast.event.webcast.participation'),
			'properties' => array(
				'type' => 'select',
				'list' => $webcastSlugsWithAny
			),
			'operators'  => array(
				'include' => array('in', '!in')
			)
		));

		// Filtre : est inscrit mais n'a pas participé à un webcast
		$event->addChoice('webcast-no-participation', array(
			'label' => $event->getTranslator()->trans('plugin.gotowebcast.event.webcast.no.participation'),
			'properties' => array(
				'type' => 'select',
				'list' => $webcastSlugsWithoutAny
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

		$webcastFilters = array('webcast-subscription', 'webcast-participation', 'webcast-no-participation');

		if (in_array($currentFilter, $webcastFilters)) {

			// Table qui contient les inscriptions / participations aux webcasts
			$webcastEventsTable = $em->getClassMetadata('GoToWebcastBundle:WebcastEvent')->getTableName();

			// Webcasts à rechercher (arrav)
			$webcastSlugs = $details['filter'];
			$webcastSlugsForQuery = array_map(function($slug){return "'".$slug."'";}, $webcastSlugs);

			// ou ANY (n'imoporte quel webcast)
			$isAnyWebcast = in_array('any', $webcastSlugs);

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
							->from($webcastEventsTable, $alias.$k);

				// Recherche d'une liste de webcasts
				if ( !$isAnyWebcast) {
					$query->where(
						$q->expr()->andX(
							$q->expr()->eq($alias.$k.'.event_type', "'" . $eventType . "'"),
							$q->expr()->in($alias.$k.'.webcast_slug', $webcastSlugsForQuery),
							$q->expr()->eq($alias.$k.'.email', 'l.email')
						)
					);
				// Recherche de n'importe quel webcast
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

				if ($currentFilter == 'webcast-subscription') {
					// Est inscrit à W1 ou W2 ou...
					// C'est à dire : il existe au moins une inscription qui matche
					$subQuery = 'EXISTS (' . $subQueriesSQL['registered'] . ')';
				}
				else  if ($currentFilter == 'webcast-participation') {
					$subQuery = 'EXISTS (' . $subQueriesSQL['participated'] . ')';
				}
				else if ($currentFilter == 'webcast-no-participation') {

					// Nombre d'inscriptions qui matchent
					$queryNbRegistered = $em->getConnection()->createQueryBuilder()
							->select('count(*)')
							->from($webcastEventsTable, $alias.'sub1')
							->where(
								$q->expr()->andX(
									$q->expr()->eq($alias.'sub1.event_type', "'registered'"),
									$q->expr()->in($alias.'sub1.webcast_slug', $webcastSlugsForQuery),
									$q->expr()->eq($alias.'sub1.email', $alias.'.email')
								)
							)->getSQL();

					// Nombre de participations qui matchent
					$queryNbParticipated = $em->getConnection()->createQueryBuilder()
							->select('count(*)')
							->from($webcastEventsTable, $alias.'sub2')
							->where(
								$q->expr()->andX(
									$q->expr()->eq($alias.'sub2.event_type', "'participated'"),
									$q->expr()->in($alias.'sub2.webcast_slug', $webcastSlugsForQuery),
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
					$subQuery = "EXISTS ( SELECT null FROM ".$webcastEventsTable." AS ".$alias." WHERE ( ".$subQuery."))";
				}
			}
			// Si opérateur "EXCLUDING"
			else if ($func == 'notIn') {

				if ($currentFilter == 'webcast-subscription') {
					// N'est inscrit ni à W1, ni à W2, ...
					// C'est à dire : la requête "est inscrit à W1 ou W2, ..." ne retourne aucun résultat
					$subQuery = 'NOT EXISTS (' . $subQueriesSQL['registered'] . ')';
				}
				else if ($currentFilter == 'webcast-participation') {
					$subQuery = 'NOT EXISTS (' . $subQueriesSQL['participated'] . ')';
				};
			}

			$event->setSubQuery($subQuery);
			$event->setFilteringStatus(true);
		}
	}

}
