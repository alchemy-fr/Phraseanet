<?php

namespace Alchemy\Phrasea\PhraseanetService\Controller;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Utilities\NetworkProxiesConfiguration;
use Alchemy\Phrasea\WorkerManager\Event\ExposeUploadEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
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
                'error_description' => 'Please, set configuration in admin!'
            ]);
        }

        $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);
        $clientOptions = [
            'base_uri'      => $exposeConfiguration['auth_base_uri'],
            'http_errors'   => false,
            'verify'        => $exposeConfiguration['verify_ssl']
        ];

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
                'error_description' => $e->getMessage()
            ]);
        }

        if ($response->getStatusCode() !== 200) {
            try {
                $b = json_decode($response->getBody()->getContents(),true);
                $message = $b['error_description'];
            } catch (\Exception $e) {
                $message = 'Error with status code: ' . $response->getStatusCode();
            }

            return $this->app->json([
                'success' => false,
                'error_description'   => $message
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
        $exposeName = $request->get('exposeName');
        if ($exposeName == null) {
            return $app->json([
                'twig' => $this->render("prod/WorkZone/ExposeList.html.twig", [
                    'publications' => [],
                ])
            ]);
        }

        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$exposeName];

        $session = $this->getSession();
        $passSessionName = $this->getPassSessionName($exposeName);
        $providerId = $session->get('auth_provider.id');

        if (!$session->has($passSessionName) && $providerId != null) {
            try {
                $provider = $this->getAuthenticationProviders()->get($providerId);
                if ($provider->getType() == 'PsAuth') {
                    $session->set($passSessionName, $provider->getAccessToken());
                    $session->set($this->getLoginSessionName($exposeName), $provider->getUserName());
                }
            } catch(\Exception $e) {
            }
        }

        if (!$session->has($passSessionName) && $exposeConfiguration['connection_kind'] == 'password' && $request->get('format') != 'json') {
             return $app->json([
                'twig'  => $this->render("prod/WorkZone/ExposeOauthLogin.html.twig", [
                    'exposeName' => $exposeName
                ])
            ]);
        }

        $accessToken = $this->getAndSaveToken($exposeName);

        if ($exposeConfiguration == null ) {
            return $app->json([
                'twig'  =>  $this->render("prod/WorkZone/ExposeList.html.twig", [
                    'publications' => [],
                ])
            ]);
        }

        $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);
        $clientOptions = [
            'base_uri'      => $exposeConfiguration['expose_base_uri'],
            'http_errors'   => false,
            'verify'        => $exposeConfiguration['verify_ssl']
        ];

        $exposeClient = $proxyConfig->getClientWithOptions($clientOptions);

        try {
            $uri = '/publications?flatten=true&order[createdAt]=desc';
            if ($request->get('mine') && $exposeConfiguration['connection_kind'] === 'password') {
                $uri .= '&mine=true';
            }

            if ($request->get('editable')) {
                $uri .= '&editable=true';
            }

            $response = $exposeClient->get($uri, [
                'headers' => [
                    'Authorization' => 'Bearer '. $accessToken,
                    'Content-Type'  => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() == 200) {
                $body = @json_decode($response->getBody()->getContents(),true);

                if (!isset($body['hydra:member']) || !isset($body['@id'])) {
                    throw new \Exception("index undefined on response body!");
                }
                $publications = $body['hydra:member'];
                $basePath = $body['@id'];
            } else {
                throw new \Exception("Error with status code : " . $response->getStatusCode());
            }

        } catch(\Exception $e) {
            return $app->json([
                'success' => false,
                'publications' => [],
                'basePath'     => [],
                'error'   => $e->getMessage()
            ]);
        }

        $exposeFrontBasePath = \p4string::addEndSlash($exposeConfiguration['expose_front_uri']);

        if ($request->get('format') == 'pub-list') {
            return $app->json([
                'publications' => $publications,
                'basePath'     => $basePath
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
            'exposeName'    => $exposeName,
            'exposeLogin'   => $session->get($this->getLoginSessionName($exposeName)),
            'basePath'      => $basePath
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
        $clientOptions = [
            'base_uri'      => $exposeConfiguration['expose_base_uri'],
            'http_errors'   => false,
            'verify'        => $exposeConfiguration['verify_ssl']
        ];

        $exposeClient = $proxyConfig->getClientWithOptions($clientOptions);

        $accessToken = $this->getAndSaveToken($request->get('exposeName'));

        $publication = [];

        try {
            $resPublication = $exposeClient->get('/publications/' . $request->get('publicationId') , [
                'headers' => [
                    'Authorization' => 'Bearer '. $accessToken,
                    'Content-Type'  => 'application/json'
                ]
            ]);
        } catch(\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

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
            'timezone'    => $request->get('timezone'),
            'publication' => $publication,
            'exposeName'  => $request->get('exposeName'),
            'permissions' => $permissions,
            'listUsers'   => $listUsers,
            'listGroups'  => $listGroups
        ]);
    }

    /**
     * Require params "exposeName" and "slug"
     *
     * @param PhraseaApplication $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function checkPublicationSlugAction(PhraseaApplication $app, Request $request)
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
            $resAvailability = $exposeClient->get('/publications/slug-availability/' . $request->get('slug') , [
                'headers' => [
                    'Authorization' => 'Bearer '. $accessToken,
                ]
            ]);
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        if ($resAvailability->getStatusCode() != 200) {
            return $app->json([
                'success' => false,
                'message' => "An error occurred when checking slug availability : " . $resAvailability->getStatusCode()
            ]);
        }

        return $app->json([
            'success'   => true,
            'isAvailable' => json_decode($resAvailability->getBody()->getContents()),
            'message'   => ''
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
        $clientOptions = [
            'base_uri'      => $exposeConfiguration['expose_base_uri'],
            'http_errors'   => false,
            'verify'        => $exposeConfiguration['verify_ssl']
        ];

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
        $exposeName = $request->get('exposeName');
        $exposeConfiguration = $this->getExposeConfiguration($exposeName);
        $page = $request->get('page')?:1;

        $exposeClient = $this->getExposeClient($exposeName);

        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }

        $accessToken = $this->getAndSaveToken($exposeName);

        try {
            $resPublication = $exposeClient->get('/publications/' . $request->get('publicationId') . '/assets?page=' . $page , [
                'headers' => [
                    'Authorization' => 'Bearer '. $accessToken,
                    'Content-Type'  => 'application/json'
                ]
            ]);
        } catch(\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        if ($resPublication->getStatusCode() != 200) {
            return $app->json([
                'success' => false,
                'message' => "An error occurred when getting publication assets: status-code " . $resPublication->getStatusCode()
            ]);
        }

        $assets = [];
        $totalItems = 0;
        if ($resPublication->getStatusCode() == 200) {
            $body = json_decode($resPublication->getBody()->getContents(),true);
            $assets = $body['hydra:member'];

            if (!empty($assets) && isset($assets[0]['asset'])) {
                // expose v1 BC: flatten assets
                $assets = array_map(function (array $pubAsset): array {
                    return $pubAsset['asset'];
                }, $assets);
            }

            $totalItems = $body['hydra:totalItems'];
        }

        $exposeFrontBasePath = \p4string::addEndSlash($exposeConfiguration['expose_front_uri']);

        return $this->render('prod/WorkZone/ExposePublicationAssets.html.twig', [
            'assets'                => $assets,
            'publicationId'         => $request->get('publicationId'),
            'capabilitiesDelete'    => $request->get('capabilitiesDelete'),
            'capabilitiesEdit'      => $request->get('capabilitiesEdit'),
            'enabled'               => $request->get('enabled'),
            'childrenCount'         => $request->get('childrenCount'),
            'totalItems'            => $totalItems,
            'page'                  => $page,
            'exposeFrontBasePath'   => $exposeFrontBasePath
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

        try {
            $resProfile = $exposeClient->get('/publication-profiles' , [
                'headers' => [
                    'Authorization' => 'Bearer '. $accessToken,
                    'Content-Type'  => 'application/json'
                ]
            ]);
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }


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
        $clientOptions = [
            'base_uri'      => $exposeConfiguration['expose_base_uri'],
            'http_errors'   => false,
            'verify'        => $exposeConfiguration['verify_ssl']
        ];

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
     * Require params "exposeName", "publicationId" and "order" of the assets
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
            $exposeClient->post(sprintf('/publications/%s/sort-assets', $request->get('publicationId', [])), [
                'headers' => [
                    'Authorization' => 'Bearer '. $accessToken,
                    'Content-Type'  => 'application/json'
                ],
                'json' => [
                    'order' => $request->get('order', []),
                ],
            ]);
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
        $profile = $request->get('profile');

        $exposeClient = $this->getExposeClient($exposeName);
        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }

        $exposeMappingName = $this->getExposeMappingName('field');
        try {
            $clientAnnotationProfile = $this->getClientAnnotationProfile($exposeClient, $exposeName, $profile);

            $fieldMapping = !empty($clientAnnotationProfile[$exposeMappingName]) ? $clientAnnotationProfile[$exposeMappingName] : [];

            $actualFieldsList = !empty($fieldMapping['fields']) ? $fieldMapping['fields'] : [];
            $fields = ($profile != null) ? $this->getFields($actualFieldsList) : [];
        } catch (\Exception $e) {

        }

        // send geoloc and send vtt checked by default if not setting
        return $this->render('prod/WorkZone/ExposeFieldList.html.twig', [
            'fields'            => $fields,
            'sendGeolocField'   => isset($fieldMapping['sendGeolocField']) ? $fieldMapping['sendGeolocField'] : null,
            'sendVttField'      => isset($fieldMapping['sendVttField']) ? $fieldMapping['sendVttField'] : null,
        ]);
    }

    public function getSubdefsListAction(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');
        $profile = $request->get('profile');

        $exposeClient = $this->getExposeClient($exposeName);
        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }

        try {
            $clientAnnotationProfile = $this->getClientAnnotationProfile($exposeClient, $exposeName, $profile);
        } catch(\Exception $e) {
        }

        $exposeMappingName = $this->getExposeMappingName('subdef');
        $actualSubdefMapping = !empty($clientAnnotationProfile[$exposeMappingName]) ? $clientAnnotationProfile[$exposeMappingName] : [];

        $databoxes = empty($profile)? [] : $this->getApplicationBox()->get_databoxes();

        return $this->render('prod/WorkZone/ExposeSubdefList.html.twig', [
            'databoxes'            => $databoxes,
            'actualSubdefMapping'  => $actualSubdefMapping
        ]);
    }

    public function getFieldMappingAction(PhraseaApplication $app, Request $request)
    {
        return $this->render('prod/WorkZone/ExposeFieldMapping.html.twig', [
            'exposeName'    => $request->get('exposeName')
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
        $profile = $request->request->get('profile');
        $sendGeolocField = !empty($request->request->get('sendGeolocField')) ? array_keys($request->request->get('sendGeolocField')): [];
        $sendVttField = !empty($request->request->get('sendVttField')) ? array_keys($request->request->get('sendVttField')) : [];

        $fields = ($request->request->get('fields') === null) ? null : array_keys($request->request->get('fields'));

        $fieldMapping = [
            'sendGeolocField'   => $sendGeolocField,
            'sendVttField'      => $sendVttField,
            'fields'            => $fields
        ];

        $fieldMapping = [
            $this->getExposeMappingName('field') => $fieldMapping
        ];

        if ($exposeName == null || $profile == null) {
            return $app->json([
                'success' => false,
                'message' => "Choose an expose and a profile !"
            ]);
        }

        $exposeClient = $this->getExposeClient($exposeName);
        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }

        $clientAnnotationProfile = [];

        try {
            // get the actual value and merge it with the new one before save
            $clientAnnotationProfile = $this->getClientAnnotationProfile($exposeClient, $exposeName, $profile);
        } catch (\Exception $e) {
        }

        $annotationValues = array_merge($clientAnnotationProfile, $fieldMapping);
        $accessToken = $this->getAndSaveToken($exposeName);

        try {
            // save field mapping in the selected profile
            $resProfile = $exposeClient->put($profile , [
                'headers' => [
                    'Authorization' => 'Bearer '. $accessToken,
                    'Content-Type'  => 'application/json'
                ],
                'json'  => [
                    'clientAnnotations' => json_encode($annotationValues)
                ]
            ]);
        } catch(\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        if ($resProfile->getStatusCode() !== 200) {
            return $app->json([
                'success' => false,
                'message' => "Error when saving mapping with status code: " . $resProfile->getStatusCode()
            ]);
        }

        return $app->json([
            'success'            => true,
            'clientAnnotations'  => $annotationValues
        ]);
    }

    public function saveSubdefMappingAction(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');
        $profile = $request->request->get('profile');
        $exposeClient = $this->getExposeClient($exposeName);
        $subdefs = $request->request->get('subdefs');

        if ($exposeClient == null) {
            return $app->json([
                'success' => false,
                'message' => "Expose configuration not set!"
            ]);
        }

        $subdefs = [
            $this->getExposeMappingName('subdef') => $subdefs
        ];

        try {
            // get the actual value and merge it with the new one before save
            $clientAnnotationProfile = $this->getClientAnnotationProfile($exposeClient, $exposeName, $profile);

            $annotationValues = array_merge($clientAnnotationProfile, $subdefs);

            $accessToken = $this->getAndSaveToken($exposeName);

            // save subdef mapping in the selected profile
            $resProfile = $exposeClient->put($profile , [
                'headers' => [
                    'Authorization' => 'Bearer '. $accessToken,
                    'Content-Type'  => 'application/json'
                ],
                'json'  => [
                    'clientAnnotations' => json_encode($annotationValues)
                ]
            ]);
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        if ($resProfile->getStatusCode() !== 200) {
            return $app->json([
                'success' => false,
                'message' => "Error when saving mapping with status code: " . $resProfile->getStatusCode()
            ]);
        }

        return $app->json([
            'success'            => true,
            'clientAnnotations'  => $subdefs
        ]);
    }

    /**
     * Get client annotation from the expose profile
     *
     * @param Client $exposeClient
     * @param $exposeName
     * @param $profileRoute
     * @return array|mixed
     */
    private function getClientAnnotationProfile(Client $exposeClient, $exposeName, $profileRoute)
    {
        $accessToken = $this->getAndSaveToken($exposeName);

        $resProfile = $exposeClient->get($profileRoute , [
            'headers' => [
                'Authorization' => 'Bearer '. $accessToken,
            ]
        ]);

        $actualFieldsList = [];
        if ($resProfile->getStatusCode() == 200) {
            $actualFieldsList = json_decode($resProfile->getBody()->getContents(),true);
            $actualFieldsList = (!empty($actualFieldsList['clientAnnotations'])) ? json_decode($actualFieldsList['clientAnnotations'], true) : [];
        }

        return $actualFieldsList;
    }

    /**
     * field | subdef  context
     * @param $mappingContext
     *
     * @return string
     */
    private function getExposeMappingName($mappingContext)
    {
        $phraseanetLocalId = $this->app['conf']->get(['phraseanet-service', 'phraseanet_local_id']);

        return $phraseanetLocalId.'_'. $mappingContext . '_mapping';
    }

    /**
     * get list of field in databoxes
     *
     * @return array
     */
    private function getFields($actualFieldsList)
    {
        $databoxes = $this->getApplicationBox()->get_databoxes();

        $fieldFromProfile = [];
        foreach ($actualFieldsList as $value) {
            $t = explode('_', $value);
            $databox = $this->getApplicationBox()->get_databox($t[0]);
            $viewName = $t[0]. ':::' .$databox->get_viewname();

            $fieldFromProfile[$viewName][$t[1]]['id']      = $value;
            $fieldFromProfile[$viewName][$t[1]]['name']    = $databox->get_meta_structure()->get_element($t[1])->get_label($this->app['locale']);
            $fieldFromProfile[$viewName][$t[1]]['checked'] = true;
        }

        $fields = $fieldFromProfile;
        foreach ($databoxes as $databox) {
            foreach ($databox->get_meta_structure() as $meta) {
                $viewName = $databox->get_sbas_id().':::'.$databox->get_viewname();
                if (!empty($fields[$viewName][$meta->get_id()]) && in_array($databox->get_sbas_id().'_'.$meta->get_id(), $fields[$viewName][$meta->get_id()])) {
                   continue;
                }
                // get databoxID_metaID for the checkbox name
                $fields[$viewName][$meta->get_id()]['id'] = $databox->get_sbas_id().'_'.$meta->get_id();
                $fields[$viewName][$meta->get_id()]['name'] = $meta->get_label($this->app['locale']);
            }
        }

        return $fields;
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
            if ($permission['userType'] == 'user' && !empty($listUsers)) {
                $key = array_search($permission['userId'], array_column($listUsers, 'id'));
                $permission = array_merge($permission, $listUsers[$key]);
                $listUsers[$key]['selected'] = true;
            } elseif ($permission['userType'] == 'group' && !empty($listGroups)) {
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

                $oauthClient = $proxyConfig->getClientWithOptions([
                    'verify' => $config['verify_ssl'],
                ]);

                $response = $oauthClient->post($config['expose_base_uri'] . '/oauth/v2/token', [
                    'json' => [
                        'client_id'     => $config['expose_client_id'],
                        'client_secret' => $config['expose_client_secret'],
                        'grant_type'    => 'client_credentials',
                        'scope'         => 'publish'
                    ]
                ]);

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
        $clientOptions = [
            'base_uri'      => $exposeConfiguration['expose_base_uri'],
            'http_errors'   => false,
            'verify'        => $exposeConfiguration['verify_ssl']
        ];

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

    /**
     * @return ProvidersCollection
     */
    private function getAuthenticationProviders()
    {
        return $this->app['authentication.providers'];
    }
}
