<?php

namespace Alchemy\Phrasea\Account;

use Alchemy\Phrasea\Account\Command\UpdateAccountCommand;
use Alchemy\Phrasea\Account\Command\UpdateFtpSettingsCommand;
use Alchemy\Phrasea\Authentication\Authenticator;

class AccountService
{

    /**
     * @var \connection_pdo
     */
    private $connection;

    /**
     * @var Authenticator
     */
    private $authenticationService;

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
        'getNotifications' => 'set_notifications'
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

    public function __construct(\connection_pdo $appboxConnection, Authenticator $authenticator)
    {
        $this->authenticationService = $authenticator;
        $this->connection = $appboxConnection;
    }

    public function updateAccount(UpdateAccountCommand $command)
    {
        $this->connection->beginTransaction();

        try {
            $user = $this->authenticationService->getUser();

            foreach ($this->updateAccountMethodMap as $getter => $setter) {
                $value = call_user_func([$command, $getter]);

                if ($value !== null) {
                    call_user_func([$user, $setter], $value);
                }
            }

            $this->connection->commit();
        }
        catch (\Exception $e) {
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
        }
        catch (\Exception $e) {
            $this->connection->rollback();

            throw new AccountException('Account FTP settings update failed', 0, $e);
        }
    }
}
