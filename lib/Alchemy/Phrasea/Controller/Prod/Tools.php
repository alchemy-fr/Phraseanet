<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Exception\RuntimeException;
use DataURI;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Tools implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireRight('doctools');
        });

        $controllers->get('/', function (Application $app, Request $request) {

            $records = RecordsRequest::fromRequest($app, $request, false);

            $metadatas = false;
            $record = null;
            $recordAccessibleSubdefs = array();

            if (count($records) == 1) {
                /** @var \record_adapter $record */
                $record = $records->first();

                // fetch subdef list:
                $subdefs = $record->get_subdefs();

                /** @var \ACL $acl */
                $acl = $app['authentication']->getUser()->ACL();

                if ($acl->has_right('bas_chupub')
                    && $acl->has_right_on_base($record->get_base_id(), 'canmodifrecord')
                    && $acl->has_right_on_base($record->get_base_id(), 'imgtools')
                ) {
                    $databoxSubdefs = $record->get_databox()->get_subdef_structure()->getSubdefGroup($record->get_type());

                    foreach ($subdefs as $subdef) {
                        $label = $subdefName = $subdef->get_name();
                        if (null === $permalink = $subdef->get_permalink()) {
                            continue;
                        }

                        if ('document' == $subdefName) {
                            $label = _('prod::tools: document');
                        } elseif (isset($databoxSubdefs[$subdefName])) {
                            if (!$acl->has_access_to_subdef($record, $subdefName)) {
                                continue;
                            }

                            $label = $databoxSubdefs[$subdefName]->get_label($app['locale.I18n']);
                        }

                        $recordAccessibleSubdefs[] = array(
                            'name'  => $subdefName,
                            'state' => $permalink->get_is_activated(),
                            'label' => $label,
                        );
                    }
                }

                if (!$record->is_grouping()) {
                    try {
                        $metadatas = $app['exiftool.reader']
                                ->files($record->get_subdef('document')->get_pathfile())
                                ->first()->getMetadatas();
                    } catch (\PHPExiftool\Exception\Exception $e) {

                    } catch (\Exception_Media_SubdefNotFound $e) {

                    }
                }
            }

            $var = array(
                'records'   => $records,
                'record'    => $record,
                'recordSubdefs' => $recordAccessibleSubdefs,
                'metadatas' => $metadatas,
            );

            return $app['twig']->render('prod/actions/Tools/index.html.twig', $var);
        });

        $controllers->post('/rotate/', function (Application $app, Request $request) {
            $records = RecordsRequest::fromRequest($app, $request, false);
            $rotation = in_array($request->request->get('rotation'), array('-90', '90', '180')) ? $request->request->get('rotation', 90) : 90;

            foreach ($records as $record) {
                foreach ($record->get_subdefs() as $subdef) {
                    if ($subdef->get_type() !== \media_subdef::TYPE_IMAGE) {
                        continue;
                    }

                    try {
                        $subdef->rotate($rotation, $app['media-alchemyst'], $app['mediavorus']);
                    } catch (\Exception $e) {
                    }
                }
            }

            return $app->json(array('success' => true, 'errorMessage' => ''));
        })->bind('prod_tools_rotate');

        $controllers->post('/image/', function (Application $app, Request $request) {
            $return = array('success' => true);

            $force = $request->request->get('force_substitution') == '1';

            $selection = RecordsRequest::fromRequest($app, $request, false, array('canmodifrecord'));

            foreach ($selection as $record) {
                $substituted = false;
                foreach ($record->get_subdefs() as $subdef) {
                    if ($subdef->is_substituted()) {
                        $substituted = true;

                        if ($force) {
                            // unset flag
                            $subdef->set_substituted(false);
                        }
                        break;
                    }
                }

                if (!$substituted || $force) {
                    $record->rebuild_subdefs();
                }
            }


            return $app->json($return);
        })->bind('prod_tools_image');

        $controllers->post('/hddoc/', function (Application $app, Request $request) {
            $success = false;
            $message = _('An error occured');

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

                        $record->substitute_subdef('document', $media, $app);
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
                        $message = _('Document has been successfully substitued');
                    } catch (\Exception $e) {
                        $message = _('file is not valid');
                    }
                } else {
                    $message = _('file is not valid');
                }
            } else {
                $app->abort(400, 'Missing file parameter');
            }

            return $app['twig']->render('prod/actions/Tools/iframeUpload.html.twig', array(
                'success'   => $success,
                'message'   => $message,
            ));
        })->bind('prod_tools_hd_substitution');

        $controllers->post('/chgthumb/', function (Application $app, Request $request) {
            $success = false;
            $message = _('An error occured');

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

                        $record->substitute_subdef('thumbnail', $media, $app);
                        $app['phraseanet.logger']($record->get_databox())->log(
                            $record,
                            \Session_Logger::EVENT_SUBSTITUTE,
                            'thumbnail',
                            ''
                        );

                        unlink($tempoFile);
                        rmdir($tempoDir);
                        $success = true;
                        $message = _('Thumbnail has been successfully substitued');
                    } catch (\Exception $e) {
                        $message = _('file is not valid');
                    }
                } else {
                    $message = _('file is not valid');
                }
            } else {
                $app->abort(400, 'Missing file parameter');
            }

            return $app['twig']->render('prod/actions/Tools/iframeUpload.html.twig', array(
                'success'   => $success,
                'message'   => $message,
            ));
        })->bind('prod_tools_thumbnail_substitution');

        $controllers->post('/thumb-extractor/confirm-box/', function (Application $app, Request $request) {
            $return = array('error'   => false, 'datas'   => '');
            $template = 'prod/actions/Tools/confirm.html.twig';

            try {
                $record = new \record_adapter($app, $request->request->get('sbas_id'), $request->request->get('record_id'));
                $var = array(
                    'video_title'    => $record->get_title()
                    , 'image'          => $request->request->get('image', '')
                );
                $return['datas'] = $app['twig']->render($template, $var);
            } catch (\Exception $e) {
                $return['datas'] = _('an error occured');
                $return['error'] = true;
            }

            return $app->json($return);
        });

        $controllers->post('/thumb-extractor/apply/', function (Application $app, Request $request) {
            try {
                $record = new \record_adapter($app, $request->request->get('sbas_id'), $request->request->get('record_id'));

                $subDef = $request->request->get('sub_def');

                // legacy handling
                if (!is_array($subDef)) {
                    $subDef = array('name' => 'thumbnail', 'src' => $request->request->get('image', ''));
                }

                foreach ($subDef as $def) {

                    $dataUri = DataURI\Parser::parse($def['src']);

                    $path = $app['root.path'] . '/tmp';

                    $name = sprintf('extractor_thumb_%s', $record->get_serialize_key());

                    $fileName = sprintf('%s/%s.png', $path, $name);

                    file_put_contents($fileName, $dataUri->getData());

                    $media = $app['mediavorus']->guess($fileName);

                    $record->substitute_subdef($def['name'], $media, $app);

                    $app['phraseanet.logger']($record->get_databox())->log(
                      $record,
                      \Session_Logger::EVENT_SUBSTITUTE,
                      $def['name'],
                      ''
                    );

                    unset($media);

                    $app['filesystem']->remove($fileName);
                }

                $return = array('success' => true, 'message' => '');
            } catch (\Exception $e) {
                $return = array('success' => false, 'message' => $e->getMessage());
            }

            return $app->json($return);
        });

        /**
         * Edit share state of the record
         *
         * name         : export_multi_export
         *
         * description  : Display edit_record_sharing export
         *
         * method       : POST
         *
         * parameters   : base_id, record_id
         *
         * data params  : name, state
         *
         * return       : JSON Response
         */
        $controllers->post('/sharing-editor/{base_id}/{record_id}/', $this->call('editRecordSharing'))
          ->bind('edit_record_sharing');

        return $controllers;
    }

    /**
     * Edit a record share state
     * @param \Alchemy\Phrasea\Application $app
     * @param Request $request
     * @param $base_id
     * @param $record_id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function editRecordSharing(\Alchemy\Phrasea\Application $app, Request $request, $base_id, $record_id)
    {
        $record = new \record_adapter($app, \phrasea::sbasFromBas($app, $base_id), $record_id);
        $subdefName = (string)$request->request->get('name');
        $state = $request->request->get('state') == 'true' ? true : false;

        /** @var \ACL $acl */
        $acl = $app['authentication']->getUser()->ACL();

        if (!$acl->has_right('bas_chupub')
            || !$acl->has_right_on_base($record->get_base_id(), 'canmodifrecord')
            || !$acl->has_right_on_base($record->get_base_id(), 'imgtools')
        ) {
            $app->abort(403);
        }

        $subdef = $record->get_subdef($subdefName);

        $permalink = $subdef->get_permalink();
        $permalink->set_is_activated($state);

        $changedState = $permalink->get_is_activated();

        return $app->json(array('success' => true, 'state' => $changedState), 200);
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }

}
