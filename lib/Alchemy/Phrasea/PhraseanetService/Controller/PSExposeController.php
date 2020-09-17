<?php

namespace Alchemy\Phrasea\PhraseanetService\Controller;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Twig\PhraseanetExtension;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;

class PSExposeController extends Controller
{
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
        if ( $request->get('exposeName') == null) {
            return $this->render("prod/WorkZone/ExposeList.html.twig", [
                'publications' => [],
            ]);
        }

        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$request->get('exposeName')];

        $exposeClient = new Client(['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false]);

        if (!isset($exposeConfiguration['token'])) {
            $exposeConfiguration = $this->generateAndSaveToken($exposeConfiguration, $request->get('exposeName'));
        }

        if ($exposeConfiguration == null ) {
            return $this->render("prod/WorkZone/ExposeList.html.twig", [
                'publications' => [],
            ]);
        }

        $response = $exposeClient->get('/publications', [
            'headers' => [
                'Authorization' => 'Bearer '. $exposeConfiguration['token'],
                'Content-Type'  => 'application/json'
            ]
        ]);

        $publicationsID = [];
        if ($response->getStatusCode() == 200) {
            $body = json_decode($response->getBody()->getContents(),true);
            $publicationsID = array_column($body['hydra:member'], 'id');
        }

        $publications = [];
        foreach ($publicationsID as $publicationID) {
            $resPublication = $exposeClient->get('/publications/' . $publicationID , [
                'headers' => [
                    'Authorization' => 'Bearer '. $exposeConfiguration['token'],
                    'Content-Type'  => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() == 200) {
                $publications[] = json_decode($resPublication->getBody()->getContents(),true);
            }
        }

        //
        if ($request->get('format') == 'json') {
            return $app->json([
                'publications' => $publications
            ]);
        }

        return $this->render("prod/WorkZone/ExposeList.html.twig", [
            'publications' => $publications,
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

        // TODO: taken account admin config ,acces_token for user or client_credentiels

        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$exposeName];

        $exposeClient = new Client(['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false]);

        try {
            if (!isset($exposeConfiguration['token'])) {
                $exposeConfiguration = $this->generateAndSaveToken($exposeConfiguration, $exposeName);
            }

            $response = $this->postPublication($exposeClient, $exposeConfiguration['token'], json_decode($request->get('publicationData'), true));

            if ($response->getStatusCode() == 401) {
                $exposeConfiguration = $this->generateAndSaveToken($exposeConfiguration, $exposeName);

                $response = $this->postPublication($exposeClient, $exposeConfiguration['token'], json_decode($request->get('publicationData'), true));
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

        // TODO: taken account admin config ,acces_token for user or client_credentiels

        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$exposeName];

        $exposeClient = new Client(['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false]);

        try {
            if (!isset($exposeConfiguration['token'])) {
                $exposeConfiguration = $this->generateAndSaveToken($exposeConfiguration, $exposeName);
            }

            $response = $this->putPublication($exposeClient, $request->get('publicationId'), $exposeConfiguration['token'], json_decode($request->get('publicationData'), true));

            if ($response->getStatusCode() == 401) {
                $exposeConfiguration = $this->generateAndSaveToken($exposeConfiguration, $exposeName);
                $response = $this->putPublication($exposeClient, $request->get('publicationId'), $exposeConfiguration['token'], json_decode($request->get('publicationData'), true));
            }

            if ($response->getStatusCode() !== 201) {
                return $app->json([
                    'success' => false,
                    'message' => "An error occurred when updating publication: status-code " . $response->getStatusCode()
                ]);
            }
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => "An error occurred when updating publication!"
            ]);
        }

        return $app->json([
            'success' => true,
            'message' => "Publication successfully updated!"
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
    public function deletePublication(PhraseaApplication $app, Request $request)
    {
        $exposeName = $request->get('exposeName');

        // TODO: taken account admin config ,acces_token for user or client_credentiels

        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$exposeName];

        $exposeClient = new Client(['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false]);

        try {
            if (!isset($exposeConfiguration['token'])) {
                $exposeConfiguration = $this->generateAndSaveToken($exposeConfiguration, $exposeName);
            }

            $response = $this->removePublication($exposeClient, $request->get('publicationId'), $exposeConfiguration['token']);

            if ($response->getStatusCode() == 401) {
                $exposeConfiguration = $this->generateAndSaveToken($exposeConfiguration, $exposeName);
                $response = $this->removePublication($exposeClient, $request->get('publicationId'), $exposeConfiguration['token']);
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
        if ($publicationId == null) {
            return $app->json([
                'success' => false,
                'message'   => 'Need to give publicationId to add asset in publication!'
            ]);
        }

        try {
            $records = RecordsRequest::fromRequest($app, $request);
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message'   => 'An error occured when wanting to create publication!'
            ]);
        }

        // TODO: taken account admin config ,acces_token for user or client_credentiels

        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$exposeName];

        $exposeClient = new Client(['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false]);

        /** @var \record_adapter $record */
        foreach ($records as $record) {
            try {
                $helpers = new PhraseanetExtension($app);
                $canSeeBusiness = $helpers->isGrantedOnCollection($record->getBaseId(), [\ACL::CANMODIFRECORD]);

                $captionsByfield = $record->getCaption($helpers->getCaptionFieldOrder($record, $canSeeBusiness));

                $description = "<dl>";

                foreach ($captionsByfield as $name => $value) {
                    if ($helpers->getCaptionFieldGuiVisible($record, $name) == 1) {
                        $description .= "<dt>" . $helpers->getCaptionFieldLabel($record, $name). "</dt>";
                        $description .= "<dd>" . $helpers->getCaptionField($record, $name, $value). "</dd>";
                    }
                }

                $description .= "</dl>";

                $databox = $record->getDatabox();
                $caption = $record->get_caption();
                $lat = $lng = null;

                foreach ($databox->get_meta_structure() as $meta) {
                    if (strpos(strtolower($meta->get_name()), 'longitude') !== FALSE  && $caption->has_field($meta->get_name())) {
                        // retrieve value for the corresponding field
                        $fieldValues = $record->get_caption()->get_field($meta->get_name())->get_values();
                        $fieldValue = array_pop($fieldValues);
                        $lng = $fieldValue->getValue();

                    } elseif (strpos(strtolower($meta->get_name()), 'latitude') !== FALSE  && $caption->has_field($meta->get_name())) {
                        // retrieve value for the corresponding field
                        $fieldValues = $record->get_caption()->get_field($meta->get_name())->get_values();
                        $fieldValue = array_pop($fieldValues);
                        $lat = $fieldValue->getValue();

                    }
                }

                $multipartData = [
                    [
                        'name'      => 'file',
                        'contents'  => fopen($record->get_subdef('document')->getRealPath(), 'r')
                    ],
                    [
                        'name'      => 'publication_id',
                        'contents'  => $publicationId,

                    ],
                    [
                        'name'      => 'slug',
                        'contents'  => 'asset_'. $record->getId()
                    ],
                    [
                        'name'      => 'description',
                        'contents'  => $description
                    ]
                ];

                if ($lat !== null) {
                    array_push($multipartData, [
                        'name'      => 'lat',
                        'contents'  => $lat
                    ]);
                }

                if ($lng !== null) {
                    array_push($multipartData, [
                        'name'      => 'lng',
                        'contents'  => $lng
                    ]);
                }

                $response = $exposeClient->post('/assets', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $exposeConfiguration['token']
                    ],
                    'multipart' => $multipartData
                ]);

                if ($response->getStatusCode() !==201) {
                    return $app->json([
                        'success' => false,
                        'message' => "An error occurred when creating asset: status-code " . $response->getStatusCode()
                    ]);
                }

                $assetsResponse = json_decode($response->getBody(),true);

                // add preview sub-definition

                $this->postSubDefinition(
                    $exposeClient,
                    $exposeConfiguration['token'],
                    $record->get_subdef('preview')->getRealPath(),
                    $assetsResponse['id'],
                    'preview',
                    true
                );

                // add thumbnail sub-definition

                $this->postSubDefinition(
                    $exposeClient,
                    $exposeConfiguration['token'],
                    $record->get_subdef('thumbnail')->getRealPath(),
                    $assetsResponse['id'],
                    'thumbnail',
                    false,
                    true
                );


            } catch (\Exception $e) {
                return $app->json([
                    'success' => false,
                    'message' => "An error occurred when creating asset!"
                ]);
            }
        }

        return $app->json([
            'success' => true,
            'message' => count($records) . "added to the publication!"
        ]);
    }

    /**
     * @param $config
     * @param $exposeName
     *
     * @return mixed
     */
    private function generateAndSaveToken($config, $exposeName)
    {
        $oauthClient = new Client();

        try {
            $response = $oauthClient->post($config['expose_base_uri'] . '/oauth/v2/token', [
                'json' => [
                    'client_id'     => $config['client_id'],
                    'client_secret' => $config['client_secret'],
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

        $config['token'] = $tokenBody['access_token'];

        $this->app['conf']->set(['phraseanet-service', 'expose-service', 'exposes', $exposeName], $config);

        return $config;
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

    private function postSubDefinition(Client $exposeClient, $token, $path, $assetId, $subdefName, $isPreview = false, $isThumbnail = false)
    {
        return $exposeClient->post('/sub-definitions', [
            'headers' => [
                'Authorization' => 'Bearer ' .$token
            ],
            'multipart' => [
                [
                    'name'      => 'file',
                    'contents'  => fopen($path, 'r')
                ],
                [
                    'name'      => 'asset_id',
                    'contents'  => $assetId,

                ],
                [
                    'name'      => 'name',
                    'contents'  => $subdefName
                ],
                [
                    'name'      => 'use_as_preview',
                    'contents'  => $isPreview
                ],
                [
                    'name'      => 'use_as_thumbnail',
                    'contents'  => $isThumbnail
                ]
            ]
        ]);
    }

}
