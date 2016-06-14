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
		$this->webinarModel = $factory->getModel('plugin.GoToWebinar.Webinar');
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
				'notRegisteredInAny' => $this->translator->trans('plugin.gotowebinar.criteria.not_registered'),
				'participatedInAtLeast' => $this->translator->trans('plugin.gotowebinar.criteria.participated'),
				'notParticipatedInAny' => $this->translator->trans('plugin.gotowebinar.criteria.not_participated'),
				'registeredButNotParticipatedInAtLeast' => $this->translator->trans('plugin.gotowebinar.criteria.registered_but_not_participated')
			)
		));

		// Liste des webinaires disponibles
		$webinarSlugs = $this->webinarModel->getDistinctWebcastSlugs();
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
