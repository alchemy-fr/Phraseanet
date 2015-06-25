<?php

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\ORM\EntityManager;
use Entities\UsrAuthProvider;
use Repositories\UsrAuthProviderRepository;

class RegistrationService
{
    /**
     * @var array
     */
    private static $userPropertySetterMap = array(
        'gender'    => 'set_gender',
        'firstname' => 'set_firstname',
        'lastname'  => 'set_lastname',
        'address'   => 'set_address',
        'zipcode'   => 'set_zip',
        'tel'       => 'set_tel',
        'fax'       => 'set_fax',
        'job'       => 'set_job',
        'company'   => 'set_company',
        'position'  => 'set_position',
        'geonameid' => 'set_geonameid',
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
     * @var UsrAuthProviderRepository
     */
    private $userAuthenticationProviderRepository;

    /**
     * @param Application $application
     * @param \appbox $appbox
     * @param ProvidersCollection $oauthProviderCollection
     * @param UsrAuthProviderRepository $userAuthenticationProviderRepository
     */
    public function __construct(
        Application $application,
        \appbox $appbox,
        ProvidersCollection $oauthProviderCollection,
        UsrAuthProviderRepository $userAuthenticationProviderRepository
    ) {
        $this->app = $application;
        $this->appbox = $appbox;
        $this->oauthProviderCollection = $oauthProviderCollection;
        $this->userAuthenticationProviderRepository = $userAuthenticationProviderRepository;
    }

    /**
     * @param $providerId
     * @return null|\User_Adapter
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

    public function registerUser(array $data, array $selectedCollections, $providerId = null)
    {
        require_once $this->app['root.path'] . '/lib/classes/deprecated/inscript.api.php';

        $provider = null;

        if ($providerId !== null) {
            $provider = $this->oauthProviderCollection->get($providerId);
        }

        $inscriptions = giveMeBases($this->app);
        $authorizedCollections = $this->getAuthorizedCollections($selectedCollections, $inscriptions);

        if (!isset($data['login'])) {
            $data['login'] = $data['email'];
        }

        $user = \User_Adapter::create($this->app, $data['login'], $data['password'], $data['email'], false);

        foreach (self::$userPropertySetterMap as $property => $method) {
            if (isset($data[$property])) {
                call_user_func(array($user, $method), $data[$property]);
            }
        }

        if (null !== $provider) {
            $this->attachProviderToUser($this->app['EM'], $provider, $user);
            $this->app['EM']->flush();
        }


        if ($this->app['phraseanet.registry']->get('GV_autoregister')) {
            $this->applyAclsToUser($authorizedCollections, $user);
        }

        $this->createCollectionAccessDemands($user, $authorizedCollections);
        $user->set_mail_locked(true);

        return $user;
    }

    private function attachProviderToUser(EntityManager $em, ProviderInterface $provider, \User_Adapter $user)
    {
        $usrAuthProvider = new UsrAuthProvider();
        $usrAuthProvider->setDistantId($provider->getToken()->getId());
        $usrAuthProvider->setProvider($provider->getId());
        $usrAuthProvider->setUsrId($user->get_id());

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
    private function applyAclsToUser($authorizedCollections, $user)
    {
        $template_user_id = \User_Adapter::get_usr_id_from_login($this->app, 'autoregister');
        $template_user = \User_Adapter::getInstance($template_user_id, $this->app);

        $base_ids = array();

        foreach (array_keys($authorizedCollections) as $base_id) {
            $base_ids[] = $base_id;
        }

        $user->ACL()->apply_model($template_user, $base_ids);
    }

    /**
     * @param \User_Adapter $user
     * @param array $authorizedCollections
     */
    private function createCollectionAccessDemands(\User_Adapter $user, $authorizedCollections)
    {
        $demandOK = array();
        $autoReg = $user->ACL()->get_granted_base();
        $appbox_register = new \appbox_register($this->appbox);

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
