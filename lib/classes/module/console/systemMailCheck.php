<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Setup\Version\MailChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class module_console_systemMailCheck extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Checks if email addresses are uniques (mandatory since 3.5)');
        $this->addOption('list'
            , 'l'
            , null
            , 'List all bad accounts instead of the interactive mode'
        );

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Processing...");

        /** @var appbox $appBox */
        $appBox = $this->getService('phraseanet.appbox');
        $checker = new MailChecker($appBox);
        $bad_users = $checker->getWrongEmailUsers();

        foreach ($bad_users as $email => $users) {
            if ($input->getOption('list')) {
                $this->write_infos($email, $users, $output, $appBox);
            } elseif ($this->manage_group($email, $users, $output, $appBox) === false) {
                break;
            }

            $output->writeln("");
        }

        $output->write('Finished !', true);

        return 0;
    }

    protected function manage_group($email, $users, $output, $appbox)
    {
        $is_stopped = false;

        while (! $is_stopped) {
            $this->write_infos($email, $users, $output, $appbox);

            $dialog = $this->getHelperSet()->get('dialog');

            do {
                $question = '<question>What should I do ? '
                    . 'continue (C), detach from mail (d), or stop (s)</question>';

                $continue = mb_strtolower($dialog->ask($output, $question, 'C'));
            } while ( ! in_array($continue, ['c', 'd', 's']));

            if ($continue == 's') {
                return false;
            } elseif ($continue == 'c') {
                return true;
            } elseif ($continue == 'd') {
                $dialog = $this->getHelperSet()->get('dialog');

                $id = $dialog->ask($output, '<question>Which id ?</question>', '');

                try {
                    $tmp_user = $this->container['repo.users']->find($id);

                    if ($tmp_user->getEmail() != $email) {
                        throw new Exception('Invalid user');
                    }

                    $tmp_user->setEmail(null);

                    unset($users[$id]);
                } catch (\Exception $e) {
                    $output->writeln('<error>Wrong id</error>');
                }
            }

            if (count($users) <= 1) {
                $output->writeln(sprintf("<info>\n%s fixed !</info>", $email));
                $is_stopped = true;
            }
        }

        return true;
    }

    protected function write_infos($email, $users, $output, $appbox)
    {
        $output->writeln($email);

        foreach ($users as $user) {
            $dateConn = new \DateTime($user['last_conn']);

            $output->writeln(
                sprintf(
                    "\t %5d %40s   %s"
                    , $user['usr_id']
                    , $user['usr_login']
                    , $dateConn->format('Y m d')
                )
            );
        }
    }
}
