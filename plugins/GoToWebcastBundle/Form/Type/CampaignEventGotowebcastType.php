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
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class CampaignEventGotowebcastType
 */
class CampaignEventGotowebcastType extends AbstractType
{
	protected $translator;

	/**
	 * Injection de dépendances
	 */
	public function __construct(TranslatorInterface $translator, MauticFactory $factory)
	{
		$this->translator = $translator;
		$this->webcastModel = $factory->getModel('gotowebcast.webcast');
	}

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		// Liste des opérandes
		$builder->add('webcast-criteria', 'choice', array(
			'label' => $this->translator->trans('plugin.gotowebcast.decision.criteria'),
			'choices'  => array(
				'registeredInAtLeast' => $this->translator->trans('plugin.gotowebcast.criteria.registered'),
				'participatedInAtLeast' => $this->translator->trans('plugin.gotowebcast.criteria.participated')
			)
		));

		// Liste des webcasts disponibles
		$webcastSlugs = $this->webcastModel->getDistinctWebcastSlugs();
		$choices = array_merge(
			array('ANY' => $this->translator->trans('plugin.gotowebcast.event.webcast.any')),
			array_combine($webcastSlugs, $webcastSlugs)
		);

		$builder->add('webcasts', 'choice', array(
			'label' => $this->translator->trans('plugin.gotowebcast.decision.webcasts.list'),
			'choices' => $choices,
            'multiple' => true
		));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'gotowebcast_formtype_campaignevent';
    }
}
