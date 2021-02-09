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

use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class UserPasswordCommand extends Command
{
    use NotifierAware;

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('user:password');

        $this->setDescription('Set user password in Phraseanet <comment>(experimental)</>')
            ->addOption('user_id', null, InputOption::VALUE_REQUIRED, 'The id of user.')
            ->addOption('generate', null, InputOption::VALUE_NONE, 'Generate and set with a random value')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Set the user password to the input value')
            ->addOption('send_renewal_email', null, InputOption::VALUE_NONE, 'Send email link to user for password renewing, work only if --password or --generate are not define')
            ->addOption('password_hash', null, InputOption::VALUE_OPTIONAL, 'Define a password hashed, work only with password_nonce')
            ->addOption('password_nonce', null, InputOption::VALUE_OPTIONAL, 'Define a password nonce, work only with password_hash')
            ->addOption('dump', null, InputOption::VALUE_NONE, 'Return the password hashed and nonce')
            ->addOption('jsonformat', null, InputOption::VALUE_NONE, 'Output in json format')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answer yes to all questions')

            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $dialog             = $this->getHelperSet()->get('dialog');
        $userRepository     = $this->container['repo.users'];
        $userManipulator    = $this->container['manipulator.user'];

        $user               = $userRepository->find($input->getOption('user_id'));
        $password           = $input->getOption('password');
        $generate           = $input->getOption('generate');
        $sendRenewalEmail   = $input->getOption('send_renewal_email');
        $dump               = $input->getOption('dump');
        $passwordHash       = $input->getOption('password_hash');
        $passwordNonce      = $input->getOption('password_nonce');
        $jsonformat         = $input->getOption('jsonformat');
        $yes                = $input->getOption('yes');


        if ($user === null) {
            $output->writeln('<info>Not found User.</info>');
            return 0;
        }

        if ($passwordHash && $passwordNonce) {
            $user->setNonce($passwordNonce);
            $user->setPassword($passwordHash);
            $userManipulator->updateUser($user);

            $output->writeln('<info>password set with hashed pass</info>');

            return 0;
        }

        if ($dump) {
            $oldHash  = $user->getPassword();
            $oldNonce = $user->getNonce();
        }

        if ($generate) {
            $oldHash  = $user->getPassword();
            $oldNonce = $user->getNonce();

            $password = $this->container['random.medium']->generateString(64);
        } else {
            if (!$password && $sendRenewalEmail) {
                $this->sendPasswordSetupMail($user);
                $output->writeln('<info>email link sended for password renewing!</info>');

                return 0;
            } elseif (!$password && !$sendRenewalEmail && ! $dump) {
                $output->writeln('<error>choose one option to set a password!</error>');

                return 0;
            }
        }

        if ($password) {
            if (!$yes) {
                do {
                    $continue = mb_strtolower($dialog->ask($output, '<question>Do you want really set password to this user? (y/N)</question>', 'N'));
                } while (!in_array($continue, ['y', 'n']));

                if ($continue !== 'y') {
                    $output->writeln('Aborting !');

                    return;
                }
            }
            $oldHash  = $user->getPassword();
            $oldNonce = $user->getNonce();

            $userManipulator->setPassword($user,$password);
        } 

        if ($dump) {
            if ($jsonformat) {
                $hash['password_hash']  = $oldHash;
                $hash['nonce']          = $oldNonce;

                echo json_encode($hash);

                return 0;
            } else {
                $output->writeln('<info>password_hash :</info>' . $oldHash);
                $output->writeln('<info>nonce :</info>' . $oldNonce);

                return 0;
            }
        }

        if (($password || $generate)) {
            if ($jsonformat) {
                $hash['new_password']           = $password;
                $hash['previous_password_hash'] = $oldHash;
                $hash['previous_nonce']         = $oldNonce;

                echo json_encode($hash);
            } else {
                $output->writeln('<info>new_password :</info>' . $password);
                $output->writeln('<info>previous_password_hash :</info>' . $oldHash);
                $output->writeln('<info>previous_nonce :</info>' . $oldNonce);
            }
        }

        return 0;
    }

    /**
     * Send mail for renew password
     * @param User $user
     */
    private function sendPasswordSetupMail(User $user)
    {
        $this->setDelivererLocator(new LazyLocator($this->container, 'notification.deliverer'));
        $receiver = Receiver::fromUser($user);

        $token = $this->container['manipulator.token']->createResetPasswordToken($user);
                 
        $url = $this->container['url_generator']->generate('login_renew_password', [ 'token' => $token->getValue() ], true);
        $mail = MailRequestPasswordUpdate::create($this->container, $receiver);
        $servername = $this->container['conf']->get('servername');
        $mail->setButtonUrl($url);
        $mail->setLogin($user->getLogin());
        $mail->setExpiration(new \DateTime('+1 day'));

        if (($locale = $user->getLocale()) != null) {
            $mail->setLocale($locale);
        }

        $this->deliver($mail);
    }

}
