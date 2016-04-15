<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebinarBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class PageController
 */
class PageController extends CommonController
{

	/**
	 * Page du plugin accessible depuis le menu principal
	 */
	public function indexAction () 
	{
		$gtwApiService = $this->get('plugin.gotowebinar.service.gtwapi');
		
		$viewParameters = array(
			'isApiOk' => true,
			'apiError' => "",
			'webinars' => array()
		);
		
		try {
			$viewParameters['webinars'] = $gtwApiService->getWebinarList($onlyFutures=false, $onlySubjects=false);
		}
		catch(BadRequestHttpException $e) {
			$viewParameters['isApiOk'] = false;
			$viewParameters['apiError'] = $e->getMessage();
		}
		
		return $this->delegateView(array(
			'viewParameters' => $viewParameters,
			'contentTemplate' => 'GoToWebinarBundle:page:index.html.php',
			'passthroughVars' => array(
				'activeLink'    => '#plugin_gotowebinar_menu_index',
				'mauticContent' => 'gtwPageInit',
				'route'         => $this->generateUrl('plugin.gotowebinar.route.main.page')
			)
		));
	}
	
}

?>