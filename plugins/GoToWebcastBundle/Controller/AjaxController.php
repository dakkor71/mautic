<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebcastBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;


/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{
	
	/**
	 * @return json(success)
	 */
	public function checkApiAction()
	{
		$gtwcastApiService = $this->get('plugin.gotowebcast.service.gtwapi');
		$success = $gtwcastApiService->checkApi();

		return $this->sendJsonResponse(array(
			'success' => $success ? 1 : 0
		));
	}

}

?>
