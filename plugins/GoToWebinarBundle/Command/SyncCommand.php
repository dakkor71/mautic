<?php
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

namespace MauticPlugin\GoToWebinarBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commande CLI : Synchronisation des inscriptions et des participations aux webinaires
 *
 * php app/console gotowebinar:sync [--webinarKey=]
 *
 */
class SyncCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
	protected function configure ()
    {
        $this->setName('gotowebinar:sync')
			 ->setDescription('Synchronisation des inscriptions et des participations aux webinaires')
			 ->addOption('webinarKey', null, InputOption::VALUE_OPTIONAL, 'Si absent, tous les webinaires sont synchronisés.', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute (InputInterface $input, OutputInterface $output)
    {
		// Si le plugin est désactivé, on stoppe l'exécution
		$isPluginEnabled = $this->getContainer()->get('mautic.factory')->getParameter('gotowebinar_enable_plugin');
		if ($isPluginEnabled !== true) {
			$output->writeln('Plugin is disabled.');
			return;
		}

		$gtwSyncService = $this->getContainer()->get('plugin.gotowebinar.service.gtwsync');

		try {
			// Si un webinarKey est spécifié en paramètre, on restreint la synchro à celui-ci
			$options = $input->getOptions();
			$webinarKey = $options['webinarKey'];
			if ($webinarKey !== null) {
				$gtwSyncService->sync(array($webinarKey));
			}
			else {
				// Sinon, tous les webinaires seront traités
				$gtwSyncService->sync();
			}
		}
		catch (BadRequestHttpException $e) {
			echo $e->getMessage() . PHP_EOL;
		}

		$output->writeln('Done.');
	}
}
