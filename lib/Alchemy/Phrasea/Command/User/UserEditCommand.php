<?php

namespace Alchemy\Phrasea\Command\User;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserEditCommand extends Command
{
    public function __construct()
    {
        parent::__construct('user:edit');

        $this->setDescription('Edit user in Phraseanet')
            ->addOption('user_id', null, InputOption::VALUE_REQUIRED, 'The id of user.')
            ->addOption('generatepassword', null, InputOption::VALUE_NONE, 'Generate and set with a random value')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Set the user password to the input value')
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'the user login need to be unique')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'the user email, do not send notification about update change')
            ->addOption('mailLock', null, InputOption::VALUE_REQUIRED, 'lock the email , true/false')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answer yes to all questions')
            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        /** @var UserRepository $userRepository */
        $userRepository     = $this->container['repo.users'];
        /** @var UserManipulator $userManipulator */
        $userManipulator    = $this->container['manipulator.user'];

        $userId     = $input->getOption('user_id');
        $login      = $input->getOption('login');
        $email      = $input->getOption('email');
        $mailLock   = $input->getOption('mailLock');
        $password   = $input->getOption('password');
        $generatePassword   = $input->getOption('generatepassword');
        $yes        = $input->getOption('yes');


        if (empty($userId)) {
            $output->writeln('<info>Give the user_id to edit : example --user_id=9999</info>');

            return 0;
        }

        $user = $userRepository->find($userId);

        if ($user === null) {
            $output->writeln('<error>Not found User.</error>');

            return 0;
        }

        if (!empty($password) && !empty($generatePassword)) {
            $output->writeln('<info>Choose only one option to set a password!</info>');

            return 0;
        }

        if ($login) {
            try {
                $userManipulator->setLogin($user, $login);
                $output->writeln('<info>User Login successfully changed !</info>');
            } catch(\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }

        if ($email) {
            try {
                $userManipulator->setEmail($user, $email);
                $output->writeln('<info>User Email successfully changed !</info>');
            } catch(\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }

        if ($mailLock) {
            if (in_array($mailLock, ['true', 'false'])) {
                $mailLock = ($mailLock == 'true') ? true : false;
                $user->setMailLocked($mailLock);
                $userManipulator->updateUser($user);

                $output->writeln('<info>User mailLock status successfully changed !</info>');
            } else {
                $output->writeln('<error>Bad value for mailLock (true/false)</error>');
            }
        }

        if ($generatePassword) {
            $password = $this->container['random.medium']->generateString(64);
            $userManipulator->setPassword($user,$password);
            $output->writeln('<info>User password successfully changed !</info>');

        }

        if ($password) {
            if (!$yes) {
                do {
                    $continue = mb_strtolower($dialog->ask($output, '<question>Do you want really set password to this user? (y/N)</question>', 'N'));
                } while (!in_array($continue, ['y', 'n']));

                if ($continue !== 'y') {
                    $output->writeln('Aborting !');

                    return 0;
                }
            }

            $userManipulator->setPassword($user,$password);

            $output->writeln('<info>User password successfully changed !</info>');
        }

        return 0;
    }
}
