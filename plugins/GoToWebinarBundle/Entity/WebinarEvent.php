<?php
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebinarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 *
 * @ORM\Table(name="plugin_gotowebinar_events")
 * @ORM\Entity
 *
 */
class WebinarEvent
{
	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(name="email", type="string", length=255)
	 */
	protected $email;

	/**
	 * @ORM\Column(name="webinar_slug", type="string", length=255)
	 */
	protected $webinarSlug;

	/**
	 * @ORM\Column(name="event_datetime", type="datetime")
	 */
	protected $eventDatetime;

	/**
	 * @ORM\Column(name="event_type", type="string", length=31)
	 */
	protected $eventType;

	/**
	 * Constructeur : fixe les valeurs par défaut de l'entité
	 */
	public function __construct()
	{
		$this->email = "undefined";
		$this->webinarSlug = "undefined";
		$this->eventDatetime = new \Datetime;
		$this->eventType = "undefined";
	}

	/**
	 * @param ORM\ClassMetadata $metadata
	 */
	public static function loadMetadata(ORM\ClassMetadata $metadata)
	{
	    $builder = new ClassMetadataBuilder($metadata);

	    $builder->setTable('plugin_gotowebinar_events');

	    $builder->addId();
	    $builder->addNamedField('email', 'string', 'email');
	    $builder->addNamedField('webinarSlug', 'string', 'webinar_slug');
	    $builder->addNamedField('eventDatetime', 'datetime', 'event_datetime');

	    $field = $builder->createField('eventType', 'string')
	    ->columnName('event_type')
	    ->length(31)
	    ->build();
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @param string
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getWebinarSlug()
	{
		return $this->webinarSlug;
	}

	/**
	 * @param string
	 */
	public function setWebinarSlug($webinarSlug)
	{
		$this->webinarSlug = $webinarSlug;
	}

	/**
	 * @return string
	 */
	public function getEventDatetime()
	{
		return $this->eventDatetime;
	}

	/**
	 * @param string
	 */
	public function setEventDatetime(\Datetime $eventDatetime)
	{
		$this->eventDatetime = $eventDatetime;
	}

	/**
	 * @return string
	 */
	public function getEventType()
	{
		return $this->eventType;
	}

	/**
	 * @param string
	 */
	public function setEventType($eventType)
	{
		$this->eventType = $eventType;
	}
}