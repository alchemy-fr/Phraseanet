<?php

namespace Alchemy\Phrasea\Account;

use Alchemy\Phrasea\Account\Command\UpdateAccountCommand;
use Alchemy\Phrasea\Account\Command\UpdateFtpCredentialsCommand;
use Alchemy\Phrasea\Account\Command\UpdatePasswordCommand;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Model\Entities\FtpCredential;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manager\UserManager;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class AccountService
{

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

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var UserManipulator
     */
    private $userManipulator;

    /**
     * @var UserRepository
     */
    private $userRepository;


    private $updateAccountMethodMap = [
        'getEmail' => 'setEmail',
        'getGender' => 'setGender',
        'getFirstName' => 'setFirstName',
        'getLastName' => 'setLastName',
        'getAddress' => 'setAddress',
        'getZipCode' => 'setZipCode',
        'getCity' => 'setCity',
        'getPhone' => 'setPhone',
        'getFax' => 'setFax',
        'getJob' => 'setJob',
        'getCompany' => 'setCompany',
        'getPosition' => 'setActivity',
        'getGeonameId' => 'setGeonameId',
        'getNotifications' => 'setMailNotificationsActivated'
    ];

    private $updateFtpSettingsMap = [
        'isEnabled' => 'setActive',
        'getAddress' => 'setAddress',
        'getLogin' => 'setLogin',
        'getPassword' => 'setPassword',
        'getPassiveMode' => 'setPassive',
        'getFolder' => 'setReceptionFolder',
        'getFolderPrefix' => 'setRepositoryPrefixName',
        'getRetries' => 'setMaxRetry'
    ];

    public function __construct(
        Authenticator $authenticator,
        PasswordEncoderInterface $passwordEncoder,
        EventDispatcherInterface $eventDispatcher,
        EntityManager $entityManager,
        UserManager $userManager,
        UserManipulator $userManipulator,
        UserRepository $userRepository
    ) {
        $this->authenticationService = $authenticator;
        $this->passwordEncoder = $passwordEncoder;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
        $this->userManipulator = $userManipulator;
        $this->userRepository = $userRepository;
    }

    public function updatePassword(UpdatePasswordCommand $command, $login = null)
    {
        $user = $this->getUserOrCurrentUser($login);
        $passwordIsValid = $this->passwordEncoder->isPasswordValid(
            $user->getPassword(),
            $command->getOldPassword(),
            $user->getNonce()
        );

        if (! $passwordIsValid) {
            throw new AccountException('Invalid password provided');
        }

        $this->userManipulator->setPassword($user, $command->getPassword());
    }

    public function updateAccount(UpdateAccountCommand $command, $login = null)
    {
        try {
            $user = $this->getUserOrCurrentUser($login);

            foreach ($this->updateAccountMethodMap as $getter => $setter) {
                $value = call_user_func([$command, $getter]);

                if ($value !== null) {
                    call_user_func([$user, $setter], $value);
                }
            }

            $this->userManager->update($user, true);
        } catch (\Exception $e) {
            throw new AccountException('Account update failed:' . $e->getMessage(), 0, $e);
        }
    }

    public function updateFtpSettings(UpdateFtpCredentialsCommand $command)
    {
        try {
            $user = $this->authenticationService->getUser();
            $credentials = $user->getFtpCredential();

            if (null === $credentials) {
                $credentials = new FtpCredential();
                $credentials->setUser($user);
            }

            foreach ($this->updateFtpSettingsMap as $getter => $setter) {
                $value = call_user_func([$command, $getter]);

                if ($value !== null) {
                    call_user_func([$credentials, $setter], $value);
                }
            }

            $this->entityManager->persist($credentials);
            $this->entityManager->flush($credentials);
        } catch (\Exception $e) {
            throw new AccountException('Account FTP settings update failed', 0, $e);
        }
    }

    /**
     * @param string $login
     * @throws AccountException
     */
    public function deleteAccount($login = null, array $grantedBaseIdList = array())
    {
        $user = $this->getUserOrCurrentUser($login);

        $this->userManipulator->delete($user, $grantedBaseIdList);
    }

    /**
     * @param string $login
     * @return User
     */
    private function getUserOrCurrentUser($login = null)
    {
        if ($login !== null) {
            $user = $this->userRepository->findByLogin($login);

            if (! $user) {
                throw new AccountException('User not found');
            }
        } else {
            $user = $this->authenticationService->getUser();
        }

        return $user;
    }
}
