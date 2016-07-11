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
 * Class WebinarlistType
 *
 * Formulaire de configuration du champ "Liste des webinaires"
 *
 */
class WebinarlistType extends AbstractType
{
	/**
     * @return void
     */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		// Liste des webinaires : sera hydratée lors de la sauvegarde du formulaire
		$builder->add('webinarlist_serialized', 'hidden');
		
		// Chemin vers un helper nécessaire au rendu du template final, dans les formulaires ATMT
		$builder->add('mautic_field_helper_path', 'hidden');
	}
	
	/**
     * @return string
     */
	public function getName () 
	{
		// Retourne l'alias du formulaire défini dans config.php
		return 'gotowebinar_formtype_webinarlist';
	}
}
