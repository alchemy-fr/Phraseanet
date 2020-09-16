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

        return $this->render("prod/WorkZone/ExposeList.html.twig", [
            'publications' => $publications,
        ]);
    }

    public function createPublicationAction(PhraseaApplication $app, Request $request)
    {
        try {
            $records = RecordsRequest::fromRequest($app, $request);
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message'   => 'An error occured when wanting to create publication!'
            ]);
        }

        $exposeName = $request->get('exposeName');

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

            if ($response->getStatusCode() !==201) {
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
                        'contents'  => $publicationsResponse['id'],

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
