<?php
namespace App\Command\Setup;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Process\ExecutableFinder;

use App\Core\Configuration\StructureTemplate;


class InstallTest extends Command
{

    protected function configure()
    {
        $this
            ->setName('setup:user')
            ->setDescription('Test User Install.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // DO INSTALL
        $this->getApplication()->getKernel()->getContainer()->get('phraseanet.installer')->installUser();

        $output->writeln("<info>Install successful !</info>");


    }


}