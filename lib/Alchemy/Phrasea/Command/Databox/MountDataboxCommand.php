<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2020 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('databox:mount');

        $this->setDescription('Mount databox')
            ->addArgument('databox', InputArgument::REQUIRED, 'Database name in Mysql', null)
            ->addArgument('user_id', InputArgument::REQUIRED, 'The Id of user owner (this account became full admin on this databox)', null)
            ->addOption('db-host', null, InputOption::VALUE_OPTIONAL, 'MySQL server host')
            ->addOption('db-port', null, InputOption::VALUE_OPTIONAL, 'MySQL server port')
            ->addOption('db-user', null, InputOption::VALUE_OPTIONAL, 'MySQL server user')
            ->addOption('db-password', null, InputOption::VALUE_OPTIONAL, 'MySQL server password')
        ;

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        try {

            /** @var UserRepository $userRepository */
            $userRepository = $this->container['repo.users'];

            $owner = $userRepository->find($input->getArgument('user_id'));

            if (empty($owner)) {
                $output->writeln('<error>User not found ! </error>');

                return;
            }

            if ($owner->isGuest() || !$this->container->getAclForUser($owner)->is_admin()) {
                $output->writeln('<error>Admin role is required for the owner ! </error>');

                return;
            }

            $databoxName = $input->getArgument('databox');
            $dialog = $this->getHelperSet()->get('dialog');

            $connectionSettings = new DataboxConnectionSettings(
                $input->getOption('db-host')?:$this->container['conf']->get(['main', 'database', 'host']),
                $input->getOption('db-port')?:$this->container['conf']->get(['main', 'database', 'port']),
                $input->getOption('db-user')?:$this->container['conf']->get(['main', 'database', 'user']),
                $input->getOption('db-password')?:$this->container['conf']->get(['main', 'database', 'password'])
            );

            do {
                $continue = mb_strtolower($dialog->ask($output, '<question> Do you want really mount this databox? (y/N)</question>', 'N'));
            }
            while ( ! in_array($continue, ['y', 'n']));

            if ($continue !== 'y') {
                $output->writeln('Aborting !');

                return;
            }

            /** @var DataboxService $databoxService */
            $databoxService = $this->container['databox.service'];

            \phrasea::clear_sbas_params($this->container);

            $databox = $databoxService->mountDatabox(
                $databoxName,
                $owner,
                $connectionSettings
            );

            $output->writeln("\n\t<info>Data-Box ID ".$databox->get_sbas_id()." mounted successful !</info>\n");
        } catch (\Exception $e) {
            $output->writeln('<error>Mount databox failed :'.$e->getMessage().'</error>');
        }

        return 0;
    }

}
