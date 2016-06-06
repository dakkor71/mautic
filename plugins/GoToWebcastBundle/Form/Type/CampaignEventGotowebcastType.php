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
		$this->webcastModel = $factory->getModel('plugin.GoToWebcast.Webcast');
	}
	
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		// Liste des opérandes
		$builder->add('webcast-operand', 'choice', array(
			'label' => $this->translator->trans('plugin.gotowebcast.event.webcast.operand'),
			'choices'  => array(
				'registered' => $this->translator->trans('plugin.gotowebcast.operators.registered'),
				'notRegistered' => $this->translator->trans('plugin.gotowebcast.operators.not_registered'),
				'participated' => $this->translator->trans('plugin.gotowebcast.operators.participated'),
				'notParticipated' => $this->translator->trans('plugin.gotowebcast.operators.not_participated'),
				'registeredButNotParticipated' => $this->translator->trans('plugin.gotowebcast.operators.registered_but_not_participated')
			)
		));
		
		// Liste des webcasts disponibles
		$webcastSlugs = $this->webcastModel->getDistinctWebcastSlugs();
		
		$builder->add('webcast', 'choice', array(
			'label' => $this->translator->trans('plugin.gotowebcast.event.webcast'),
			'choices' => array_combine($webcastSlugs, $webcastSlugs) 
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
