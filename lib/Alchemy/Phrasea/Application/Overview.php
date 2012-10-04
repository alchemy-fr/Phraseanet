<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(
        function() {

            $app = new PhraseaApplication();

            $appbox = $app['phraseanet.appbox'];
            $session = $appbox->get_session();

            $deliver_content = function(Request $request, \Session_Handler $session, \record_adapter $record, $subdef, $watermark, $stamp, PhraseaApplication $app) {

                    $file = $record->get_subdef($subdef);

                    $pathIn = $pathOut = $file->get_pathfile();

                    if ($watermark === true && $file->get_type() === \media_subdef::TYPE_IMAGE) {
                        $pathOut = \recordutils_image::watermark($file);
                    } elseif ($stamp === true && $file->get_type() === \media_subdef::TYPE_IMAGE) {
                        $pathOut = \recordutils_image::stamp($file);
                    }

                    $log_id = null;
                    try {
                        $registry = \registry::get_instance();
                        $logger = $session->get_logger($record->get_databox());
                        $log_id = $logger->get_id();

                        $referrer = 'NO REFERRER';

                        if (isset($_SERVER['HTTP_REFERER'])) {
                            $referrer = $_SERVER['HTTP_REFERER'];
                        }

                        $record->log_view($log_id, $referrer, $registry->get('GV_sit'));
                    } catch (\Exception $e) {

                    }

                    $response = \set_export::stream_file($pathOut, $file->get_file(), $file->get_mime(), null === $request->query->get('download') ? 'inline' : 'attachment');
                    $response->setPrivate();

                    /* @var $response \Symfony\Component\HttpFoundation\Response */
                    if ($file->getEtag()) {
                        $response->setEtag($file->getEtag());
                        $response->setLastModified($file->get_modification_date());
                    }

                    $response->headers->addCacheControlDirective('must-revalidate', true);
                    $response->isNotModified($request);

                    return $response;
                };

            $app->get('/datafiles/{sbas_id}/{record_id}/{subdef}/', function($sbas_id, $record_id, $subdef, PhraseaApplication $app) use ($session, $deliver_content) {

                    $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
                    $record = new \record_adapter($sbas_id, $record_id);

                    if (!$session->is_authenticated()) {
                        throw new \Exception_Session_NotAuthenticated();
                    }

                    $stamp = $watermark = $all_access = false;

                    if ($subdef != 'thumbnail') {
                        $subdefStruct = $databox->get_subdef_structure();

                        if ($subdefStruct->getSubdefGroup($record->get_type())) {
                            foreach ($subdefStruct->getSubdefGroup($record->get_type()) as $subdefObj) {
                                if ($subdefObj->get_name() == $subdef) {
                                    if ($subdefObj->get_class() == 'thumbnail') {
                                        $all_access = true;
                                    }
                                    break;
                                }
                            }
                        }

                    $user = \User_Adapter::getInstance($session->get_usr_id(), $app['phraseanet.appbox']);

                        if (!$user->ACL()->has_access_to_subdef($record, $subdef)) {
                            throw new \Exception_UnauthorizedAction();
                        }

                        $watermark = !$user->ACL()->has_right_on_base($record->get_base_id(), 'nowatermark');

                        if ($watermark && !$all_access) {
                            $subdef_class = $databox
                                ->get_subdef_structure()
                                ->get_subdef($record->get_type(), $subdef)
                                ->get_class();

                            if ($subdef_class == \databox_subdef::CLASS_PREVIEW && $user->ACL()->has_preview_grant($record)) {
                                $watermark = false;
                            } elseif ($subdef_class == \databox_subdef::CLASS_DOCUMENT && $user->ACL()->has_hd_grant($record)) {
                                $watermark = false;
                            }
                        }

                        if ($watermark && !$all_access) {

                        $em = $app['phraseanet.core']->getEntityManager();

                            $repository = $em->getRepository('\Entities\BasketElement');

                            /* @var $repository \Repositories\BasketElementRepository */

                            $ValidationByRecord = $repository->findReceivedValidationElementsByRecord($record, $user);
                            $ReceptionByRecord = $repository->findReceivedElementsByRecord($record, $user);

                            if ($ValidationByRecord && count($ValidationByRecord) > 0) {
                                $watermark = false;
                            } elseif ($ReceptionByRecord && count($ReceptionByRecord) > 0) {
                                $watermark = false;
                            }
                        }
                    }

                    return $deliver_content($app['request'], $session, $record, $subdef, $watermark, $stamp, $app);
                })->assert('sbas_id', '\d+')->assert('record_id', '\d+');

            $app->get('/permalink/v1/{label}/{sbas_id}/{record_id}/{key}/{subdef}/view/'
                , function($label, $sbas_id, $record_id, $key, $subdef, PhraseaApplication $app) {

                    $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

                    $record = \media_Permalink_Adapter::challenge_token($databox, $key, $record_id, $subdef);

                    if (!$record instanceof \record_adapter) {
                        throw new \Exception('bad luck');
                    }

                    $params = array(
                        'subdef_name' => $subdef
                        , 'module_name' => 'overview'
                        , 'module'      => 'overview'
                        , 'view'        => 'overview'
                        , 'record'      => $record
                    );

                    return new Response($app['twig']->render('overview.html.twig', $params));
                })->assert('sbas_id', '\d+')->assert('record_id', '\d+');

            $app->get('/permalink/v1/{label}/{sbas_id}/{record_id}/{key}/{subdef}/'
                    , function($label, $sbas_id, $record_id, $key, $subdef) use ($app, $session, $deliver_content) {
                        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
                        $record = \media_Permalink_Adapter::challenge_token($databox, $key, $record_id, $subdef);
                        if (!($record instanceof \record_adapter))
                            throw new \Exception('bad luck');

                        $watermark = $stamp = false;

                        if ($session->is_authenticated()) {
                            $user = \User_Adapter::getInstance($session->get_usr_id(), $app['phraseanet.appbox']);

                            $watermark = !$user->ACL()->has_right_on_base($record->get_base_id(), 'nowatermark');

                            if ($watermark) {

                                $em = $app['phraseanet.core']->getEntityManager();

                                $repository = $em->getRepository('\Entities\BasketElement');

                                if (count($repository->findReceivedValidationElementsByRecord($record, $user)) > 0) {
                                    $watermark = false;
                                } elseif (count($repository->findReceivedElementsByRecord($record, $user)) > 0) {
                                    $watermark = false;
                                }
                            }

                            return $deliver_content($app['request'], $session, $record, $subdef, $watermark, $stamp, $app);
                        } else {
                            $collection = \collection::get_from_base_id($record->get_base_id());
                            switch ($collection->get_pub_wm()) {
                                default:
                                case 'none':
                                    $watermark = false;
                                    break;
                                case 'stamp':
                                    $stamp = true;
                                    break;
                                case 'wm':
                                    $watermark = false;
                                    break;
                            }
                        }

                        return $deliver_content($app['request'], $session, $record, $subdef, $watermark, $stamp, $app);
                    }
                )
                ->assert('sbas_id', '\d+')->assert('record_id', '\d+');

            $app->error(function (\Exception $e) {
                    if ($e instanceof \Exception_Session_NotAuthenticated) {
                        $code = 403;
                        $message = 'Forbidden';
                    } elseif ($e instanceof \Exception_NotAllowed) {
                        $code = 403;
                        $message = 'Forbidden';
                    } elseif ($e instanceof \Exception_NotFound) {
                        $code = 404;
                        $message = 'Not Found';
                    } else {
                        $code = 404;
                        $message = 'Not Found';
                    }

                    return new Response($message, $code, array('X-Status-Code' => $code));
                });

            return $app;
        }
);
