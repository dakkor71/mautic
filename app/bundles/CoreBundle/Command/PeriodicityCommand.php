<?php

/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\NullOutput;

class PeriodicityCommand extends ModeratedCommand
{

    protected function configure()
    {

        $this->setName('mautic:periodicity:update')
            ->setDescription('Run all periodicity event')
            ->addOption('--id', '-id', InputOption::VALUE_OPTIONAL, 'Id of periodicity', false)
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Force execution even if another process is assumed running.');
            // ->addOption('--batch-limit', '-l', InputOption::VALUE_OPTIONAL, 'Set batch size of leads to process per round. Defaults to 300.', 300)
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();
        $objectId = $options['id'];
        if (!$this->checkRunStatus($input, $output, ($objectId) ? $objectId : 'all')) {
            return 0;
        }

        $container = $this->getContainer();
        $factory = $container->get('mautic.factory');
        $translator = $factory->getTranslator();
        $em = $factory->getEntityManager();
//         $output->writeln('<info>Execute periodicity event </info>');
        $periodicityModel = $factory->getModel('core.periodicity');
        $periodicitys = $periodicityModel->getRepository()->getEntities();

        /**@var \Mautic\CoreBundle\Entity\Periodicity $p */

        foreach ($periodicitys as $p) {
            var_dump($p->nextShoot()->format('H:i:s d/m/Y'));
            if ($p->nextShoot() < new \DateTime()) {

                continue;
            }
            $output->writeln('<info>' . $translator->trans('mautic.core.command.perodicity.update', array('%id%' => $p->getid())) . '</info>');

            $env = $container->get('kernel')->getEnvironment();

            $args = array(
                '--env' => $env,
                '--id' => $p->getTargetId()
            );

            if ($env == 'prod') {
                $args[] = '--no-debug';
            }

            $input       = new ArgvInput($args);
            $application = $this->getApplication()->find('mautic:'.$p->getType());
            $returnCode = $application->run($input, $output);
        }
        $this->completeRun();

        return $returnCode;
    }
}