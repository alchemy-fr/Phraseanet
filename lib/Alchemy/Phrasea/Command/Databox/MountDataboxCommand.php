<?php

namespace Alchemy\Phrasea\Command\Databox;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Databox\DataboxConnectionSettings;
use Alchemy\Phrasea\Databox\DataboxService;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MountDataboxCommand extends Command
{

    protected function configure()
    {
        $this->setName('databox:mount')
            ->addArgument('databox', InputArgument::REQUIRED, 'Database name for the databox', null)
            ->addArgument('owner', InputArgument::REQUIRED, 'Email of the databox admin user', null)
            ->addOption('connection', 'c', InputOption::VALUE_NONE, 'Flag to set new database settings')
            ->addOption('db-host', null, InputOption::VALUE_OPTIONAL, 'MySQL server host', 'localhost')
            ->addOption('db-port', null, InputOption::VALUE_OPTIONAL, 'MySQL server port', 3306)
            ->addOption('db-user', null, InputOption::VALUE_OPTIONAL, 'MySQL server user', 'phrasea')
            ->addOption('db-password', null, InputOption::VALUE_OPTIONAL, 'MySQL server password', null);
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $databoxName = $input->getArgument('databox');
        $connectionSettings = $input->getOption('connection') == false ? null : new DataboxConnectionSettings(
            $input->getOption('db-host'),
            $input->getOption('db-port'),
            $input->getOption('db-user'),
            $input->getOption('db-password')
        );

        /** @var UserRepository $userRepository */
        $userRepository = $this->container['repo.users'];
        /** @var DataboxService $databoxService */
        $databoxService = $this->container['databox.service'];

        $owner = $userRepository->findByEmail($input->getArgument('owner'));

        $databoxService->mountDatabox(
            $databoxName,
            $owner,
            $connectionSettings
        );

        $output->writeln('Databox mounted');
    }
}
