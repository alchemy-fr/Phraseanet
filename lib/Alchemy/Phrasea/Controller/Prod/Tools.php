<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Exception\RuntimeException;
use DataURI;
use PHPExiftool\Exception\ExceptionInterface as PHPExiftoolException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Tools implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.tools'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireRight('doctools');
        });

        $controllers->get('/', function (Application $app, Request $request) {

            $records = RecordsRequest::fromRequest($app, $request, false);

            $metadatas = false;
            $record = null;

            if (count($records) == 1) {
                $record = $records->first();
                if (!$record->is_grouping()) {
                    try {
                        $metadatas = $app['exiftool.reader']
                                ->files($record->get_subdef('document')->get_pathfile())
                                ->first()->getMetadatas();
                    } catch (PHPExiftoolException $e) {

                    } catch (\Exception_Media_SubdefNotFound $e) {

                    }
                }
            }

            $var = [
                'records'   => $records,
                'record'    => $record,
                'metadatas' => $metadatas,
            ];

            return $app['twig']->render('prod/actions/Tools/index.html.twig', $var);
        });

        $controllers->post('/rotate/', function (Application $app, Request $request) {
            $return = ['success'      => true, 'errorMessage' => ''];

            $records = RecordsRequest::fromRequest($app, $request, false);

            $rotation = in_array($request->request->get('rotation'), ['-90', '90', '180']) ? $request->request->get('rotation', 90) : 90;

            foreach ($records as $record) {
                foreach ($record->get_subdefs() as $name => $subdef) {
                    if ($name == 'document')
                        continue;

                    try {
                        $subdef->rotate($rotation, $app['media-alchemyst'], $app['mediavorus']);
                    } catch (\Exception $e) {

                    }
                }
            }

            return $app->json($return);
        })->bind('prod_tools_rotate');

        $controllers->post('/image/', function (Application $app, Request $request) {
            $return = ['success' => true];

            $selection = RecordsRequest::fromRequest($app, $request, false, ['canmodifrecord']);

            foreach ($selection as $record) {

                $substituted = false;
                foreach ($record->get_subdefs() as $subdef) {
                    if ($subdef->is_substituted()) {
                        $substituted = true;
                        break;
                    }
                }

                if (!$substituted || $request->request->get('ForceThumbSubstit') == '1') {
                    $record->rebuild_subdefs();
                }
            }

            return $app->json($return);
        })->bind('prod_tools_image');

        $controllers->post('/hddoc/', function (Application $app, Request $request) {
            $success = false;
            $message = $app->trans('An error occured');

            if ($file = $request->files->get('newHD')) {

                if ($file->isValid()) {

                    $fileName = $file->getClientOriginalName();

                    try {

                        $tempoDir = tempnam(sys_get_temp_dir(), 'substit');
                        unlink($tempoDir);
                        mkdir($tempoDir);
                        $tempoFile = $tempoDir . DIRECTORY_SEPARATOR . $fileName;

                        if (false === rename($file->getPathname(), $tempoFile)) {
                            throw new RuntimeException('Error while renaming file');
                        }

                        $record = new \record_adapter(
                            $app,
                            $request->get('sbas_id'),
                            $request->get('record_id')
                        );

                        $media = $app['mediavorus']->guess($tempoFile);

                        $app['subdef.substituer']->substitute($record, 'document', $media);
                        $record->insertTechnicalDatas($app['mediavorus']);
                        $app['phraseanet.metadata-setter']->replaceMetadata($app['phraseanet.metadata-reader']->read($media), $record);

                        $app['phraseanet.logger']($record->get_databox())->log(
                            $record,
                            \Session_Logger::EVENT_SUBSTITUTE,
                            'HD',
                            ''
                        );

                        if ((int) $request->request->get('ccfilename') === 1) {
                            $record->set_original_name($fileName);
                            $app['phraseanet.SE']->updateRecord($record);
                        }
                        unlink($tempoFile);
                        rmdir($tempoDir);
                        $success = true;
                        $message = $app->trans('Document has been successfully substitued');
                    } catch (\Exception $e) {
                        $message = $app->trans('file is not valid');
                    }
                } else {
                    $message = $app->trans('file is not valid');
                }
            } else {
                $app->abort(400, 'Missing file parameter');
            }

            return $app['twig']->render('prod/actions/Tools/iframeUpload.html.twig', [
                'success'   => $success,
                'message'   => $message,
            ]);
        })->bind('prod_tools_hd_substitution');

        $controllers->post('/chgthumb/', function (Application $app, Request $request) {
            $success = false;
            $message = $app->trans('An error occured');

            if ($file = $request->files->get('newThumb')) {

                if ($file->isValid()) {
                    try {
                        $fileName = $file->getClientOriginalName();
                        $tempoDir = tempnam(sys_get_temp_dir(), 'substit');
                        unlink($tempoDir);
                        mkdir($tempoDir);
                        $tempoFile = $tempoDir . DIRECTORY_SEPARATOR . $fileName;

                        if (false === rename($file->getPathname(), $tempoFile)) {
                            throw new RuntimeException('Error while renaming file');
                        }

                        $record = new \record_adapter(
                            $app,
                            $request->get('sbas_id'),
                            $request->get('record_id')
                        );

                        $media = $app['mediavorus']->guess($tempoFile);

                        $app['subdef.substituer']->substitute($record, 'thumbnail', $media);
                        $app['phraseanet.logger']($record->get_databox())->log(
                            $record,
                            \Session_Logger::EVENT_SUBSTITUTE,
                            'thumbnail',
                            ''
                        );

                        unlink($tempoFile);
                        rmdir($tempoDir);
                        $success = true;
                        $message = $app->trans('Thumbnail has been successfully substitued');
                    } catch (\Exception $e) {
                        $message = $app->trans('file is not valid');
                    }
                } else {
                    $message = $app->trans('file is not valid');
                }
            } else {
                $app->abort(400, 'Missing file parameter');
            }

            return $app['twig']->render('prod/actions/Tools/iframeUpload.html.twig', [
                'success'   => $success,
                'message'   => $message,
            ]);
        })->bind('prod_tools_thumbnail_substitution');

        $controllers->post('/thumb-extractor/confirm-box/', function (Application $app, Request $request) {
            $return = ['error'   => false, 'datas'   => ''];
            $template = 'prod/actions/Tools/confirm.html.twig';

            try {
                $record = new \record_adapter($app, $request->request->get('sbas_id'), $request->request->get('record_id'));
                $var = [
                    'video_title'    => $record->get_title()
                    , 'image'          => $request->request->get('image', '')
                ];
                $return['datas'] = $app['twig']->render($template, $var);
            } catch (\Exception $e) {
                $return['datas'] = $app->trans('an error occured');
                $return['error'] = true;
            }

            return $app->json($return);
        });

        $controllers->post('/thumb-extractor/apply/', function (Application $app, Request $request) {
            $return = ['success' => false, 'message' => ''];

            try {
                $record = new \record_adapter($app, $request->request->get('sbas_id'), $request->request->get('record_id'));

                $dataUri = DataURI\Parser::parse($request->request->get('image', ''));

                $path = $app['root.path'] . '/tmp';

                $name = sprintf('extractor_thumb_%s', $record->get_serialize_key());

                $fileName = sprintf('%s/%s.png', $path, $name);

                file_put_contents($fileName, $dataUri->getData());

                $media = $app['mediavorus']->guess($fileName);

                $app['subdef.substituer']->substitute($record, 'thumbnail', $media);
                $app['phraseanet.logger']($record->get_databox())->log(
                    $record,
                    \Session_Logger::EVENT_SUBSTITUTE,
                    'thumbnail',
                    ''
                );

                unset($media);
                $app['filesystem']->remove($fileName);

                $return['success'] = true;
            } catch (\Exception $e) {
                $return['message'] = $e->getMessage();
            }

            return $app->json($return);
        });

        return $controllers;
    }
}
