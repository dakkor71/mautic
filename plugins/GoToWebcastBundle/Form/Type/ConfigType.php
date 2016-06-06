<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebcastBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Formulaire de configuration du plugin
 *
 * Class ConfigType
 */
class ConfigType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{

		$builder->add('gotowebcast_api_username', 'text', array(
			'label' => 'plugin.gotowebcast.config.username.label',
			'required' => false,
			'label_attr' => array('class' => 'control-label'),
			'attr' => array(
				'class' => 'form-control'
			)
		));

		$builder->add('gotowebcast_api_password', 'text', array(
			'label' => 'plugin.gotowebcast.config.password.label',
			'required' => false,
			'label_attr' => array('class' => 'control-label'),
			'attr' => array(
				'class' => 'form-control'
			)
		));

		$builder->add('check_api_button', 'standalone_button', array(
			'label' => 'plugin.gotowebcast.config.check_api_button.label',
			'required' => false,
			'attr'     => array(
				'class'   => 'check-api btn',
			)
		));
	}
	
	public function getName ()
	{
		return 'gotowebcast_formtype_config';
	}
}

?>
