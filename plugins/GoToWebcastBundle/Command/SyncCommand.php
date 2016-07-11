<?php
/**
 * @package     GoToWebcast
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebcastBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commande CLI : Synchronisation des inscriptions et des participations aux webcasts
 *
 * php app/console gotowebcast:sync [--webcastKey=]
 *
 */
class SyncCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
	protected function configure ()
    {
        $this->setName('gotowebcast:sync')
			 ->setDescription('Synchronisation des inscriptions et des participations aux webcasts')
			 ->addOption('webcastKey', null, InputOption::VALUE_OPTIONAL, 'Si absent, tous les webcasts sont synchronisés.', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute (InputInterface $input, OutputInterface $output)
    {
		$gtwcastSyncService = $this->getContainer()->get('plugin.gotowebcast.service.gtwsync');
		
		try {
			// Si un webcastKey est spécifié en paramètre, on restreint la synchro à celui-ci
			$options = $input->getOptions();
			$webcastKey = $options['webcastKey'];
			if ($webcastKey !== null) {
				$gtwcastSyncService->sync(array($webcastKey));
			}
			else {
				// Sinon, tous les webcasts seront traités
				$gtwcastSyncService->sync();
			}
		}
		catch (BadRequestHttpException $e) {
			echo $e->getMessage() . PHP_EOL;
		}
		
		$output->writeln('Done.');
	}
}