<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebcastBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
/**
 * @ORM\Table(name="plugin_gotowebcast_events")
 * @ORM\Entity
 */
class WebcastEvent
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
	 * @ORM\Column(name="webcast_slug", type="string", length=255)
	 */
	protected $webcastSlug;

	/**
	 * @ORM\Column(name="event_datetime", type="datetime")
	 */
	protected $eventDatetime;

	/**
	 * @ORM\Column(name="event_type", type="string", length=31)
	 */
	protected $eventType;

	/**
	 * Constructeur : fixe lzes valeurs par défaut de l'entité
	 */
	public function __construct()
	{
		$this->email = "undefined";
		$this->webcastSlug = "undefined";
		$this->eventDatetime = new \Datetime;
		$this->eventType = "undefined";
	}

	public static function loadMetadata(ORM\ClassMetadata $metadata)
	{
	    $builder = new ClassMetadataBuilder($metadata);

	    $builder->setTable('plugin_gotowebcast_events');

	    $builder->addId();
	    $builder->addNamedField('email', 'string', 'email');
	    $builder->addNamedField('webcastSlug', 'string', 'webcast_slug');
	    $builder->addNamedField('eventDatetime', 'datetime', 'event_datetime');

	    $builder->createField('eventType', 'string')
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
	public function getWebcastSlug()
	{
		return $this->webcastSlug;
	}

	/**
	 * @param string
	 */
	public function setWebcastSlug($webcastSlug)
	{
		$this->webcastSlug = $webcastSlug;
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