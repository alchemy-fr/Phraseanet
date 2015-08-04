<?php

namespace Alchemy\Phrasea\Account;

use Alchemy\Phrasea\Account\Command\UpdateAccountCommand;
use Alchemy\Phrasea\Account\Command\UpdateFtpSettingsCommand;
use Alchemy\Phrasea\Account\Command\UpdatePasswordCommand;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Core\Event\AccountDeletedEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class AccountService
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var \connection_pdo
     */
    private $connection;

    /**
     * @var Authenticator
     */
    private $authenticationService;

    /**
     * @var PasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    private $updateAccountMethodMap = [
        'getEmail' => 'set_email',
        'getGender' => 'set_gender',
        'getFirstName' => 'set_firstname',
        'getLastName' => 'set_lastname',
        'getAddress' => 'set_address',
        'getZipCode' => 'set_zip',
        'getCity' => 'set_city',
        'getPhone' => 'set_tel',
        'getFax' => 'set_fax',
        'getJob' => 'set_job',
        'getCompany' => 'set_company',
        'getPosition' => 'set_position',
        'getGeonameId' => 'set_geonameid',
        'getNotifications' => 'set_mail_notifications'
    ];

    private $updateFtpSettingsMap = [
        'isEnabled' => 'set_activeftp',
        'getAddress' => 'set_ftp_address',
        'getLogin' => 'set_ftp_login',
        'getPassword' => 'set_ftp_password',
        'getPassiveMode' => 'set_ftp_passif',
        'getFolder' => 'set_ftp_dir',
        'getFolderPrefix' => 'set_ftp_dir_prefix',
        'getDefaultData' => 'set_defaultftpdatas'
    ];

    public function __construct(
        Application $application,
        \connection_pdo $appboxConnection,
        Authenticator $authenticator,
        PasswordEncoderInterface $passwordEncoder,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->application = $application;
        $this->authenticationService = $authenticator;
        $this->connection = $appboxConnection;
        $this->passwordEncoder = $passwordEncoder;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function updatePassword(UpdatePasswordCommand $command, $login = null)
    {
        $user = $this->authenticationService->getUser();
        $passwordIsValid = $this->passwordEncoder->isPasswordValid(
            $user->get_password(),
            $command->getOldPassword(),
            $user->get_nonce()
        );

        if (! $passwordIsValid) {
            throw new AccountException('Invalid password provided');
        }

        $user = $this->getUserOrCurrentUser($login);
        $encodedPassword = $this->passwordEncoder->encodePassword($command->getPassword(), $user->get_nonce());

        $user->setEncodedPassword($encodedPassword);
    }

    public function updateAccount(UpdateAccountCommand $command, $login = null)
    {
        $this->connection->beginTransaction();

        try {
            $user = $this->getUserOrCurrentUser($login);

            foreach ($this->updateAccountMethodMap as $getter => $setter) {
                $value = call_user_func([$command, $getter]);

                if ($value !== null) {
                    call_user_func([$user, $setter], $value);
                }
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollback();

            throw new AccountException('Account update failed', 0, $e);
        }
    }

    public function updateFtpSettings(UpdateFtpSettingsCommand $command)
    {
        $this->connection->beginTransaction();

        try {
            $user = $this->authenticationService->getUser();

            foreach ($this->updateFtpSettingsMap as $getter => $setter) {
                $value = call_user_func([$command, $getter]);

                if ($value !== null) {
                    call_user_func([$user, $setter], $value);
                }
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollback();

            throw new AccountException('Account FTP settings update failed', 0, $e);
        }
    }

    public function deleteAccount($login = null)
    {
        $user = $this->getUserOrCurrentUser($login);

        $user->delete();
    }

    /**
     * @param $login
     * @return \User_Adapter
     */
    private function getUserOrCurrentUser($login = null)
    {
        if ($login !== null) {
            $userId = \User_Adapter::get_usr_id_from_login($this->application, $login);

            if ($userId === false) {
                throw new AccountException('User not found');
            }

            $user = new \User_Adapter($userId, $this->application);
        } else {
            $user = $this->authenticationService->getUser();
        }

        return $user;
    }
}
