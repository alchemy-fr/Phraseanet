<?php

namespace Alchemy\Phrasea\PhraseanetService\Controller;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Repositories\PsSettings\Expose;
use Alchemy\Phrasea\Utilities\NetworkProxiesConfiguration;
use Alchemy\Phrasea\WorkerManager\Event\ExposeUploadEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Doctrine\ORM\NonUniqueResultException;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class PSExposeController extends Controller
{
    /**
     * Set access token on session 'password_access_token_' + expose_name
     * Save also login on session 'expose_connected_login_' + expose_name
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function authenticateAction(PhraseaApplication $app, Request $request)
    {
        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$request->request->get('exposeName')];

        if ($exposeConfiguration == null) {
            return $this->app->json([
                'success' => false,
                'message' => 'Please, set configuration in admin!'
            ]);
        }

        $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);
        $clientOptions = ['base_uri' => $exposeConfiguration['auth_base_uri'], 'http_errors' => false];

        $oauthClient = $proxyConfig->getClientWithOptions($clientOptions);

        try {
            $response = $oauthClient->post('/oauth/v2/token', [
                'json' => [
                    'client_id'     => $exposeConfiguration['auth_client_id'],
                    'client_secret' => $exposeConfiguration['auth_client_secret'],
                    'grant_type'    => 'password',
                    'username'      =>  $request->request->get('auth-username'),
                    'password'      =>  $request->request->get('auth-password')      ]
            ]);
        } catch(\Exception $e) {
            return $this->app->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        if ($response->getStatusCode() !== 200) {
            return $this->app->json([
                'success' => false,
                'message' => 'Status code: '. $response->getStatusCode()
            ]);
        }

        $tokenBody = $response->getBody()->getContents();

        $tokenBody = json_decode($tokenBody,true);
        $session = $this->getSession();
        $passSessionName = $this->getPassSessionName($request->request->get('exposeName'));

        $session->set($passSessionName, $tokenBody['access_token']);

        $loginSessionName = $this->getLoginSessionName($request->request->get('exposeName'));
        $session->set($loginSessionName, $request->request->get('auth-username'));

        return $this->app->json([
            'success'       => true,
            'exposeName'    => $request->request->get('exposeName'),
            'exposeLogin'   => $request->request->get('auth-username')
        ]);
    }

    public function logoutAction(PhraseaApplication $app, Request $request)
    {
        $session = $this->getSession();
        $loginSessionName = $this->getLoginSessionName($request->get('exposeName'));
        $passSessionName = $this->getPassSessionName($request->get('exposeName'));

        $session->remove($loginSessionName);
        $session->remove($passSessionName);

        return $app->json([
            'success' => true
        ]);
    }

    /**
     * Add update or delete access control entry (ACE) for a publication
     * "action" param value : "update" or "delete"
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updatePublicationPermissionAction(PhraseaApplication $app, Request $request)
    {
        $exposeClient = $this->getExposeClient($request->get('exposeName'));

        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }
        $accessToken = $this->getAndSaveToken($request->get('exposeName'));

        try {
            $guzzleParams = [
                'headers' => [
                    'Authorization' => 'Bearer '. $accessToken,
                    'Content-Type'  => 'application/json'
                ],
                'json' => $request->get('jsonData')
            ];

            if ($request->get('action') == 'delete') {
                $response = $exposeClient->delete('/permissions/ace', $guzzleParams);
                $message = 'Permission successfully deleted!';
            } else {
                $response = $exposeClient->put('/permissions/ace', $guzzleParams);
                $message = 'Permission successfully updated!';
            }

        } catch(\Exception $e) {
            return $this->app->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        if ($response->getStatusCode() !== 200) {
            return $this->app->json([
                'success' => false,
                'message' => 'Status code: '. $response->getStatusCode()
            ]);
        }

        return $this->app->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     *  Get list of publication
     *  Use param "format=json" to retrieve a json
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return string|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listPublicationAction(PhraseaApplication $app, Request $request)
    {
        if ($request->get('exposeName') == null) {
            return $app->json([
                'twig' => $this->render("prod/WorkZone/ExposeList.html.twig", [
                    'publications' => [],
                ])
            ]);
        }

        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$request->get('exposeName')];

        $session = $this->getSession();
        $passSessionName = $this->getPassSessionName($request->get('exposeName'));

        if (!$session->has($passSessionName) && $exposeConfiguration['connection_kind'] == 'password' && $request->get('format') != 'json') {
             return $app->json([
                'twig'  => $this->render("prod/WorkZone/ExposeOauthLogin.html.twig", [
                    'exposeName' => $request->get('exposeName')
                ])
            ]);
        }

        $accessToken = $this->getAndSaveToken($request->get('exposeName'));

        if ($exposeConfiguration == null ) {
            return $app->json([
                'twig'  =>  $this->render("prod/WorkZone/ExposeList.html.twig", [
                    'publications' => [],
                ])
            ]);
        }

        $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);
        $clientOptions = ['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false];

        $exposeClient = $proxyConfig->getClientWithOptions($clientOptions);

        $response = $exposeClient->get('/publications?flatten=true&order[createdAt]=desc', [
            'headers' => [
                'Authorization' => 'Bearer '. $accessToken,
                'Content-Type'  => 'application/json'
            ]
        ]);

        $exposeFrontBasePath = \p4string::addEndSlash($exposeConfiguration['expose_front_uri']);
        $publications = [];

        if ($response->getStatusCode() == 200) {
            $body = json_decode($response->getBody()->getContents(),true);
            $publications = $body['hydra:member'];
        }

        if ($request->get('format') == 'pub-list') {
            return $app->json([
                'publications' => $publications
            ]);
        }

        $exposeListTwig = $this->render("prod/WorkZone/ExposeList.html.twig", [
            'publications'          => $publications,
            'exposeFrontBasePath'   => $exposeFrontBasePath
        ]);

        // not called on june 2021
        if ($request->get('format') == 'twig') {
            return $exposeListTwig;
        }

        return $app->json([
            'twig'          => $exposeListTwig,
            'exposeName'    => $request->get('exposeName'),
            'exposeLogin'   => $session->get($this->getLoginSessionName($request->get('exposeName')))
        ]);
    }

    /**
     * Require params "exposeName" and "publicationId"
     * optional param "onlyAssets" equal to 1  to return only assets list
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return string
     */
    public function getPublicationAction(PhraseaApplication $app, Request $request)
    {
        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$request->get('exposeName')];

        $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);
        $clientOptions = ['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false];

        $exposeClient = $proxyConfig->getClientWithOptions($clientOptions);

        $accessToken = $this->getAndSaveToken($request->get('exposeName'));

        $publication = [];

        $resPublication = $exposeClient->get('/publications/' . $request->get('publicationId') , [
            'headers' => [
                'Authorization' => 'Bearer '. $accessToken,
                'Content-Type'  => 'application/json'
            ]
        ]);

        if ($resPublication->getStatusCode() != 200) {
            return $app->json([
                'success' => false,
                'message' => "An error occurred when getting publication: status-code " . $resPublication->getStatusCode()
            ]);
        }

        if ($resPublication->getStatusCode() == 200) {
            $publication = json_decode($resPublication->getBody()->getContents(),true);
        }

        if ($request->get('onlyAssets')) {
            return $this->render("prod/WorkZone/ExposePublicationAssets.html.twig", [
                'assets'        => $publication['assets'],
                'publicationId' => $publication['id']
            ]);
        }

        list($permissions, $listUsers, $listGroups) = $this->getPermissions($exposeClient, $request->get('publicationId'), $accessToken);

        return $this->render("prod/WorkZone/ExposeEdit.html.twig", [
            'publication' => $publication,
            'exposeName'  => $request->get('exposeName'),
            'permissions' => $permissions,
            'listUsers'   => $listUsers,
            'listGroups'  => $listGroups
        ]);
    }

    /**
     * @param PhraseaApplication $app
     * @param Request $request
     * @return string
     */
    public function listPublicationPermissionAction(PhraseaApplication $app, Request $request)
    {
        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$request->get('exposeName')];

        $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);
        $clientOptions = ['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false];
        $exposeClient = $proxyConfig->getClientWithOptions($clientOptions);

        $accessToken = $this->getAndSaveToken($request->get('exposeName'));

        list($permissions, $listUsers, $listGroups) = $this->getPermissions($exposeClient, $request->get('publicationId'), $accessToken);

        return $this->render("prod/WorkZone/ExposePermission.html.twig", [
            'permissions' => $permissions,
            'listUsers'   => $listUsers,
            'listGroups'  => $listGroups
        ]);
    }

    /**
     * Require params "exposeName" and "publicationId"
     * optionnal param "page"
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return string|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getPublicationAssetsAction(PhraseaApplication $app, Request $request)
    {
        $page = $request->get('page')?:1;

        $exposeClient = $this->getExposeClient($request->get('exposeName'));

        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }

        $accessToken = $this->getAndSaveToken($request->get('exposeName'));

        $resPublication = $exposeClient->get('/publications/' . $request->get('publicationId') . '/assets?page=' . $page , [
            'headers' => [
                'Authorization' => 'Bearer '. $accessToken,
                'Content-Type'  => 'application/json'
            ]
        ]);

        if ($resPublication->getStatusCode() != 200) {
            return $app->json([
                'success' => false,
                'message' => "An error occurred when getting publication assets: status-code " . $resPublication->getStatusCode()
            ]);
        }

        $pubAssets = [];
        $totalItems = 0;
        if ($resPublication->getStatusCode() == 200) {
            $body = json_decode($resPublication->getBody()->getContents(),true);
            $pubAssets = $body['hydra:member'];
            $totalItems = $body['hydra:totalItems'];
        }

        return $this->render("prod/WorkZone/ExposePublicationAssets.html.twig", [
            'pubAssets'     => $pubAssets,
            'publicationId' => $request->get('publicationId'),
            'totalItems'    => $totalItems,
            'page'          => $page
        ]);
    }

    /**
     * Require params "exposeName"
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listProfileAction(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');
        if ( $exposeName == null) {
            return $app->json([
                'profiles' => [],
                'basePath' => []
            ]);
        }

        $exposeClient = $this->getExposeClient($exposeName);
        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }
        $accessToken = $this->getAndSaveToken($exposeName);

        $profiles = [];
        $basePath = '';

        $resProfile = $exposeClient->get('/publication-profiles' , [
            'headers' => [
                'Authorization' => 'Bearer '. $accessToken,
                'Content-Type'  => 'application/json'
            ]
        ]);

        if ($resProfile->getStatusCode() != 200) {
            return $app->json([
                'success' => false,
                'message' => "An error occurred when getting publication: status-code " . $resProfile->getStatusCode()
            ]);
        }

        if ($resProfile->getStatusCode() == 200) {
            $body = json_decode($resProfile->getBody()->getContents(),true);
            $profiles = $body['hydra:member'];
            $basePath = $body['@id'];
        }

        return $app->json([
            'profiles' => $profiles,
            'basePath' => $basePath
        ]);
    }

    /**
     * Create a publication
     * Require params "exposeName" and "publicationData"
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createPublicationAction(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');
        if ( $exposeName == null) {
            return $app->json([
                'success' => false,
                'message' => "ExposeName required, select one!"
            ]);
        }

        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$exposeName];

        $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);
        $clientOptions = ['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false];

        $exposeClient = $proxyConfig->getClientWithOptions($clientOptions);

        try {
            $accessToken = $this->getAndSaveToken($exposeName);

            $response = $this->postPublication($exposeClient, $accessToken, json_decode($request->get('publicationData'), true));

            if ($response->getStatusCode() == 401) {
                $accessToken = $this->getAndSaveToken($exposeName);

                $response = $this->postPublication($exposeClient, $accessToken, json_decode($request->get('publicationData'), true));
            }

            if ($response->getStatusCode() !== 201) {
                return $app->json([
                    'success' => false,
                    'message' => "An error occurred when creating publication: status-code " . $response->getStatusCode()
                ]);
            }

            $publicationsResponse = json_decode($response->getBody(),true);
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => "An error occurred when creating publication!"
            ]);
        }

        $path = empty($publicationsResponse['slug']) ? $publicationsResponse['id'] : $publicationsResponse['slug'] ;
        $url = \p4string::addEndSlash($exposeConfiguration['expose_front_uri']) . $path;

        $link = "<a style='color:blue;' target='_blank' href='" . $url . "'>" . $url . "</a>";

        return $app->json([
            'success' => true,
            'message' => "Publication successfully created!",
            'link'    => $link
        ]);
    }

    /**
     * Update a publication
     * Require params "exposeName" and "publicationId"
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updatePublicationAction(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');
        $exposeClient = $this->getExposeClient($exposeName);
        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }

        try {
            $accessToken = $this->getAndSaveToken($exposeName);

            $response = $this->putPublication($exposeClient, $request->get('publicationId'), $accessToken, json_decode($request->get('publicationData'), true));

            if ($response->getStatusCode() == 401) {
                $accessToken = $this->getAndSaveToken($exposeName);
                $response = $this->putPublication($exposeClient, $request->get('publicationId'), $accessToken, json_decode($request->get('publicationData'), true));
            }

            if ($response->getStatusCode() !== 200) {
                return $app->json([
                    'success' => false,
                    'message' => "An error occurred when updating publication: status-code " . $response->getStatusCode()
                ]);
            }
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => "An error occurred when updating publication! ". $e->getMessage()
            ]);
        }

        return $app->json([
            'success' => true,
            'message' => "Publication successfully updated!"
        ]);
    }

    /**
     * Update assets positions
     * Require params "exposeName" and "listPositions" of the assets
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updatePublicationAssetsOrderAction(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');

        $exposeClient = $this->getExposeClient($exposeName);

        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }

        try {
            $accessToken = $this->getAndSaveToken($exposeName);
            $listPositions = json_decode($request->get('listPositions'), true);
            foreach ($listPositions as $pubAssetId => $pos) {
                $this->putPublicationAsset($exposeClient, $pubAssetId, $accessToken, ['position' => $pos]);
            }
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => "An error occurred when updating assets order! ". $e->getMessage()
            ]);
        }

        return $app->json([
            'success' => true,
            'message' => "Assets order successfully updated!"
        ]);
    }

    /**
     * Delete a Publication
     * require params "exposeName" and "publicationId"
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deletePublicationAction(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');

        $exposeClient = $this->getExposeClient($exposeName);
        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }

        try {
            $accessToken = $this->getAndSaveToken($exposeName);

            $response = $this->removePublication($exposeClient, $request->get('publicationId'), $accessToken);

            if ($response->getStatusCode() == 401) {
                $accessToken = $this->getAndSaveToken($exposeName);
                $response = $this->removePublication($exposeClient, $request->get('publicationId'), $accessToken);
            }

            if ($response->getStatusCode() !== 204) {
                return $app->json([
                    'success' => false,
                    'message' => "An error occurred when deleting publication: status-code " . $response->getStatusCode()
                ]);
            }
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => "An error occurred when deleting publication!"
            ]);
        }

        return $app->json([
            'success' => true,
            'message' => "Publication successfully deleted!"
        ]);
    }

    /**
     * Delete asset from publication
     * require params "exposeName" ,"publicationId" and "assetId"
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deletePublicationAssetAction(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');

        $exposeClient = $this->getExposeClient($exposeName);

        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }

        try {
            $accessToken = $this->getAndSaveToken($exposeName);

            $response = $this->removeAssetPublication($exposeClient, $request->get('publicationId'), $request->get('assetId'), $accessToken);

            if ($response->getStatusCode() == 401) {
                $accessToken = $this->getAndSaveToken($exposeName);
                $response = $this->removeAssetPublication($exposeClient, $request->get('publicationId'), $request->get('assetId'), $accessToken);
            }

            if ($response->getStatusCode() !== 204) {
                return $app->json([
                    'success' => false,
                    'message' => "An error occurred when deleting asset: status-code " . $response->getStatusCode()
                ]);
            }
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => "An error occurred when deleting asset!"
            ]);
        }

        return $app->json([
            'success' => true,
            'message' => "Asset successfully removed from publication!"
        ]);

    }

    /**
     * Add assets in a publication
     * Require params "lst" , "exposeName" and "publicationId"
     * "lst" is a list of record as "baseId_recordId"
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function addPublicationAssetsAction(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');
        $publicationId = $request->get('publicationId');
        $lst =  $request->get('lst');

        if ($publicationId == null) {
            return $app->json([
                'success' => false,
                'message'   => 'Need to give publicationId to add asset in publication!'
            ]);
        }

        $accessToken = $this->getAndSaveToken($exposeName);

        $this->getEventDispatcher()->dispatch(WorkerEvents::EXPOSE_UPLOAD_ASSETS, new ExposeUploadEvent($lst, $exposeName, $publicationId, $accessToken));

        return $app->json([
            'success' => true,
            'message' => " Record (s) to be added to the publication!"
        ]);
    }

    /**
     * @param PhraseaApplication $app
     * @param Request $request
     * @return string
     */
    public function getDataboxesFieldAction(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');
        $databoxes = $this->getApplicationBox()->get_databoxes();

        $fields = [];
        foreach ($databoxes as $databox) {
            foreach ($databox->get_meta_structure() as $meta) {
                // get databoxID_metaID for the checkbox name
                $fields[$databox->get_viewname()][$meta->get_id()]['id'] = $databox->get_sbas_id().'_'.$meta->get_id();
                $fields[$databox->get_viewname()][$meta->get_id()]['name'] = $meta->get_label($this->app['locale']);
            }
        }

        $actualFieldList = [];
        if (!empty($exposeName)) {
            /** @var Expose $ex */
            $ex = $this->app['ps_settings.expose'];
            $exposeInstance = $ex->getInstance($exposeName);

            $actualFieldList = json_decode($exposeInstance->getFieldList(), true);
        }

        return $this->render('prod/WorkZone/ExposeFieldMapping.html.twig', [
            'fields'            => $fields,
            'exposeName'        => $exposeName,
            'actualFieldList'   => $actualFieldList
        ]);
    }

    /**
     * @param PhraseaApplication $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function saveFieldMappingAction(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');
        $fields = ($request->request->get('fields') === null) ? null : json_encode(array_keys($request->request->get('fields')));

        /** @var Expose $ex */
        $ex = $this->app['ps_settings.expose'];

        $exposeInstance = $ex->getInstance($exposeName);

        if ($exposeInstance === null) {
            try {
                $exposeInstance = $ex->create($exposeName);
            } catch (NonUniqueResultException $e) {
                return $app->json([
                    'success'        => true,
                    'message'        => $e->getMessage()
                ]);
            }
        }

        $exposeInstance->setFieldList($fields);

        return $app->json([
            'success'        => true,
            'message'        => 'Field list saved!'
        ]);
    }

    /**
     * @param Client $exposeClient
     * @param $publicationId
     * @param $accessToken
     * @return array
     */
    private function getPermissions(Client $exposeClient, $publicationId, $accessToken)
    {
        $permissions = [];
        $listUsers = [];
        $listGroups = [];

        $resPermission = $exposeClient->get('/permissions/aces?objectType=publication&objectId=' . $publicationId, [
            'headers' => [
                'Authorization' => 'Bearer '. $accessToken
            ]
        ]);

        if ($resPermission->getStatusCode() == 200) {
            $permissions = json_decode($resPermission->getBody()->getContents(),true);
        }

        $resUsers = $exposeClient->get('/permissions/users', [
            'headers' => [
                'Authorization' => 'Bearer '. $accessToken
            ]
        ]);

        if ($resUsers->getStatusCode() == 200) {
            $listUsers = json_decode($resUsers->getBody()->getContents(),true);
        }

        $resGroups = $exposeClient->get('/permissions/groups', [
            'headers' => [
                'Authorization' => 'Bearer '. $accessToken
            ]
        ]);

        if ($resGroups->getStatusCode() == 200) {
            $listGroups = json_decode($resGroups->getBody()->getContents(),true);
        }

        foreach ($permissions as &$permission) {
            if ($permission['userType'] == 'user') {
                $key = array_search($permission['userId'], array_column($listUsers, 'id'));
                $permission = array_merge($permission, $listUsers[$key]);
                $listUsers[$key]['selected'] = true;
            } elseif ($permission['userType'] == 'group') {
                $key = array_search($permission['userId'], array_column($listGroups, 'id'));
                $permission = array_merge($permission, $listGroups[$key]);
                $listGroups[$key]['selected'] = true;
            }
        }

        return [
            $permissions,
            $listUsers,
            $listGroups
        ];
    }

    /**
     * Get password session name
     *
     * @param $exposeName
     * @return string
     */
    private function getPassSessionName($exposeName)
    {
        $expose_name = str_replace(' ', '_', $exposeName);

        return 'password_access_token_'.$expose_name;
    }

    /**
     * Get login session name
     *
     * @param $exposeName
     * @return string
     */
    private function getLoginSessionName($exposeName)
    {
        $expose_name = str_replace(' ', '_', $exposeName);

        return 'expose_connected_login_'.$expose_name;
    }

    /**
     * Get Token and save in session
     * @param $exposeName
     *
     * @return mixed
     */
    private function getAndSaveToken($exposeName)
    {
        $config = $this->getExposeConfiguration($exposeName);
        $session = $this->getSession();
        $passSessionName = $this->getPassSessionName($exposeName);

        $expose_name = str_replace(' ', '_', $exposeName);
        $credentialSessionName = 'credential_access_token_'.$expose_name;

        $accessToken = '';
        if ($config['connection_kind'] == 'password') {
            $accessToken = $session->get($passSessionName);
        } elseif ($config['connection_kind'] == 'client_credentials') {
            if ($session->has($credentialSessionName)) {
                $accessToken = $session->get($credentialSessionName);
            } else {
                $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);

                $oauthClient = $proxyConfig->getClientWithOptions([]);

                try {
                    $response = $oauthClient->post($config['expose_base_uri'] . '/oauth/v2/token', [
                        'json' => [
                            'client_id'     => $config['expose_client_id'],
                            'client_secret' => $config['expose_client_secret'],
                            'grant_type'    => 'client_credentials',
                            'scope'         => 'publish'
                        ]
                    ]);
                } catch(\Exception $e) {
                    return null;
                }

                if ($response->getStatusCode() !== 200) {
                    return null;
                }

                $tokenBody = $response->getBody()->getContents();

                $tokenBody = json_decode($tokenBody,true);

                $session->set($credentialSessionName, $tokenBody['access_token']);

                $accessToken = $tokenBody['access_token'];
            }
        }

        return $accessToken;
    }

    private function postPublication(Client $exposeClient, $token, $publicationData)
    {
        return $exposeClient->post('/publications', [
            'headers' => [
                'Authorization' => 'Bearer '. $token,
                'Content-Type'  => 'application/json'
            ],
            'json' => $publicationData
        ]);
    }

    private function putPublication(Client $exposeClient, $publicationId, $token, $publicationData)
    {
        return $exposeClient->put('/publications/' . $publicationId, [
            'headers' => [
                'Authorization' => 'Bearer '. $token,
                'Content-Type'  => 'application/json'
            ],
            'json' => $publicationData
        ]);
    }

    private function putPublicationAsset(Client $exposeClient, $publicationAssetId, $token, $publicationAssetData)
    {
        return $exposeClient->put('/publication-assets/' . $publicationAssetId, [
            'headers' => [
                'Authorization' => 'Bearer '. $token,
                'Content-Type'  => 'application/json'
            ],
            'json' => $publicationAssetData
        ]);
    }

    private function removePublication(Client $exposeClient, $publicationId, $token)
    {
        return $exposeClient->delete('/publications/' . $publicationId, [
            'headers' => [
                'Authorization' => 'Bearer '. $token
            ]
        ]);
    }

    private function removeAssetPublication(Client $exposeClient, $publicationId, $assetId, $token)
    {
        return $exposeClient->delete('/assets/'. $assetId, [
            'headers' => [
                'Authorization' => 'Bearer '. $token
            ]
        ]);
    }

    /**
     * @param $exposeName
     * @return Client|null
     */
    private function getExposeClient($exposeName)
    {
        $exposeConfiguration = $this->getExposeConfiguration($exposeName);
        if ($exposeConfiguration === null) {
            return null;
        }

        $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);
        $clientOptions = ['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false];

        return $proxyConfig->getClientWithOptions($clientOptions);
    }

    /**
     * @param $exposeName
     * @return array|null
     */
    private function getExposeConfiguration($exposeName)
    {
        $exposeConfiguration = $this->app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        try {
            $exposeConfiguration = $exposeConfiguration[$exposeName];
        } catch (\Exception $e) {
            return null;
        }

        return $exposeConfiguration;
    }

    /**
     * @return EventDispatcherInterface
     */
    private function getEventDispatcher()
    {
        return $this->app['dispatcher'];
    }

    /**
     * @return Session
     */
    private function getSession()
    {
        return $this->app['session'];
    }
}
