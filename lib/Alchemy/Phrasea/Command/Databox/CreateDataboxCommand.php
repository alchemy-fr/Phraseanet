<?php

namespace Alchemy\Phrasea\Command\Databox;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\Configuration\StructureTemplate;
use Alchemy\Phrasea\Databox\DataboxConnectionSettings;
use Alchemy\Phrasea\Databox\DataboxService;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\DialogHelper;

class CreateDataboxCommand extends Command
{

    protected function configure()
    {
        $this->setName('databox:create')
            ->addArgument('databox', InputArgument::REQUIRED, 'Database name for the databox', null)
            ->addArgument('owner', InputArgument::REQUIRED, 'Login of the databox admin user', null)
            ->addOption('connection', 'c', InputOption::VALUE_NONE, 'Flag to set new database settings')
            ->addOption('db-host', null, InputOption::VALUE_OPTIONAL, 'MySQL server host', 'localhost')
            ->addOption('db-port', null, InputOption::VALUE_OPTIONAL, 'MySQL server port', 3306)
            ->addOption('db-user', null, InputOption::VALUE_OPTIONAL, 'MySQL server user', 'phrasea')
            ->addOption('db-password', null, InputOption::VALUE_OPTIONAL, 'MySQL server password', null)
            ->addOption(
                'db-template',
                null,
                InputOption::VALUE_OPTIONAL,
                'Databox template',
                null
            );
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var DialogHelper $dialog */
        $dialog = $this->getHelperSet()->get('dialog');

        $databoxName = $input->getArgument('databox');
        $connectionSettings = $input->getOption('connection') == false ? null : new DataboxConnectionSettings(
            $input->getOption('db-host'),
            $input->getOption('db-port'),
            $input->getOption('db-user'),
            $input->getOption('db-password')
        );

        /** @var UserRepository $userRepository */
        $userRepository = $this->container['repo.users'];

        $owner = $userRepository->findByLogin($input->getArgument('owner'));
        if(!$owner) {
            $output->writeln(sprintf("<error>Unknown user \"%s\"</error>", $input->getArgument('owner')));

            return 1;
        }

        /** @var DataboxService $databoxService */
        $databoxService = $this->container['databox.service'];

        if($databoxService->exists($databoxName, $connectionSettings)) {
            $output->writeln(sprintf("<error>Database \"%s\" already exists</error>", $databoxName));

            return 1;
        }

        /** @var StructureTemplate $templates */
        $templates = $this->container['phraseanet.structure-template'];

        // if a template name is provided, check that this template exists
        $templateName = $input->getOption('db-template');
        if($templateName && !$templates->getByName($templateName)) {
            throw new \Exception_InvalidArgument(sprintf("Databox template \"%s\" not found.", $templateName));
        }
        if(!$templateName) {
            // propose a default template : the first available if "en-simple" does not exists.
            $defaultDBoxTemplate = $templates->getDefault();

            do {
                $templateName = $dialog->ask($output, 'Choose a template from ('.$templates->toString().') for metadata structure <comment>[default: "'.$defaultDBoxTemplate.'"]</comment> : ', $defaultDBoxTemplate);
                if(!$templates->getByName($templateName)){
                    $output->writeln("    <error>Data-Box template : Template not found, try again.</error>");
                }
            }
            while (!$templates->getByName($templateName));
        }

        try {
            $databoxService->createDatabox(
                $databoxName,
                $templateName,
                $owner,
                $connectionSettings
            );
        }
        catch(\Exception $e) {
            $output->writeln(sprintf("<error>Failed to create database \"%s\", error=\"%s\"</error>"
                , $databoxName
                , $e->getMessage()
            ));

            return 1;
        }

        $output->writeln('Databox created');

        return 0;
    }
}
