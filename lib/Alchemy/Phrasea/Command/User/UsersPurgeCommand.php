<?php

namespace Alchemy\Phrasea\Command\User;

use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersPurgeCommand  extends Command
{
    /**
     * Constructor
     */
    public function configure()
    {
        $this->setHelp(
            "ex:\n"
            ." --days=\"30\"          : purge users (excluding administrators) not connected since 30 days\n"
            ." -days=\"10\" --login-prefix=\"invite\" : delete \"invite*\" not connected since 10 days\n"
            ." -v --days=\"30\" --dry : list users, do not delete\n"
        );
        $this->setName("users:purge")
            ->setDescription('Purge Sleeping Users')
            ->setHelp('')
            ->addOption('days'  , '', InputOption::VALUE_REQUIRED, 'purge users not connected since the last N days', null)
            ->addOption('dry'   , '', InputOption::VALUE_NONE,     'count users to be purged, do not purge')
            ->addOption('purge-admins', '', InputOption::VALUE_NONE,     'allow purge of administrators')
            ->addOption('login-prefix', '', InputOption::VALUE_OPTIONAL,     'purge only logins that begin with prefix')
            ->addOption('force-30', '', InputOption::VALUE_NONE,     'allow days < 30 without confirm')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        // check some options validity
        $days = (int)($input->getOption('days'));
        $dry  = $input->getOption('dry');
        $purge_admins = $input->getOption('purge-admins');
        $login_prefix = $input->getOption('login-prefix');
        $force_30 = $input->getOption('force-30');

        if ($days <= 0) {
            $output->writeln('<error>--days must be > 0</error>');
            return;
        }

        if ($days < 30 && !$force_30) {
            $dialog = $this->getHelper('dialog');
            if(!$dialog->askConfirmation($output, "Please confirm purge < 30 days <question>[Y/N]</question> : ", false)) {
                return;
            }
        }

        $app = $this->container;

        /** @var UserRepository $userRepository */
        $userRepository = $app['repo.users'];
        $users = $userRepository->findUsersToPurge($days, $login_prefix, $purge_admins);

        $ndel = $nfailed = 0;
        $usersList = [];

        /** @var User $user */
        foreach ($users as $user) {
            $usersList[] = [
                $user->getId(),
                $user->getLogin(),
                $user->getLastConnection()->format('Y-m-d h:m:s')
            ];

            if (!$dry) {
                try {
                    $this->container['manipulator.user']->delete($user);
                    $ndel++;
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                        $output->writeln(sprintf("DELETED\t%d\t%s\t%s", $user['id'], $user['login'], $user['last_connection']));
                    }
                } catch (\Exception $e) {
                    $nfailed++;
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                        $output->writeln(sprintf("FAILURE\t%d\t%s\t%s", $user['id'], $user['login'], $user['last_connection']));
                    }
                }
            } else {
                $ndel++;
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln(sprintf("NOT DELETED (dry-run)\t%d\t%s\t%s", $user['id'], $user['login'], $user['last_connection']));
                }
            }
        }

        if ($dry) {
            $output->writeln(sprintf("%d users NOT deleted because dry-run", $ndel));
            $userTable = $this->getHelperSet()->get('table');
            $headers = ['id', 'login', 'last_connection'];
            $userTable
                ->setHeaders($headers)
                ->setRows($usersList)
                ->render($output);
        } else {
            $output->writeln(sprintf("%d users deleted, %d failed", $ndel, $nfailed));
        }
    }
}
