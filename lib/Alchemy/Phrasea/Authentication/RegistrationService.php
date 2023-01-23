<?php

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Exception\RegistrationException;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Configuration\RegistrationManager;
use Alchemy\Phrasea\Core\Event\RegistrationEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UsrAuthProvider;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Manipulator\RegistrationManipulator;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\UsrAuthProviderRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RegistrationService
{
    /**
     * @var array
     */
    private static $userPropertySetterMap = array(
        'gender'    => 'setGender',
        'firstname' => 'setFirstName',
        'lastname'  => 'setLastName',
        'address'   => 'setAddress',
        'city'      => 'setCity',
        'zipcode'   => 'setZipCode',
        'tel'       => 'setPhone',
        'fax'       => 'setFax',
        'job'       => 'setJob',
        'company'   => 'setCompany',
        'position'  => 'setJob',
        'geonameid' => 'setGeonameId',
        'notifications' => 'setMailNotificationsActivated'
    );

    /**
     * @var Application
     */
    private $app;

    /**
     * @var \appbox
     */
    private $appbox;

    /**
     * @var ACLProvider
     */
    private $aclProvider;

    /**
     * @var PropertyAccess
     */
    private $configuration;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ProvidersCollection
     */
    private $oauthProviderCollection;

    /**
     * @var RegistrationManager
     */
    private $registrationManager;

    /**
     * @var UsrAuthProviderRepository
     */
    private $userAuthenticationProviderRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var UserManipulator
     */
    private $userManipulator;

    /**
     * @var TokenManipulator
     */
    private $tokenManipulator;

    /**
     * @var TokenRepository
     */
    private $tokenRepository;

    /**
     * @var RegistrationManipulator
     */
    private $registrationManipulator;

    /**
     * @param Application $application
     * @param \appbox $appbox
     * @param ACLProvider $aclProvider
     * @param PropertyAccess $configuration
     * @param EntityManager $entityManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param ProvidersCollection $oauthProviderCollection
     * @param UsrAuthProviderRepository $userAuthenticationProviderRepository
     * @param UserRepository $userRepository
     * @param UserManipulator $userManipulator
     * @param TokenManipulator $tokenManipulator
     * @param TokenRepository $tokenRepository
     * @param RegistrationManipulator $registrationManipulator
     * @param RegistrationManager $registrationManager
     */
    public function __construct(
        Application $application,
        \appbox $appbox,
        ACLProvider $aclProvider,
        PropertyAccess $configuration,
        EntityManager $entityManager,
        EventDispatcherInterface $eventDispatcher,
        ProvidersCollection $oauthProviderCollection,
        UsrAuthProviderRepository $userAuthenticationProviderRepository,
        UserRepository $userRepository,
        UserManipulator $userManipulator,
        TokenManipulator $tokenManipulator,
        TokenRepository $tokenRepository,
        RegistrationManipulator $registrationManipulator,
        RegistrationManager $registrationManager
    ) {
        $this->aclProvider = $aclProvider;
        $this->app = $application;
        $this->appbox = $appbox;
        $this->configuration = $configuration;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->oauthProviderCollection = $oauthProviderCollection;
        $this->userAuthenticationProviderRepository = $userAuthenticationProviderRepository;
        $this->userRepository = $userRepository;
        $this->userManipulator = $userManipulator;
        $this->tokenManipulator = $tokenManipulator;
        $this->tokenRepository = $tokenRepository;
        $this->registrationManipulator = $registrationManipulator;
        $this->registrationManager = $registrationManager;
    }

    /**
     * @param $providerId
     * @return null|User
     */
    public function registerOauthUser($providerId)
    {
        $provider = $this->oauthProviderCollection->get($providerId);
        $token = $provider->getToken();

        $userAuthenticationProvider = $this->userAuthenticationProviderRepository->findWithProviderAndId(
            $provider->getId(),
            $token->getId()
        );

        if ($userAuthenticationProvider) {
            return $userAuthenticationProvider->getUser();
        }

        return null;
    }

    public function registerUser(array $data, array $selectedCollections = null, $providerId = null)
    {
        $provider = null;

        if ($providerId !== null) {
            $provider = $this->oauthProviderCollection->get($providerId);
        }

        $authorizedCollections = $this->getAuthorizedCollections($selectedCollections);

        if (!isset($data['login'])) {
            $data['login'] = $data['email'];
        }

        $user = $this->userManipulator->createUser($data['login'], $data['password'], $data['email'], false);

        if (isset($data['geonameid'])) {
            $this->userManipulator->setGeonameId($user, $data['geonameid']);
        }

        foreach (self::$userPropertySetterMap as $property => $method) {
            if (isset($data[$property])) {
                $user->$method($data[$property]);
            }
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush($user);

        if (null !== $provider) {
            $this->attachProviderToUser($provider, $user);
            $this->entityManager->flush();
        }

        if ($this->configuration->get(['registry', 'registration', 'auto-register-enabled'])) {
            $acl = $this->aclProvider->get($user);
            foreach ($authorizedCollections as $baseId => $collection) {
                if( ($model = $collection->getAutoregisterModel($data['email'])) !== null) {
                    if( ($template_user = $this->userRepository->findByLogin($model)) !== null) {
                        $acl->apply_model($template_user, [$baseId]);
                    }
                }
            }
        }

        $this->createCollectionAccessDemands($user, $authorizedCollections);
        $user->setMailLocked(true);

        return $user;
    }

    public function createCollectionRequests(User $user, array $collections)
    {
        $authorizedCollections = $this->getAuthorizedCollections($collections);

        $this->createCollectionAccessDemands($user, $authorizedCollections);
    }

    public function getAccountUnlockToken(User $user)
    {
        return $this->tokenManipulator->createAccountUnlockToken($user)
            ->getValue();
    }

    public function unlockAccount($token)
    {
        $token = $this->tokenRepository->findValidToken($token);

        if (! $token) {
            throw new RegistrationException('Invalid token');
        }

        $user = $token->getUser();

        if (!$user->isMailLocked()) {
            throw new RegistrationException(
                'Account is already unlocked, you can login.',
                RegistrationException::ACCOUNT_ALREADY_UNLOCKED
            );
        }

        $this->tokenManipulator->delete($token);
        $user->setMailLocked(false);

        return $user;
    }

    private function attachProviderToUser(ProviderInterface $provider, User $user)
    {
        $usrAuthProvider = new UsrAuthProvider();
        $usrAuthProvider->setDistantId($provider->getToken()->getId());
        $usrAuthProvider->setProvider($provider->getId());
        $usrAuthProvider->setUser($user);

        try {
            $provider->logout();
        } catch (RuntimeException $e) {
            // log these errors
        }

        $this->entityManager->persist($usrAuthProvider);
    }

    /**
     * @param array $selectedCollections
     * @return \collection[]
     */
    private function getAuthorizedCollections($selectedCollections)
    {
        $inscriptions = $this->registrationManager->getRegistrationSummary();
        $authorizedCollections = [];

        foreach ($this->appbox->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                if (null !== $selectedCollections && !in_array($collection->get_base_id(), $selectedCollections)) {
                    continue;
                }

                if (\igorw\get_in($inscriptions, [$databox->get_sbas_id(), 'collections', $collection->get_base_id(), 'can-register'])) {
                    $authorizedCollections[$collection->get_base_id()] = $collection;
                }
            }
        }

        return $authorizedCollections;
    }

    /**
     * @param User $user
     * @param array $authorizedCollections
     */
    private function createCollectionAccessDemands(User $user, $authorizedCollections)
    {
        $acl = $this->aclProvider->get($user);

        $registrationManipulator = $this->registrationManipulator;
        $successfulRegistrations = [];
        foreach($authorizedCollections as $baseId => $collection) {
            if(!$acl->has_access_to_base($baseId)) {
                $registrationManipulator->createRegistration($user, $collection);
                $successfulRegistrations[$baseId] = $collection;
            }
        }


        $autoReg = $acl->get_granted_base();
        foreach ($autoReg as $baseId => $collection) {
            $granted[$baseId] = $collection->get_label($this->app['locale']);

            $this->app['manipulator.webhook-event']->create(
                WebhookEvent::USER_REGISTRATION_GRANTED,
                WebhookEvent::USER_REGISTRATION_TYPE,
                [
                    'user_id'  => $user->getId(),
                    'granted'  => $granted,
                    'rejected' => []
                ],
                [$baseId]
            );

            unset($granted);
        }


        $this->eventDispatcher->dispatch(PhraseaEvents::REGISTRATION_AUTOREGISTER, new RegistrationEvent($user, $autoReg));
        $this->eventDispatcher->dispatch(PhraseaEvents::REGISTRATION_CREATE, new RegistrationEvent($user, $successfulRegistrations));
    }
}
