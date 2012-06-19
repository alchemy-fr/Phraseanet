<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Helper;
use DataURI;
use MediaVorus\MediaVorus;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $controllers->get('/', function(Application $app, Request $request) {
                $helper = new Helper\Record\Tools($app['Core'], $request);

                $selection = $helper->get_elements();

                $metadatas = false;

                $record = null;

                if (count($selection) == 1) {

                    $record = reset($selection);

                    if ( ! $record->is_grouping()) {
                        try {

                            $reader = new \PHPExiftool\Reader();

                            $metadatas = $reader
                                    ->files($record->get_subdef('document')->get_pathfile())
                                    ->first()->getMetadatas();
                        } catch (\PHPExiftool\Exception\Exception $e) {

                        } catch (\Exception_Media_SubdefNotFound $e) {

                        }
                    }
                }

                $reader = null;

                $template = 'prod/actions/Tools/index.html.twig';

                $var = array(
                    'helper'    => $helper,
                    'selection' => $selection,
                    'record'    => $record,
                    'metadatas' => $metadatas,
                );

                return new Response($app['Core']->getTwig()->render($template, $var));
            });

        $controllers->post('/rotate/', function(Application $app, Request $request) {
                $return = array('success'      => false, 'errorMessage' => '');

                $helper = new Helper\Record\Tools($app['Core'], $request);

                $rotation = in_array($request->get('rotation'), array('-90', '90', '180')) ? $request->get('rotation', 90) : 90;

                $selection = $helper->get_elements();

                foreach ($selection as $record) {
                    try {
                        $record->rotate_subdefs($rotation);
                        $return['success'] = true;
                    } catch (\Exception $e) {
                        $return['errorMessage'] = $e->getMessage();
                    }
                }

                $json = $app['Core']->getSerializer()->serialize($return, 'json');

                return new Response($json, 200, array('content-type' => 'application/json'));
            });

        $controllers->post('/image/', function(Application $app, Request $request) {
                $return = array('success' => true);

                $helper = new Helper\Record\Tools($app['Core'], $request);

                $selection = $helper->get_elements();

                foreach ($selection as $record) {

                    $substituted = false;
                    foreach ($record->get_subdefs() as $subdef) {
                        if ($subdef->is_substituted()) {
                            $substituted = true;
                            break;
                        }
                    }

                    if ( ! $substituted || $request->get('ForceThumbSubstit') == '1') {
                        $record->rebuild_subdefs();
                    }
                }

                $json = $app['Core']->getSerializer()->serialize($return, 'json');

                return new Response($json, 200, array('content-type' => 'application/json'));
            });

        $controllers->post('/hddoc/', function(Application $app, Request $request) {
                $success = false;
                $errorMessage = "";
                $fileName = null;

                if ($file = $request->files->get('newHD')) {

                    if ($file->isValid()) {

                        $fileName = $file->getClientOriginalName();
                        $size = $file->getClientSize();

                        try {
                            $record = new \record_adapter(
                                    $request->get('sbas_id')
                                    , $request->get('record_id')
                            );

                            $media = $app['Core']['mediavorus']->guess($file);

                            $record->substitute_subdef('document', $media);

                            if ((int) $request->get('ccfilename') === 1) {
                                $record->set_original_name($fileName);
                            }

                            $success = true;
                        } catch (\Exception $e) {
                            $errorMessage = $e->getMessage();
                        }
                    } else {
                        $errorMessage = _('file is not valid');
                    }
                }

                $template = 'prod/actions/Tools/iframeUpload.html.twig';
                $var = array(
                    'success'      => $success
                    , 'fileName'     => $fileName
                    , 'errorMessage' => $errorMessage
                );

                return new Response($app['Core']->getTwig()->render($template, $var));

                /**
                 *
                 */
            });

        $controllers->post('/chgthumb/', function(Application $app, Request $request) {
                $success = false;
                $errorMessage = "";

                if ($file = $request->files->get('newThumb')) {

                    $size = $file->getClientSize();
                    $fileName = $file->getClientOriginalName();

                    if ($size && $fileName && $file->isValid()) {
                        try {
                            $rootPath = $app['Core']->getRegistry()->get('GV_RootPath');
                            $tmpFile = $rootPath . 'tmp/' . $fileName;
                            rename($file->getPathname(), $tmpFile);

                            $record = new \record_adapter(
                                    $request->get('sbas_id')
                                    , $request->get('record_id')
                            );

                            $media = $app['Core']['mediavorus']->guess($file);

                            $record->substitute_subdef('thumbnail', $media);

                            $success = true;
                        } catch (\Exception $e) {
                            $errorMessage = $e->getMessage();
                        }
                    } else {
                        $errorMessage = _('file is not valid');
                    }

                    $template = 'prod/actions/Tools/iframeUpload.html.twig';
                    $var = array(
                        'success'      => $success
                        , 'fileName'     => $fileName
                        , 'errorMessage' => $errorMessage
                    );

                    return new Response($app['Core']->getTwig()->render($template, $var));
                }
            });

        $controllers->post('/thumb-extractor/confirm-box/', function(Application $app, Request $request) {
                $return = array('error'   => false, 'datas'   => '');
                $template = 'prod/actions/Tools/confirm.html.twig';

                try {
                    $record = new \record_adapter($request->get('sbas_id'), $request->get('record_id'));
                    $var = array(
                        'video_title'    => $record->get_title()
                        , 'image'          => $request->get('image', '')
                    );
                    $return['datas'] = $app['Core']->getTwig()->render($template, $var);
                } catch (\Exception $e) {
                    $return['datas'] = _('an error occured');
                    $return['error'] = true;
                }

                $json = $app['Core']->getSerializer()->serialize($return, 'json');

                return new Response($json, 201, array('content-type' => 'application/json'));
            });

        $controllers->post('/thumb-extractor/apply/', function(Application $app, Request $request) {
                $return = array('success' => false, 'message' => '');

                try {
                    $record = new \record_adapter($request->get('sbas_id'), $request->get('record_id'));

                    $dataUri = DataURI\Parser::parse($request->get('image', ''));

                    $path = $app['Core']->getRegistry()->get('GV_RootPath') . 'tmp';

                    $name = sprintf('extractor_thumb_%s', $record->get_serialize_key());

                    $fileName = sprintf('%s/%s.png', $path, $name);

                    file_put_contents($fileName, $dataUri->getData());

                    $media = $app['Core']['mediavorus']->guess(new \SplFileInfo($fileName));

                    $record->substitute_subdef('thumbnail', $media);

                    unset($media);
                    $app['Core']['file-system']->remove($fileName);

                    $return['success'] = true;
                } catch (\Exception $e) {
                    $return['message'] = $e->getMessage();
                }

                $json = $app['Core']->getSerializer()->serialize($return, 'json');

                return new Response($json, 201, array('content-type' => 'application/json'));
            });

        return $controllers;
    }
}
