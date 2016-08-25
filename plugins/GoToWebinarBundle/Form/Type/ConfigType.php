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
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;

/**
 * Formulaire de configuration du plugin
 *
 * Class ConfigType
 */
class ConfigType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('gotowebinar_enable_plugin', 'yesno_button_group', array(
			'choice_list' => new ChoiceList(
				array(false, true),
				array('plugin.gotowebinar.config.disable', 'plugin.gotowebinar.config.enable')
			),
			'label' => ' ',
			'data' => (!isset($options['data']['gotowebinar_enable_plugin'])) ? false : $options['data']['gotowebinar_enable_plugin']
		));

		$builder->add('gotowebinar_consumer_key', 'text', array(
			'label' => 'plugin.gotowebinar.config.consumer_key.label',
			'required' => false,
			'label_attr' => array('class' => 'control-label'),
			'attr' => array(
				'class' => 'form-control'
			)
		));

		$builder->add('oauth_button', 'standalone_button', array(
			'label' => 'plugin.gotowebinar.config.oauth_button.label',
			'required' => false,
			'attr'     => array(
				'class'   => 'oauth btn btn-success'
			)
		));

		$builder->add('gotowebinar_access_token', 'text', array(
			'label' => 'plugin.gotowebinar.config.access_token.label',
			'required' => false,
			'label_attr' => array('class' => 'control-label'),
		    'mapped'=>false,
		    'data'=>!isset($builder->getOptions()['data']['gotowebinar_access_token'])?'':$builder->getOptions()['data']['gotowebinar_access_token'],
			'attr' => array(
				'class' => 'form-control',
				'disabled' => 'disabled'
			)
		));

		$builder->add('gotowebinar_organizer_key', 'text', array(
			'label' => 'plugin.gotowebinar.config.organizer_key.label',
			'required' => false,
			'label_attr' => array('class' => 'control-label'),
		    'mapped'=>false,
		    'data'=>!isset($builder->getOptions()['data']['gotowebinar_organizer_key'])?'':$builder->getOptions()['data']['gotowebinar_organizer_key'],
			'attr' => array(
				'class' => 'form-control',
				'disabled' => 'disabled'
			)
		));

		$builder->add('check_api_button', 'standalone_button', array(
			'label' => 'plugin.gotowebinar.config.check_api_button.label',
			'required' => false,
			'attr'     => array(
				'class'   => 'check-api btn',
			)
		));
	}

	public function getName ()
	{
		return 'gotowebinar_formtype_config';
	}
}

?>
