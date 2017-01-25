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
		// DEBUG
		// $inesIntegration = $this->factory->getHelper('integration')->getIntegrationObject('Ines');
		// $contact = $inesIntegration->getApiHelper()->getContactFromInes(373);
		// var_dump($contact);
		// die();
		// $inesIntegration->getApiHelper()->test();
		// $leadRepo = $this->factory->getModel('lead.lead')->getRepository();
		// $inesIntegration->getApiHelper()->syncLeadToInes($leadRepo->getEntity(18));
		// die();

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
