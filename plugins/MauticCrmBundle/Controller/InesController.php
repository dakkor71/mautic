<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;


/**
 * Class InesController
 */
class InesController extends FormController
{
	// Page qui affiche la file d'attente des leads à synchroniser / supprimer avec INES
    public function logsAction()
    {
		$inesSyncLogModel = $this->factory->getModel('crm.ines_sync_log');

		$limit = 200;
		$items = $inesSyncLogModel->getAllEntities($limit);

		return $this->delegateView(array(
			'viewParameters' => array(
				'items' => $items
			),
			'contentTemplate' => 'MauticCrmBundle:Integration\Ines:logs.html.php'
		));
    }
}
