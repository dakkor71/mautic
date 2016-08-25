<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
namespace MauticPlugin\GoToWebinarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class PushLeadToApiType
 *
 * Formulaire de configuration de l'action de formulaire "Push lead to GoToWebinar"
 *
 */
class PushLeadToApiType extends AbstractType
{
	/**
     * @return void
     */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
	}
	
	/**
     * @return string
     */
	public function getName () 
	{
		// Retourne l'alias du formulaire défini dans config.php
		return 'gotowebinar_formtype_formaction';
	}
}
