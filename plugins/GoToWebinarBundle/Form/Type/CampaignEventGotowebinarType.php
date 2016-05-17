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
		$builder->add('webinar-operand', 'choice', array(
			'label' => $this->translator->trans('plugin.gotowebinar.event.webinar.operand'),
			'choices'  => array(
				'registered' => $this->translator->trans('plugin.gotowebinar.operators.registered'),
				'notRegistered' => $this->translator->trans('plugin.gotowebinar.operators.not_registered'),
				'participated' => $this->translator->trans('plugin.gotowebinar.operators.participated'),
				'notParticipated' => $this->translator->trans('plugin.gotowebinar.operators.not_participated'),
				'registeredButNotParticipated' => $this->translator->trans('plugin.gotowebinar.operators.registered_but_not_participated')
			)
		));
		
		// Liste des webinaires disponibles
		$webinarSlugs = $this->webinarModel->getDistinctWebinarSlugs();
		
		$builder->add('webinar', 'choice', array(
			'label' => $this->translator->trans('plugin.gotowebinar.event.webinar'),
			'choices' => array_combine($webinarSlugs, $webinarSlugs) 
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
