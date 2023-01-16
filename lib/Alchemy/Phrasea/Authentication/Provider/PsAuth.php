<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2022 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Alchemy\Phrasea\Authentication\Provider\Token\Token;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UsrAuthProvider;
use Exception;
use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client as Guzzle;
use Guzzle\Http\ClientInterface;
use RandomLibtest\Mocks\Random\Generator as RandomGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class PsAuth extends AbstractProvider
{
    /**
     * @var string|null
     */
    private $iconUri;

    /**
     * @var Guzzle
     */
    private $client;

    /**
     * @var array
     */
    private $config;


    private function debug($s = '')
    {
        static $lastfile = "?";
        if(array_key_exists('debug', $this->config) && $this->config['debug'] === true) {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            if ($bt[0]['file'] != $lastfile) {
                file_put_contents('/var/alchemy/Phraseanet/logs/psauth.log', sprintf("FILE %s \n", ($lastfile = $bt[0]['file'])), FILE_APPEND);
            }
            $s = sprintf("LINE (%d) : %s\n", $bt[0]['line'], $s);
            file_put_contents('/var/alchemy/Phraseanet/logs/psauth.log', $s, FILE_APPEND);
        }
    }

    public function __construct(UrlGenerator $urlGenerator, SessionInterface $session, array $config, Guzzle $client)
    {
        parent::__construct($urlGenerator, $session);

        $this->config = $config;
        if(!array_key_exists('model-gpfx', $this->config)) {
            $this->config['model-gpfx'] = '_G_';
        }
        if(!array_key_exists('model-upfx', $this->config)) {
            $this->config['model-upfx'] = '_U_';
        }
        if(!array_key_exists('metamodel', $this->config)) {
            $this->config['metamodel'] = '_metamodel';
        }
        if(!array_key_exists('auto-logout', $this->config)) {
            $this->config['auto-logout'] = false;
        }
        if(!array_key_exists('auto-connect-idp-name', $this->config)) {
            $this->config['auto-connect-idp-name'] = null;
        }

        $this->client  = $client;
        $this->iconUri = array_key_exists('icon-uri', $config) ? $config['icon-uri'] : null; // if not set, will fallback on default icon
    }

    /**
     * {@inheritdoc}
     */
    public static function create(UrlGenerator $generator, SessionInterface $session, array $options): AbstractProvider
    {
        foreach (['client-id', 'client-secret', 'base-url', 'provider-type', 'provider-name'] as $parm) {
            if (!isset($options[$parm]) || (trim($options[$parm]) == '')) {
                throw new InvalidArgumentException(sprintf('Missing Phraseanet "%s" parameter in conf/authentification/providers', $parm));
            }
        }

        $guzzle = new Guzzle($options['base-url']);
        $guzzle->setSslVerification(false, false, 0);

        return new self($generator, $session, $options, $guzzle);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'PS Auth';
    }

    /**
     * @param ClientInterface $client
     *
     * @return self
     */
    public function setGuzzleClient(ClientInterface $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return ClientInterface
     */
    public function getGuzzleClient()
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(array $params = array()): RedirectResponse
    {
        $this->debug();
        $this->session->invalidate(0);

        /*
         * for oauth2 the callback url(s) MUST be fully static. One CAN register multiple possible urls, like
         * - one for phraseanet home : already static
         * - one for phraseanet oauth api
         * - ... ?
         * api client may want to include static/variable params to be used for final redirect (eg. parade),
         * we pass those in session
         * lib/Alchemy/Phrasea/Controller/Api/OAuth2Controller::authorizeCallbackAction(...) will restore params
         */
        $this->session->set($this->getId() . ".parms", array_merge(['providerId' => $this->getId()], $params));
        $this->debug(sprintf("authenticate params saved : session[%s] = %s",
            $this->getId() . ".parms",
            var_export($params, true)
        ));

        $params = ['providerId' => $this->getId()]; // the only required parm (constant)
        $this->debug(sprintf("redirect_uri params (cleaned) = %s", var_export($params, true)));

        $redirect_uri = $this->generator->generate(
            'login_authentication_provider_callback',
            $params,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $this->debug(sprintf("redirect_uri = %s", $redirect_uri));

        $state = $this->createState();

        $this->session->set($this->getId() . '.provider.state', $state);

        $parms = [
            'client_id' => $this->config['client-id'],
            'state' => $state,
            'redirect_uri' => $redirect_uri,
            'response_type' => "code"
        ];

        if($this->config['auto-connect-idp-name']) {
            $url = sprintf("%s/%s/%s/auth?connect=%s&%s",
                $this->config['base-url'],
                urlencode($this->config['provider-type']),
                urlencode($this->config['provider-name']),
                urlencode($this->config['auto-connect-idp-name']),
                http_build_query($parms, '', '&')
            );
        }
        else {
            $url = sprintf("%s/%s/%s/auth?%s",
                $this->config['base-url'],
                urlencode($this->config['provider-type']),
                urlencode($this->config['provider-name']),
                http_build_query($parms, '', '&')
            );
        }

        $this->debug(sprintf("go to url = %s", $url));

        return new RedirectResponse($url);
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        $this->debug("logout ?");
        if($this->config['auto-logout']) {

            // too bad: getting the logout page is not enough...
//        $url = "security/logout";
//        $guzzleRequest = $this->client->get($url);
//        $response = $guzzleRequest->send();
//        $this->debug($response->getBody());
//        return null;

            // ... we really need to redirect to it, which will prevent phr to redirect to his home
            $url = sprintf("%s/security/logout", $this->config['base-url']);

            return new RedirectResponse($url);
        }

        return null;
    }

    public function logoutAndRedirect($redirect_uri)
    {
        $this->debug("logoutAndRedirect ?");
        if($this->config['auto-logout']) {
            $url = sprintf("%s/security/logout?r=%s", $this->config['base-url'], urlencode($redirect_uri));

            return new RedirectResponse($url);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function onCallback(Request $request)
    {
        $this->debug();
        if (!$this->session->has($this->getId() . '.provider.state')) {
            throw new NotAuthenticatedException('No state value in session ; CSRF try ?');
        }
        $this->debug();
        if ($request->query->get('state') !== $this->session->remove($this->getId() . '.provider.state')) {
            throw new NotAuthenticatedException('Invalid state value ; CSRF try ?');
        }
        $this->debug();
        try {
            $url = sprintf("%s/%s/token",
                urlencode($this->config['provider-type']),
                urlencode($this->config['provider-name'])
            );
            $guzzleRequest = $this->client->post($url);

            $guzzleRequest->addPostFields([
                'grant_type' => "authorization_code",
                'code' => $request->query->get('code'),
                'redirect_uri' => $this->generator->generate(
                    'login_authentication_provider_callback',
                    ['providerId' => $this->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'client_id' => $this->config['client-id'],
                'client_secret' => $this->config['client-secret'],
            ]);
            $guzzleRequest->setHeader('Accept', 'application/json');
            $this->debug();
            $response = $guzzleRequest->send();
            $this->debug();
        }
        catch (GuzzleException $e) {
            $this->debug();
            throw new NotAuthenticatedException('Guzzle error while authentication', $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            $this->debug();
            throw new NotAuthenticatedException('Error while getting access_token');
        }

        $this->debug();
        $data = @json_decode($response->getBody(true), true);
        $this->debug();

        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->debug();
            throw new NotAuthenticatedException('Error while decoding token response, unable to parse JSON.');
        }

        $this->debug(var_export($data, true));
        $this->session->remove($this->getId() . '.provider.state');
        $this->session->set($this->getId() . '.provider.access_token', $data['access_token']);

        try {
            $this->debug();
            // $request = $this->client->get($this->getId() . 'userinfo');
            $request = $this->client->get('me');
            $request->getQuery()->add('access_token', $data['access_token']);
            $request->setHeader('Accept', 'application/json');
            $this->debug();

            $response = $request->send();
            $this->debug();
        }
        catch (GuzzleException $e) {
            $this->debug();
            throw new NotAuthenticatedException('Guzzle error while authentication', $e->getCode(), $e);
        }

        $this->debug();
        $data = @json_decode($response->getBody(true), true);
        $this->debug(var_export($data, true));

        if (200 !== $response->getStatusCode()) {
            $this->debug();
            throw new NotAuthenticatedException('Error while retrieving user info, invalid status code.');
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->debug();
            throw new NotAuthenticatedException('Error while retrieving user info, unable to parse JSON.');
        }

        $this->debug();

        $userUA = $this->CreateUser([
            'id'        => $distantUserId = $data['user_id'],
            'login'     => $data['username'],
            'firstname' => null,
            'lastname'  => null,
            'email'     => $data['email'],
            '_groups'   => $data['groups']
        ]);

        $userAuthProviderRepository = $this->getUsrAuthProviderRepository();
        $userAuthProvider = $userAuthProviderRepository
            ->findWithProviderAndId($this->getId(), $distantUserId);

        if (!$userAuthProvider) {
            $manager = $this->getEntityManager();

            $usrAuthProvider = new UsrAuthProvider();
            $usrAuthProvider->setDistantId($distantUserId);
            $usrAuthProvider->setProvider($this->getId());
            $usrAuthProvider->setUser($userUA);

            try {
                $manager->persist($usrAuthProvider);
                $manager->flush();
            }
            catch (\Exception $e) {
                // no-op
                $this->debug();
            }
        }

        $this->session->set($this->getId() . ".provider.id", $distantUserId);
        $this->session->set($this->getId() . ".provider.username", $data['username']);

        $this->debug(sprintf("session->set('%s', '%s')", $this->getId() . ".provider.id", $distantUserId));
        $this->debug(sprintf("session->set('%s', '%s')", $this->getId() . ".provider.username", $data['username']));
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): Token
    {
        $this->debug();
        $distantUserId = $this->session->get($this->getId() . '.provider.id');
        $this->debug(sprintf("session->get('%s') ==> '%s')", $this->getId() . ".provider.id", $distantUserId));

        if ('' === trim($distantUserId)) {
            $this->debug();
            throw new NotAuthenticatedException($this->getId() . ' has not authenticated');
        }

        $this->debug();
        $token = new Token($this, $distantUserId);
        $this->debug();

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity(): Identity
    {
        $this->debug();
        $identity = new Identity();

        try {
            $request = $this->client->get('me');
            $request->getQuery()->add('access_token', $this->session->get($this->getId() . '.provider.access_token'));
            $request->setHeader('Accept', 'application/json');

            $response = $request->send();
        }
        catch (GuzzleException $e) {
            $this->debug();
            throw new NotAuthenticatedException('Error while retrieving user info', $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            $this->debug();
            throw new NotAuthenticatedException('Error while retrieving user info');
        }

        $data = @json_decode($response->getBody(true), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->debug();
            throw new NotAuthenticatedException('Error while parsing json');
        }

        $this->debug();
        $identity->set(Identity::PROPERTY_EMAIL, $data['email']);
        $identity->set(Identity::PROPERTY_ID, $data['user_id']);
        $identity->set(Identity::PROPERTY_USERNAME, $data['username']);

        $this->debug();
        return $identity;
    }

    /**
     * @param array $data
     * @return User|null
     * @throws Exception
     */
    private function CreateUser(Array $data)
    {
        $userManipulator = $this->getUserManipulator();
        $userRepository = $this->getUserRepository();
        $ACLProvider = $this->getACLProvider();

        $ret = null;

        $login = trim($data['login']);

        $this->debug(sprintf("login=%s \n", var_export($login, true)));

        if ($login == "") {
            $this->debug("login is empty, user not created \n");
        }

        /** @var User $userUA */
        $userUA = $userRepository->findByLogin($login);

        if (!$userUA) {
            // need to create the user
            $this->debug(sprintf("creating user \"%s\" \n", $login));
            $tmp_email = str_replace(['.', '@'], ['_', '_'], $login) . "@nomail.eu";
            $userUA = $userManipulator->createUser($login, 'user_tmp_pwd', $tmp_email, false);


            if ($userUA) {
                $this->debug(sprintf("found user \"%s\" with id=%s \n", $login, $userUA->getId()));

                // if the id provider does NOT return groups, the new user will get "birth" privileges
                if (!is_array($data['_groups']) && array_key_exists('birth-group', $this->config)) {
                    $data['_groups'] = [$this->config['birth-group']];
                }
            }
            else {
                $this->debug(sprintf("failed to create user \"%s\" \n", $login));
            }
        }
        else {
            // the user already exists
            $this->debug(sprintf("found user \"%s\" with id=%s \n", $login, $userUA->getId()));

            // if the id provider does return groups, then revoke privileges
            if (is_array($data['_groups'])) {
                $appbox = $this->getAppbox();
                $all_base_ids = [];
                foreach ($appbox->get_databoxes() as $databox) {
                    foreach ($databox->get_collections() as $collection) {
                        $all_base_ids[] = $collection->get_base_id();
                    }
                }

                $userACL = $ACLProvider->get($userUA);
                $userACL->revoke_access_from_bases($all_base_ids)->revoke_unused_sbas_rights();
                $this->debug(sprintf("revoked from=%s \n", var_export($all_base_ids, true)));
            }
        }

        // here we should have a user

        if ($userUA) {
            $this->debug(sprintf("User id=%s \n", $userUA->getId()));

           // apply groups
            if (is_array($data['_groups'])) {

                $userACL = $ACLProvider->get($userUA);

                $models = [];

                // change groups to models
                foreach ($data['_groups'] as $grp) {
                    $models[] = ['name' => $this->config['model-gpfx'] . $grp, 'autocreate' => true];
                }

                // add "everyone-group"
                if(array_key_exists('everyone-group', $this->config)) {
                    $models[] = ['name' => $this->config['model-gpfx'] . $this->config['everyone-group'], 'autocreate' => true];
                }

                // add a specific model for the user
                $models[] = ['name' => $this->config['model-upfx'] . $login, 'autocreate' => false];

                $this->debug(sprintf("models=%s \n", var_export($models, true)));

                // if we need those (in case of creation of a model), they will be set only once
                $metaModelUA = $metaModelBASES = $metaModelOwnerUA = null;

                foreach ($models as $model) {

                    $this->debug(sprintf("searching model '%s' \n", $model['name']));

                    // we check if the model exits
                    $modelUA = $userRepository->findByLogin($model['name']);

                    if (!$modelUA) {
                        if ($model['autocreate'] == true) {
                            $this->debug(sprintf("model '%s' not found \n", $model['name']));

                            // the model does not exist, so create it
                            //
                            // if not already known, get the metamodel
                            if ($metaModelUA === null) {

                                $this->debug(sprintf("searching metamodel '%s'... \n", $this->config['metamodel']));

                                $metaModelUA = $userRepository->findByLogin($this->config['metamodel']);

                                if ($metaModelUA) {

                                    $this->debug(sprintf("metaModelID=%s \n", print_r($metaModelUA->getId(), true)));

                                    // metamodel found, get some infos...
                                    // ... get acl
                                    $metaModelACL = $ACLProvider->get($metaModelUA);
                                    // ... then list of bases
                                    $metaModelBASES = $metaModelACL->get_granted_base();
                                    // ... in fact we simply need an array of base_ids, and base_id is the keys of the array, so switch
                                    $metaModelBASES = array_keys($metaModelBASES);

                                    if ($metaModelUA->isTemplate()) {
                                        $metaModelOwnerUA = $metaModelUA->getTemplateOwner();

                                        $this->debug(sprintf("metamodel is a model, owner_id=%s \n", print_r($metaModelOwnerUA->getId(), true)));
                                    }

                                    $this->debug(sprintf("metamodel granted on bases '%s' \n", print_r($metaModelBASES, true)));
                                }
                                else {
                                    $this->debug("metamodel not found \n");

                                    $metaModelUA = false;   // don't search again
                                }
                            }

                            // now we can create the model only if we found the metamodel
                            if ($metaModelUA) {

                                $this->debug(sprintf("creating model '%s'... \n", $model['name']));

                                // create the model user...
                                $modelUA = $userManipulator->createUser($model['name'], 'model_pwd', null, false);

                                $this->debug(sprintf("model '%s' created with modelID=%s... \n", $model['name'], print_r($modelUA->getId(), true)));

                                if ($metaModelOwnerUA) {
                                    $modelUA->setTemplateOwner($metaModelOwnerUA);

                                    $this->debug(sprintf("model '%s' set as model, owner_id=%s... \n", $model['name'], print_r($metaModelOwnerUA->getId(), true)));
                                }

                                // ... then copy acl of every sbas
                                $modelACL = $ACLProvider->get($modelUA);
                                $modelACL->apply_model($metaModelUA, $metaModelBASES);

                                $this->debug(sprintf(" ... and granted on bases %s \n", print_r($metaModelBASES, true)));
                            }
                        }
                    }
                    else {
                        // the model already exists
                        $this->debug(sprintf("model '%s' already exists, id=%s \n", $model['name'], print_r($modelUA->getId(), true)));
                    }

                    // here we should have the model, except "user" models which are not automatically created

                    if ($modelUA) {
                        $this->debug(sprintf(" ... modelID=%s \n", print_r($modelUA->getId(), true)));

                        // here we have the model so get some infos about it
                        $modelACL = $ACLProvider->get($modelUA);
                        $modelBASES = $modelACL->get_granted_base();
                        // ... in fact we simply need an array of base_ids, and base_id is the keys of the array, so switch
                        $modelBASES = array_keys($modelBASES);

                        $this->debug(sprintf("model granted on bases '%s' \n", print_r($modelBASES, true)));

                        // ... then copy acl of every sbas
                        $userACL->apply_model($modelUA, $modelBASES);

                        $this->debug(sprintf("user '%s' granted on bases %s \n", $login, print_r($modelBASES, true)));
                    }
                    else {
                        $this->debug(sprintf("no model '%s' \n", $model['name']));
                    }
                }

                $userACL->inject_rights();
            }

            // now update infos of the user
            if (!is_null($data['firstname']) && ($v = trim($data['firstname'])) != '') {
                $userUA->setFirstName($v);
            }
            if (!is_null($data['firstname']) && ($v = trim($data['lastname'])) != '') {
                $userUA->setLastName($v);
            }

            $mail = "";     // mail is a special case
            try {
                if (($v = trim($data['email'])) != '') {
                    $mail = $v;
                }
            }
            catch (Exception $e) {
                // no-op
            }

            if ($mail != $userUA->getEmail()) {
                try {
                    $this->debug("unsetting former email of user");
                    $userManipulator->setEmail($userUA, null);
                    if ($mail != "") {
                        $this->debug(sprintf("setting email '%s' to user", $mail));
                        $dupUserUA = $userRepository->findByEmail($mail);
                        if ($dupUserUA == null) {
                            // ok we can set the mail
                            $userManipulator->setEmail($userUA, $mail);
                            $this->debug(sprintf("email '%s' set to user", $mail));
                        }
                        else {
                            $this->debug(sprintf("warning : another user (id=%s) already has email '%s', email not set", $dupUserUA->getId(), $mail));
                        }
                    }
                }
                catch (Exception $e) {
                    // no-op
                    $this->debug(var_export($e->getMessage(), true));
                }
            }
            else {
                $this->debug(sprintf("email '%s' does not change\n", $mail));
            }

            // yes we are logged !
            /** @var RandomGenerator $randomGenerator */
            $randomGenerator = $this->getRandomGenerator();
            $password = $randomGenerator->generateString(16);
            $userUA->setPassword($password);

            $this->debug(sprintf("returning user id=%s", $userUA->getId()));

            $ret = $userUA; // ->getId();
        }

        return $ret;
    }



    /**
     * {@inheritdoc}
     */
    public function getIconURI()
    {
        return $this->iconUri ?: 'data:image/png;base64,'
            . 'iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAAXNSR0IArs4c6QAA'
            . 'AJZlWElmTU0AKgAAAAgABQESAAMAAAABAAEAAAEaAAUAAAABAAAASgEbAAUAAAAB'
            . 'AAAAUgExAAIAAAARAAAAWodpAAQAAAABAAAAbAAAAAAAAABIAAAAAQAAAEgAAAAB'
            . 'QWRvYmUgSW1hZ2VSZWFkeQAAAAOgAQADAAAAAQABAACgAgAEAAAAAQAAADCgAwAE'
            . 'AAAAAQAAADAAAAAAXukGzAAAAAlwSFlzAAALEwAACxMBAJqcGAAAActpVFh0WE1M'
            . 'OmNvbS5hZG9iZS54bXAAAAAAADx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6'
            . 'bWV0YS8iIHg6eG1wdGs9IlhNUCBDb3JlIDUuNC4wIj4KICAgPHJkZjpSREYgeG1s'
            . 'bnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgt'
            . 'bnMjIj4KICAgICAgPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIKICAgICAg'
            . 'ICAgICAgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIgog'
            . 'ICAgICAgICAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYv'
            . 'MS4wLyI+CiAgICAgICAgIDx4bXA6Q3JlYXRvclRvb2w+QWRvYmUgSW1hZ2VSZWFk'
            . 'eTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4x'
            . 'PC90aWZmOk9yaWVudGF0aW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAg'
            . 'PC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KKS7NPQAADE5JREFUaAXtWQuMVcUZ/uY8'
            . '7vvui+WNAYHa2oqKiw/SWnaJ0ipqH4ZHmjTVGMFao00LVm2Dl9YXtVETpVYSX6m2'
            . 'tYhajU210V0KPqi7CALKrggUWeS9uOzufZzH9PvPvXdZ1t27rJCUpM5y7pyZM2fm'
            . '+/7/m3/ODMAX6QsLHJcF1HG9XeJlraEaFsOUJrW3w1MKukTzk+tR4zzYvRHpeli9'
            . '605E+YR2Wp+CVbC2o+9DtF1bd6EyodsTiV+qup1p8QpBG/SGdyLAn7A+dAqGgC92'
            . '2H4vrubVqpcaWi+v0u1/qWjteC7+o+Jz8QbJGMXy8eTHNQfEok3zYU1ZBkdAtN2L'
            . 'WkNhSVkE52VyQE4rB9XlCCe1HU76SDvu24hgQWx6+g1prxsptRq4xzM/uq0mHQ4m'
            . 'ic45sAB3Dt2P8crF3VEbs0kA7Wm4nLIG/2yxs+Nq3+30/Xg5LoDlrc6tMv/o2t5t'
            . 'agp2ypjiEVXHdz5HGrQH6mup8wZGFY67ewHisSrrNm25N5fFYBG4L3h4BdEHZIjh'
            . 'ZTCiZBPyoGzPM8O+ClV7hpfx00rpO40OLBHwBUmpwc6PY9ZhoHOCr2ugywmy7ebk'
            . '1eForCUZMm5TviHgxYLSXx58MWiKiVS+oAw+U9pwO+CYpo4aFbjDT2KzXosrCdwX'
            . '8CIrGYtvHVOS7ksmDq2a5tVQ5015nV87YpoXcu+tLPfOFcyO4Toq7FnKJi9THFBI'
            . 'gln0NFI84AceMEKFPOzBsHyJSa4RosxCgHcYr5kWFqjJWCc9HOv8KElAp1KGSqUC'
            . 'VG1zx41zTf+eaNybY1IOncpxKQlDhVyDOZiLRGj/AokCATUq2S2hQEYkYZKAsrrJ'
            . 'SkjVRpxRLAP4Hh6m+RdxfuwPiGjNsKu6G0tdz9Svq4rg3wSiu6446zdZL95cZlhz'
            . 'utKm39FleBSBxcuAY0E7ZvdFIRzpX26DS/woF/8FubDrTiI5y++E63vUVxl+rFW8'
            . 'Jdd4xo3SQsBrneoXZ4/RujuELrD+aO3aKZWrX3mx7MVbR+5PUxFjznJgdNjKygK0'
            . 'JGVDy8vkpPwLeXfZIEhKSI1OHOWBQEbiAXneOwWVY1wDO2wk+NBYuGlH1Q2zxw4Z'
            . '+34RU+9X+mS2uK4uqP/9jQtOX521R7Ze9XgmcuF8rbeut732PQwzFYyNR6wuHvhM'
            . 'ueAJCURi9fzF4QsTujcQqJHwfV8ZLsFH6vw269HM3/wZX1u88tUp0nZxQ0OfWPus'
            . 'REND0H+6eUv65YUL8MhjK+y1Y85U6rqlCI/+JvytG+HniEyXUTr8SiuQERI9iUCz'
            . 'jVha/BxcQiRPKBgg+BkCX1cD7iew7CgyQ36Hf2RvMua8q+3vNjbhvcO7u6RZw8qV'
            . 'R17pcdc3gdraoIkqLzOGTr4A5oZm/eoNP8ELqzag9fzvIzT3bsbDELzWZmivnCEk'
            . 'XiCSnw9wC/OCYsh3JCR6EAkqE/TkGE7aA7CM/TAqb8Fa+ync1DwCl6zbhFzmE315'
            . 'PI6kbfeNMd/zQPHWh9vRBb8qqSomfx2fPvES/nnNXXhrZxpdM3+FyPSFUAdb4B3c'
            . 'zvBRDe2GSMQIPFL0Rn7iitXF9Jw3BqWnBHgHTL0TVvlV2Jb4E+7cWYOaxq1o3P8R'
            . 'ZnJJt1RYpX0GnwE++wb8lJBgTXGSSBrhSWMRN018suRhtJ9xNk676jKMnf0oQh+8'
            . 'DmfD01BVxJicSAIHKC9a3OYk5ut56wv4EZRLK2yPXxCJ6Thgz8WLe4bgF9tbEXYP'
            . '4tJwhN8mUXQGSwRDk7w7QBqQgLwvWCSiaMeF52pEzpkEK5PBfxb8Fu3f+RZOmVmH'
            . 'IRPPBdY9A6f1LRjDRwgTzgcSURJxyikXmtIl+MRQZBIL8fqB8bi7ZR9Wd6zDxYkw'
            . '14Y40lrCfWAyGfaYUkl9BStMj24CIiz7/NTUlEL4vDPhrWvBtnkPYPPbu9BxzrUI'
            . 'X7QYhrsb/r4dbDiMchkOn+WItQ/msFvwjv8g5q2pxMxVa5HrbMXFsRhysoYF4HsM'
            . 'doy3x+YBdiZOkNQz97uylE0C9sgKpP/wGpoffxtDbrkEo+qeQGRXI5yWh2DTqKFh'
            . '1+BDcxqe3ODhzub3MDySRV1ZElnKsYvytAyDKpM5kh9jML+lCdAF2hLI0rM+CnxQ'
            . 'psA1AeiuHIwpwxmZNA4sfhIHp07C6DnTMGr6Q2gbpfD8riSuf+dDTsj9mFpVDicU'
            . 'x6eeRogh1mT3RaMMBnixbUkJFRvJAMVBjs5ZEl3JTHU8rg0Edf4ERLv2YuNPF+Hl'
            . 'lgTu2jgS1z/9Ciabh3B2sgxtXOAckj7K2MVOiwMOIi/tgV4d5X1wtCdk7Py0I3iq'
            . 'OZMJ4f3E6Wg44zyM35eFjnTwa2oiDnQoSqULUUtmFu1W4F7sTfo5ilSvsfsrDkgg'
            . 'b5ziMEc8EQAQCXFYWzM6cfStoTFYExqJXaEo/M4OxLkGHabOpW0mV4bOQ2Wo1mmU'
            . 'l3XRaR6r5Tvu88A+QmdAAtI0T6KQsyDzTURgMjRavNtjVaMpPAbvW0lEKakK7eEQ'
            . '32I4p7EJMhSGHbbY3kRbV4IS8lBV0U6CJCL9ayHy+dKABIrgi93LtknsblMuh1U5'
            . '1tLq79pVjPUWyn1uhbnt4naFLfKLmBIP2CFuYLhz4TPLkpU4gkOHy+H5WZSV7UfY'
            . '4qcue8xv5nqPWBy577wkAVGr2F3A5JcYDkO5uASw0RyB9+yh6DAiqCJwiUCOMrne'
            . '5YVR8BcUtw0IhWBaNmyGS4tlk5cQcbwY2g9XgSdHiMV3wzA4Z7QQCfwSjD7QT0kC'
            . '8nJgD5rcDERj4GN+L2w2hmK3iqOS0YTRnNtKERLtJzGNN6TBm7y2xTPiAYtX3gN5'
            . 'AiY9Y/ESMo4zGumuEYhEdpPoTr4rpgt398GbflNJAlxeGPR4oqB91YY4tmEItusk'
            . 'EqxNqiyxUkjEabIsH2vSWj45fFlVC8t24IGAgM3N2hHQliVE8gQsMjfMMNeUr9AA'
            . 'p1COW9nHTpbZTYntpLAqSYCvR3IMjs1uzNmCpEGLqXKdpX19ZBUlUbAyTAHP3sTw'
            . 'dJnPWc7NifRPYDKJ6QHOAd0tISFCAkeREDIMtWY164dzA7bXUZGPTTe7MxJ0NC34'
            . '/cyPDPnZ1NAQjH5I2f+u74x+sM6NRGzfUSE/62R9VzvUvOdxLvgOL94Hl1fIpezz'
            . 'YpikCXt6wBYZ2SQe5LyXeUFiIcmDujClplzbtlTFsHMiMVywNxadtFEA1tbenrdI'
            . 'L7R9EkjRiLyMZ7aub9m4682vluvMz3Ke86lLzbiegHbcAHxAgkSCOgEuJLgiS+5K'
            . 'nO9BIABbBNqTSKHOCnkk4cUrhlkk5bpdB5fErAnj//69metSPB1J9SOlfiVUJCH5'
            . 'ptY37p844sKnckjfYZuheaZvWYTNnbySI4+8EeRzopA8TnlXCNADBqUik1gsbHJN'
            . 'OKJ7kRClxAMiw7K8SDxpyzOd63rWsIxbl3/7lC3SXYoHDP2Bl+d9ekAeSEoRPDPF'
            . 'vZK9ZfeqfZtb/zXfcbwpWS9Tz7MOHk15BiXk0BtaJNVTSq7j5CVEnUcI3gqkc8QD'
            . 'VsjWlmU7oWjMSFQOpXCcd7WXu+jZS8fOEvDzHmm02YEqBV4w9usBeVhIugnBqZxR'
            . 'U1NjNjXVN7F++lnjZszxffOeiG2NE9nwkE1O7oL/2DB9A27GDaKReKBCPCCTOIg6'
            . 'gRdcy7StaEW17XV17PHSnYtWXH7qMhmvtr7eGrpvn142e4qD+VJTOpX0QK9X/aam'
            . 'JmcWZjGspIz12199Jhfb8eWcm77d9XI8KOL88Hk25bvctPHIMSsEuCaTQDyQEK1P'
            . 'qeR1PlR07ut0572VVYkvrbhsLMFrJeAb6urc5bNn59fQXgD6Kg6GQPD+cixn5ym/'
            . 'trbW2rRpU65p68u/9nx9WtZN/5maN/j9Y9IjdIdLleXnQNQO+ZSQE0lWmNFEuamz'
            . 'mecYhU9fcfm4mx/7xtDDgVy4pRHwfYEsVTdoAsXOGhoaZDBFWdlNW1/asebDF37g'
            . 'ec60nJt5h9X8aHYNHz4PIC0nFosZcerc8Jz12vdmPH/FqVc+P3NCS01B58vmUy7/'
            . '25QyxCNFDDOm/vCaCbh096Kf36d/u+aAnrq8ee/cl7ZdV3w+66/alOhSLJ80eYFE'
            . 'MZ7G70gtffCBlduXYslmHlHkk+i8eH/S5jU184Jo1BNgIBdqq2fdSX6fMmbNmmWK'
            . 'XMCV9CQH+wW8/18L/BeSV1YkHS6B9wAAAABJRU5ErkJggg==';
    }

    public function getAccessToken()
    {
        return $this->session->get($this->getId() . '.provider.access_token');
    }

    public function getUserName()
    {
        return $this->session->get($this->getId() . ".provider.username");
    }

}
