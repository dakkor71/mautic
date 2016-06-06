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
 * Class WebcastlistType
 *
 * Formulaire de configuration du champ "Liste des webcasts"
 *
 */
class WebcastlistType extends AbstractType
{
	/**
     * @return void
     */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		// Liste des webcasts : sera hydratée lors de la sauvegarde du formulaire
		$builder->add('webcastlist_serialized', 'hidden');
		
		// Chemin vers un helper nécessaire au rendu du template final, dans les formulaires ATMT
		$builder->add('mautic_field_helper_path', 'hidden');
	}
	
	/**
     * @return string
     */
	public function getName () 
	{
		// Retourne l'alias du formulaire défini dans config.php
		return 'gotowebcast_formtype_webcastlist';
	}
}
