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

/**
 * Class LeadListFiltersOperatorsEvent
 *
 * @package Mautic\FieldBundle\Event
 */
class LeadListFiltersOperatorsEvent extends CommonEvent
{
	/**
	 * Please refer to LeadListRepository.php, inside getFilterExpressionFunctions method, for examples of operators 
	 * @var array
	 */
	protected $operators;
	
	/**
	 * @var Translator
	 */
	protected $translator;
	
    /**
     * @param array $operators
	 * @param Translator $translator
     */
    public function __construct($operators, Translator $translator)
    {
        $this->operators = $operators;
        $this->translator = $translator;
    }

	/**
     * @return array
     */
    public function getOperators()
    {
        return $this->operators;
    }
	
	/**
     * @return translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

	/**
	 * Add a new operator for list filters
	 * Please refer to LeadListRepository.php, inside getFilterExpressionFunctions method, for examples of operators 
	 *
	 * @param string $operatorKey
	 * @param array $operatorConfig
	 */
	public function addOperator($operatorKey, $operatorConfig)
	{
		if ( !array_key_exists($operatorKey, $this->operators)) {
			$this->operators[$operatorKey] = $operatorConfig;
		}
	}
}
