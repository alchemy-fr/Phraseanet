<?php

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Exception\RegistrationException;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Core\Configuration\RegistrationManager;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\Registration;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UsrAuthProvider;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\UsrAuthProviderRepository;
use Doctrine\ORM\EntityManager;

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
        'position'  => 'setPosition',
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
     * @param Application $application
     * @param \appbox $appbox
     * @param ProvidersCollection $oauthProviderCollection
     * @param UsrAuthProviderRepository $userAuthenticationProviderRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        Application $application,
        \appbox $appbox,
        ProvidersCollection $oauthProviderCollection,
        UsrAuthProviderRepository $userAuthenticationProviderRepository,
        UserRepository $userRepository
    ) {
        $this->app = $application;
        $this->appbox = $appbox;
        $this->oauthProviderCollection = $oauthProviderCollection;
        $this->userAuthenticationProviderRepository = $userAuthenticationProviderRepository;
        $this->userRepository = $userRepository;
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
            return $userAuthenticationProvider->getUser($this->app);
        }

        return null;
    }

    public function registerUser(array $data, array $selectedCollections = null, $providerId = null)
    {
        $provider = null;

        if ($providerId !== null) {
            $provider = $this->oauthProviderCollection->get($providerId);
        }

        $inscriptions = $this->registrationManager->getRegistrationSummary();
        $authorizedCollections = $this->getAuthorizedCollections($selectedCollections, $inscriptions);

        if (!isset($data['login'])) {
            $data['login'] = $data['email'];
        }

        $user = $this->userManipulator->createUser($data['login'], $data['password'], $data['email'], false);

        foreach (self::$userPropertySetterMap as $property => $method) {
            if (isset($data[$property])) {
                call_user_func(array($user, $method), $data[$property]);
            }
        }

        if (null !== $provider) {
            $this->attachProviderToUser($this->app['orm.em'], $provider, $user);
            $this->app['orm.em']->flush();
        }

        if ($this->app['phraseanet.registry']->get('GV_autoregister')) {
            $this->applyAclsToUser($authorizedCollections, $user);
        }

        $this->createCollectionAccessDemands($user, $authorizedCollections);
        $user->setMailLocked(true);

        return $user;
    }

    public function createCollectionRequests(User $user, array $collections)
    {
        $inscriptions = $this->registrationManager->getRegistrationSummary($user);
        $authorizedCollections = $this->getAuthorizedCollections($collections, $inscriptions);

        $this->createCollectionAccessDemands($user, $authorizedCollections);
    }

    public function getAccountUnlockToken(User $user)
    {
        $expire = new \DateTime('+3 days');
        $token = $this->app['tokens']->getUrlToken(
            \random::TYPE_PASSWORD,
            $user->get_id(),
            $expire,
            $user->get_email()
        );

        return $token;
    }

    public function unlockAccount($token)
    {
        $tokenData = $this->app['tokens']->helloToken($token);
        $user = \User_Adapter::getInstance((int) $tokenData['usr_id'], $this->app);

        if (!$user->get_mail_locked()) {
            throw new RegistrationException(
                'Account is already unlocked, you can login.',
                RegistrationException::ACCOUNT_ALREADY_UNLOCKED
            );
        }

        $this->app['tokens']->removeToken($token);
        $user->set_mail_locked(false);

        return $user;
    }

    private function attachProviderToUser(EntityManager $em, ProviderInterface $provider, User $user)
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

        $em->persist($usrAuthProvider);
    }

    /**
     * @param array $selectedCollections
     * @param array $inscriptions
     * @return array
     */
    private function getAuthorizedCollections(array $selectedCollections = null, array $inscriptions)
    {
        $authorizedCollections = array();

        foreach ($this->appbox->get_databoxes() as $databox) {
            $databoxId = $databox->get_sbas_id();

            foreach ($databox->get_collections() as $collection) {
                if (null !== $selectedCollections && !in_array($collection->get_base_id(), $selectedCollections)) {
                    continue;
                }

                if ($this->isCollectionAuthorized($inscriptions, $collection, $databoxId)) {
                    $authorizedCollections[$collection->get_base_id()] = true;
                } else {
                    $authorizedCollections[$collection->get_base_id()] = false;
                }
            }
        }

        return $authorizedCollections;
    }

    /**
     * @param array $inscriptions
     * @param \collection $collection
     * @param $databoxId
     * @return bool
     */
    private function isCollectionAuthorized(array $inscriptions, \collection $collection, $databoxId)
    {
        return isset($inscriptions[$databoxId])
            && $inscriptions[$databoxId]['inscript'] === true
            && (isset($inscriptions[$databoxId]['Colls'][$collection->get_coll_id()])
                || isset($inscriptions[$databoxId]['CollsCGU'][$collection->get_coll_id()]));
    }

    /**
     * @param $authorizedCollections
     * @param $user
     * @return mixed
     * @throws \Exception
     */
    private function applyAclsToUser(array $authorizedCollections, User $user)
    {
        $templateUser = $this->userRepository->findByLogin('autoregister');
        $baseIds = array();

        foreach (array_keys($authorizedCollections) as $baseId) {
            $baseIds[] = $baseId;
        }

        $user->setLastAppliedTemplate($template_user);
    }

    /**
     * @param User $user
     * @param array $authorizedCollections
     */
    private function createCollectionAccessDemands(User $user, $authorizedCollections)
    {
        $demandOK = array();
        $autoReg = $user->ACL()->get_granted_base();
        $appbox_register = new Registration();

        foreach ($authorizedCollections as $base_id => $authorization) {
            if (false === $authorization || $user->ACL()->has_access_to_base($base_id)) {
                continue;
            }

            $collection = \collection::get_from_base_id($this->app, $base_id);
            $appbox_register->add_request($user, $collection);
            $demandOK[$base_id] = true;
        }

        $params = array(
            'demand' => $demandOK,
            'autoregister' => $autoReg,
            'usr_id' => $user->get_id()
        );

        $this->app['events-manager']->trigger('__REGISTER_AUTOREGISTER__', $params);
        $this->app['events-manager']->trigger('__REGISTER_APPROVAL__', $params);
    }
}
