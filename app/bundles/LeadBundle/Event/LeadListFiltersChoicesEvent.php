<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class LeadListFiltersChoicesEvent
 *
 * @package Mautic\FieldBundle\Event
 */
class LeadListFiltersChoicesEvent extends CommonEvent
{
	/**
	 * Please refer to ListModel.php, inside getChoiceFields method, for examples of choices
	 * @var array
	 */
	protected $choices;
	
	/**
	 * Please refer to ListModel.php, inside getChoiceFields method, for default operators availabled
	 * @var array
	 */
	protected $operators;
	
	/**
	 * @var Translator
	 */
	protected $translator;
	
	/**
	 * @var MauticFactory
	 */
	protected $factory;
	
    /**
     * @param array $choices
     * @param array $operators
     * @param Translator $translator
     */
    public function __construct($choices, $operators, Translator $translator, MauticFactory $factory)
    {
        $this->choices = $choices;
        $this->operators = $operators;
        $this->translator = $translator;
        $this->factory = $factory;
    }

    /**
     * @return array
     */
    public function getChoices()
    {
        return $this->choices;
    }
	
	/**
     * @return array
     */
    public function getOperators()
    {
        return $this->operators;
    }
	
	/**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }
	
	/**
     * @return MauticFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }
	
	/**
	 * Add a new choice for list filters
	 * Please refer to ListModel.php, inside getChoiceFields method, for examples of choices
	 *
	 * @param string $choiceKey
	 * @param array $choiceConfig
	 */
	public function addChoice($choiceKey, $choiceConfig)
	{
		if ( !array_key_exists($choiceKey, $this->choices)) {
			$this->choices[$choiceKey] = $choiceConfig;
		}
	}
}
