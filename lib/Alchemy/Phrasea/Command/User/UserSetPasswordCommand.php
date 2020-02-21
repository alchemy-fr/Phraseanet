<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\User;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;


class UserSetPasswordCommand extends Command
{

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('user:set-password');

        $this->setDescription('Set user password in Phraseanet')
            ->addOption('user_id', null, InputOption::VALUE_REQUIRED, 'The id of user.')
            ->addOption('generate', null, InputOption::VALUE_NONE, 'Generate the password')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'The password')
            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {

        $dialog = $this->getHelperSet()->get('dialog');
        $userRepository = $this->container['repo.users'];
        $userManipulator = $this->container['manipulator.user'];
        $user = $userRepository->find($input->getOption('user_id'));
        $password = $input->getOption('password');
        $generate = $input->getOption('generate');

        if ($user === null) {
            $output->writeln('<info>Not found User.</info>');
            return 0;
        }

        if ($generate) {
            $password = $this->container['random.medium']->generateString(64);
        } else {
            if (!$password) {
                $output->writeln('<error>--password option not specified</error>');
                return 0;
            }
        }

        do {
            $continue = mb_strtolower($dialog->ask($output, '<question>Do you want really set password to this user? (y/N)</question>', 'N'));
        } while (!in_array($continue, ['y', 'n']));

        if ($continue !== 'y') {
            $output->writeln('Aborting !');

            return;
        }

        $userManipulator->setPassword($user,$password);
        $output->writeln('New password: <info>' . $password . '</info>');

        return 0;
    }

}
