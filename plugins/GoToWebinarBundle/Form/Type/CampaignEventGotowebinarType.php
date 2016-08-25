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
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class CampaignEventGotowebinarType
 */
class CampaignEventGotowebinarType extends AbstractType
{
	protected $translator;

	/**
	 * Injection de dépendances
	 */
	public function __construct(TranslatorInterface $translator, MauticFactory $factory)
	{
		$this->translator = $translator;

		$this->webinarModel = $factory->getModel('gotowebinar.webinar');
	}

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		// Liste des opérandes
		$builder->add('webinar-criteria', 'choice', array(
			'label' => $this->translator->trans('plugin.gotowebinar.decision.criteria'),
			'choices'  => array(
				'registeredInAtLeast' => $this->translator->trans('plugin.gotowebinar.criteria.registered'),
				'participatedInAtLeast' => $this->translator->trans('plugin.gotowebinar.criteria.participated')
			)
		));

		// Liste des webinaires disponibles
		$webinarSlugs = $this->webinarModel->getDistinctWebinarSlugs();
		$choices = array_merge(
			array('ANY' => $this->translator->trans('plugin.gotowebinar.event.webinar.any')),
			array_combine($webinarSlugs, $webinarSlugs)
		);

		$builder->add('webinars', 'choice', array(
			'label' => $this->translator->trans('plugin.gotowebinar.decision.webinars.list'),
			'choices' => $choices,
            'multiple' => true
		));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'gotowebinar_formtype_campaignevent';
    }
}
