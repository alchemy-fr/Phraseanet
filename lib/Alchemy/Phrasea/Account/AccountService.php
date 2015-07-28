<?php

namespace Alchemy\Phrasea\Account;

use Alchemy\Phrasea\Account\Command\UpdateAccountCommand;
use Alchemy\Phrasea\Account\Command\UpdateFtpSettingsCommand;
use Alchemy\Phrasea\Account\Command\UpdatePasswordCommand;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Authenticator;
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

    private $updateAccountMethodMap = [
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
        PasswordEncoderInterface $passwordEncoder
    ) {
        $this->application = $application;
        $this->authenticationService = $authenticator;
        $this->connection = $appboxConnection;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function updatePassword(UpdatePasswordCommand $command, $email = null)
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

        $user = $this->getUserOrCurrentUser($email);
        $encodedPassword = $this->passwordEncoder->encodePassword($command->getPassword(), $user->get_nonce());

        $user->setEncodedPassword($encodedPassword);
    }

    public function updateAccount(UpdateAccountCommand $command, $email = null)
    {
        $this->connection->beginTransaction();

        try {
            $user = $this->getUserOrCurrentUser($email);

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

    /**
     * @param $email
     * @return \User_Adapter
     */
    private function getUserOrCurrentUser($email = null)
    {
        if ($email !== null) {
            $userId = \User_Adapter::get_usr_id_from_email($this->application, $email);

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
