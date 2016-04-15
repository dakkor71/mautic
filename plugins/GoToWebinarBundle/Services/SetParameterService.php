<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
namespace MauticPlugin\GoToWebinarBundle\Services;

use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Configurator\Configurator;

/**
 * Class SetParameterService
 *
 * Permet d'ajouter ou de modifier une valeur dans la configuration du plugin (fichier local.php)
 */
class SetParameterService
{
	private $mauticConfiguratorService;
	private $mauticCacheHelper;
	
	/**
	 * Injection de dépendances
	 */
	public function __construct(Configurator $configurator, CacheHelper $cache) 
	{
		$this->mauticConfiguratorService = $configurator;
		$this->mauticCacheHelper = $cache;
	}
	
	/**
	 * Ecrit une paire (clé, valeur) dans le fichier de configuration
	 */
	public function set($key, $value)
	{
		$configurator = $this->mauticConfiguratorService;
		if ($configurator->isFileWritable()) {
			
			// Mise à jour du fichier de config
			$configurator->mergeParameters(array(
				$key => $value
			));
			$configurator->write();
			
			// Mise à jour du cache
			$this->mauticCacheHelper->clearContainerFile();	
			return true;
		}
		else {
			return false;
		}
	}
}

?>