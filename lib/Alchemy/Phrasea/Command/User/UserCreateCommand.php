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
use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordSetup;
use Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation;
use Alchemy\Phrasea\Notification\Receiver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;


class UserCreateCommand extends Command
{
    use NotifierAware;

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct('user:create');

        $this->setDescription('Create user in Phraseanet')
            ->addOption('user_login', null, InputOption::VALUE_REQUIRED, 'The desired login for created user.')
            ->addOption('user_mail', null, InputOption::VALUE_OPTIONAL, 'The desired mail for created user.')
            ->addOption('user_password', null, InputOption::VALUE_OPTIONAL, 'The desired password')
            ->addOption('send_mail_confirm', null, InputOption::VALUE_NONE, 'Send an email to user, for validate email.')
            ->addOption('send_mail_password', null, InputOption::VALUE_NONE, 'Send an email to user, for password definition, work only if user_password is not define')
            ->addOption('model_number', null, InputOption::VALUE_OPTIONAL, 'Id of model')
            ->addOption('user_gender', null, InputOption::VALUE_OPTIONAL, 'The gender for created user.')
            ->addOption('user_firstname', null, InputOption::VALUE_OPTIONAL, 'The first name for created user.')
            ->addOption('user_lastname', null, InputOption::VALUE_OPTIONAL, 'The last name for created user.')
            ->addOption('user_compagny', null, InputOption::VALUE_OPTIONAL, 'The compagny for created user.')
            ->addOption('user_job', null, InputOption::VALUE_OPTIONAL, 'The job for created user.')
            ->addOption('user_activitie', null, InputOption::VALUE_OPTIONAL, 'The activitie for created user.')
            ->addOption('user_phone', null, InputOption::VALUE_OPTIONAL, 'The phone number for created user.')
            ->setHelp('');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {

        $userLogin       = $input->getOption('user_login');
        $userMail        = $input->getOption('user_mail');
        $userPassword    = $input->getOption('user_password');
        $sendMailConfirm = $input->getOption('send_mail_confirm');
        $sendMailPassword    = $input->getOption('send_mail_password');
        $modelNumber     = $input->getOption('model_number');
        $userGender      = $input->getOption('user_gender');
        $userFirstName   = $input->getOption('user_firstname');
        $userLastName    = $input->getOption('user_lastname');
        $userCompagny    = $input->getOption('user_compagny');
        $userJob         = $input->getOption('user_job');
        $userActivity    = $input->getOption('user_activitie');
        $userPhone       = $input->getOption('user_phone');
        
        $userRepository = $this->container['repo.users'];

        if ($userMail) {
            if (!\Swift_Validate::email($userMail)) {
                $output->writeln('<error>Invalid mail address</error>');
                return 0;
            }

            if (null !== $userRepository->findByEmail($userMail)) {
                $output->writeln('<error>An user exist with this email.</error>');
                return 0;
            }

        }

        $password = (!is_null($userPassword)) ? $userPassword : $this->container['random.medium']->generateString(128);
        $userManipulator = $this->container['manipulator.user'];
        $user = $userManipulator->createUser($userLogin, $password, $userMail);

        if ($userGender) {
            if (null === $gender = $this->verifyGender($userGender)) {
                $output->writeln('<bg=yellow;options=bold>Gender '.$userGender.' not exists.</>');
            }
            $user->setGender($gender);
        }

        if($userFirstName) $user->setFirstName($userFirstName);
        if($userLastName) $user->setLastName($userLastName);
        if($userCompagny) $user->setCompany($userCompagny);
        if($userJob) $user->setJob($userJob);
        if($userActivity) $user->setActivity($userActivity);
        if($userPhone) $user->setPhone($userPhone);
        
        if ($sendMailPassword and $userMail and is_null($userPassword)) {
            $this->sendPasswordSetupMail($user);
        }

        if ($sendMailConfirm and $userMail) {
            $user->setMailLocked(true);
            $this->sendAccountUnlockEmail($user);
        }

        if ($modelNumber) {
            $template = $userRepository->find($modelNumber);
            if (!$template) {
                $output->writeln('<bg=yellow;options=bold>Model '.$modelNumber.' not found.</>');
            } else {
                $base_ids = [];
                foreach ($this->container->getApplicationBox()->get_databoxes() as $databox) {
                    foreach ($databox->get_collections() as $collection) {
                        $base_ids[] = $collection->get_base_id();
                    }
                }
                $this->container->getAclForUser($user)->apply_model($template, $base_ids);
            }
        }

        $this->container['orm.em']->flush();

        $output->writeln("<info>Create new user successful !</info>");

        return 0;
    }

    /**
     * Get gender for user
     * @param $type
     * @return int|null
     */
    private function verifyGender($type)
    {
        switch (strtolower($type)) {
            case "mlle.":
            case "mlle":
            case "miss":
            case "mademoiselle":
            case "0":
                $gender = User::GENDER_MISS;
                break;
            case "mme":
            case "madame":
            case "ms":
            case "ms.":
            case "1":
                $gender = User::GENDER_MRS;
                break;
            case "m":
            case "m.":
            case "mr":
            case "mr.":
            case "monsieur":
            case "mister":
            case "2":
                $gender =  User::GENDER_MR;
                break;
            default:
                $gender = null;
        }
        return $gender;
    }

    /**
     * Send mail for renew password
     * @param User $user
     */
    public function sendPasswordSetupMail(User $user)
    {
        $this->setDelivererLocator(new LazyLocator($this->container, 'notification.deliverer'));
        $receiver = Receiver::fromUser($user);

        $token = $this->container['manipulator.token']->createResetPasswordToken($user);

        $mail = MailRequestPasswordSetup::create($this->container, $receiver);
        $servername = $this->container['conf']->get('servername');
        $mail->setButtonUrl('http://'.$servername.'/login/renew-password/?token='.$token->getValue());
        $mail->setLogin($user->getLogin());

        $this->deliver($mail);
    }

    /**
     * @param User $user
     */
    public function sendAccountUnlockEmail(User $user)
    {
        $this->setDelivererLocator(new LazyLocator($this->container, 'notification.deliverer'));
        $receiver = Receiver::fromUser($user);

        $token = $this->container['manipulator.token']->createAccountUnlockToken($user);

        $mail = MailRequestEmailConfirmation::create($this->container, $receiver);
        $servername = $this->container['conf']->get('servername');
        $mail->setButtonUrl('http://'.$servername.'/login/register-confirm/?code='.$token->getValue());
        $mail->setExpiration($token->getExpiration());

        $this->deliver($mail);
    }

}
