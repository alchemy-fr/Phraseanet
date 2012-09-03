<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Root implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function(Application $app, Request $request) {

                    $Core = $app['phraseanet.core'];
                    $appbox = $app['phraseanet.appbox'];
                    $user = $Core->getAuthenticatedUser();

                    \User_Adapter::updateClientInfos(3);

                    $section = $request->query->get('section', false);

                    $available = array(
                        'connected',
                        'registrations',
                        'taskmanager',
                        'base',
                        'bases',
                        'collection',
                        'user',
                        'users'
                    );

                    $feature = 'connected';
                    $featured = false;
                    $position = explode(':', $section);
                    if (count($position) > 0) {
                        if (in_array($position[0], $available)) {
                            $feature = $position[0];

                            if (isset($position[1])) {
                                $featured = $position[1];
                            }
                        }
                    }

                    $databoxes = $off_databoxes = array();
                    foreach ($appbox->get_databoxes() as $databox) {
                        try {
                            if ( ! $user->ACL()->has_access_to_sbas($databox->get_sbas_id())) {
                                continue;
                            }

                            $databox->get_connection();
                        } catch (\Exception $e) {
                            $off_databoxes[] = $databox;
                            continue;
                        }

                        $databoxes[] = $databox;
                    }

                    $params = array(
                        'feature'       => $feature,
                        'featured'      => $featured,
                        'databoxes'     => $databoxes,
                        'off_databoxes' => $off_databoxes
                    );


                    return new Response($app['twig']->render('admin/index.html.twig', array(
                                'module'        => 'admin',
                                'events'        => \eventsmanager_broker::getInstance($appbox, $Core),
                                'module_name'   => 'Admin',
                                'notice'        => $request->query->get("notice"),
                                'feature'       => $feature,
                                'featured'      => $featured,
                                'databoxes'     => $databoxes,
                                'off_databoxes' => $off_databoxes,
                                'tree'          => $app['twig']->render('admin/tree.html.twig', $params),
                            ))
                    );
                })
            ->bind('admin');

        $controllers->get('/tree/', function(Application $app, Request $request) {
                    $Core = $app['phraseanet.core'];
                    $appbox = $app['phraseanet.appbox'];
                    $user = $Core->getAuthenticatedUser();

                    \User_Adapter::updateClientInfos(3);

                    $section = $request->query->get('section', false);

                    $available = array(
                        'connected',
                        'registrations',
                        'taskmanager',
                        'base',
                        'bases',
                        'collection',
                        'user',
                        'users'
                    );

                    $feature = 'connected';
                    $featured = false;

                    $position = explode(':', $request->query->get('position', false));
                    if (count($position) > 0) {
                        if (in_array($position[0], $available)) {
                            $feature = $position[0];

                            if (isset($position[1])) {
                                $featured = $position[1];
                            }
                        }
                    }

                    $databoxes = $off_databoxes = array();
                    foreach ($appbox->get_databoxes() as $databox) {
                        try {
                            if ( ! $user->ACL()->has_access_to_sbas($databox->get_sbas_id())) {
                                continue;
                            }

                            $databox->get_connection();
                        } catch (\Exception $e) {
                            $off_databoxes[] = $databox;
                            continue;
                        }

                        $databoxes[] = $databox;
                    }

                    $params = array(
                        'feature'       => $feature,
                        'featured'      => $featured,
                        'databoxes'     => $databoxes,
                        'off_databoxes' => $off_databoxes
                    );

                    return $app['twig']->render('admin/tree.html.twig', $params);
                })
            ->bind('admin_display_tree');

        $controllers->get('/test-paths/', function(Application $app, Request $request) {

                if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
                    $app->abort(400, _('Bad request format, only JSON is allowed'));
                }

                if (0 !== count($tests = $request->query->get('tests', array()))) {

                    $app->abort(400, _('Missing tests parameter'));
                }

                if (null !== $path = $request->query->get('path')) {

                    $app->abort(400, _('Missing path parameter'));
                }

                foreach ($tests as $test) {
                    switch ($test) {
                        case 'writeable':
                            if ( ! is_writable($path)) {
                                $result = false;
                            }
                            break;
                        case 'readable':
                        default:
                            if ( ! is_readable($path)) {
                                $result = true;
                            }
                            break;
                    }
                }

                return $app->json(array('results' => $result));
            });

        $controllers->get('/structure/{databox_id}/', function(Application $app, Request $request, $databox_id) {
                    if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_sbas($databox_id, 'bas_modify_struct')) {
                        $app->abort(403);
                    }

                    $databox = $app['phraseanet.appbox']->get_databox((int) $databox_id);
                    $structure = $databox->get_structure();
                    $errors = \databox::get_structure_errors($structure);

                    if ($updateOk = ! ! $request->query->get('success', false)) {
                        $updateOk = true;
                    }

                    if (false !== $errorsStructure = $request->get('error', false)) {
                        $errorsStructure = true;
                    }

                    return new Response($app['twig']->render('admin/structure.html.twig', array(
                                'databox'         => $databox,
                                'errors'          => $errors,
                                'structure'       => $structure,
                                'errorsStructure' => $errorsStructure,
                                'updateOk'        => $updateOk
                            )));
                })
            ->assert('databox_id', '\d+')
            ->bind('database_display_stucture');

        $controllers->post('/structure/{databox_id}/', function(Application $app, Request $request, $databox_id) {
                    if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_sbas($databox_id, 'bas_modify_struct')) {
                        $app->abort(403);
                    }

                    if (null === $structure = $request->request->get('structure')) {
                        $app->abort(400, _('Missing "structure" parameter'));
                    }

                    $errors = \databox::get_structure_errors($structure);

                    $domst = new \DOMDocument('1.0', 'UTF-8');
                    $domst->preserveWhiteSpace = false;
                    $domst->formatOutput = true;

                    if (count($errors) == 0 && $domst->loadXML($structure)) {
                        $databox = $app['phraseanet.appbox']->get_databox($databox_id);
                        $databox->saveStructure($domst);

                        return $app->redirect('/admin/structure/' . $databox_id . '/?success=1');
                    } else {

                        return $app->redirect('/admin/structure/' . $databox_id . '/?success=0&error=struct');
                    }
                })
            ->assert('databox_id', '\d+')
            ->bind('database_submit_stucture');

        $controllers->get('/statusbit/{databox_id}/', function(Application $app, Request $request, $databox_id) {
                    if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_sbas($databox_id, 'bas_modify_struct')) {
                        $app->abort(403);
                    }

                    return new Response($app['twig']->render('admin/statusbit.html.twig', array(
                                'databox' => $app['phraseanet.appbox']->get_databox($databox_id),
                            )));
                })
            ->assert('databox_id', '\d+')
            ->bind('database_display_statusbit');

        $controllers->get('/statusbit/{databox_id}/status/{bit}/', function(Application $app, Request $request, $databox_id, $bit) {
                    if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_sbas($databox_id, 'bas_modify_struct')) {
                        $app->abort(403);
                    }

                    $databox = $app['phraseanet.appbox']->get_databox($databox_id);

                    $status = $databox->get_statusbits();

                    switch ($errorMsg = $request->query->get('error')) {
                        case 'rights':
                            $errorMsg = _('You do not enough rights to update status');
                            break;
                        case 'too-big':
                            $errorMsg = _('File is too big : 64k max');
                            break;
                        case 'upload-error':
                            $errorMsg = _('Status icon upload failed : upload error');
                            break;
                        case 'wright-error':
                            $errorMsg = _('Status icon upload failed : can not write on disk');
                            break;
                        case 'unknow-error':
                            $errorMsg = _('Something wrong happend');
                            break;
                    }

                    return new Response($app['twig']->render('admin/statusbit/edit.html.twig', array(
                                'status' => isset($status[$bit]) ? $status[$bit] : array(),
                                'errorMsg' => $errorMsg
                            )));
                })
            ->assert('databox_id', '\d+')
            ->assert('bit', '\d+')
            ->bind('database_display_statusbit_form');

        $controllers->post('/statusbit/{databox_id}/status/{bit}/delete/', function(Application $app, Request $request, $databox_id, $bit) {
                if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
                    $app->abort(400, _('Bad request format, only JSON is allowed'));
                }

                $error = false;

                try {
                    \databox_status::deleteStatus($databox_id, $bit);
                } catch (\Exception $e) {
                    $error = true;
                }

                return $app->json(array('success' => ! $error));
            })->assert('databox_id', '\d+')->assert('bit', '\d+');

        $controllers->post('/statusbit/{databox_id}/status/{bit}/', function(Application $app, Request $request, $databox_id, $bit) {
                    if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_sbas($databox_id, 'bas_modify_struct')) {
                        $app->abort(403);
                    }

                    $properties = array(
                        'searchable' => $request->request->get('searchable') ? '1' : '0',
                        'printable'  => $request->request->get('printable') ? '1' : '0',
                        'name'       => $request->request->get('name', ''),
                        'labelon'    => $request->request->get('label_on', ''),
                        'labeloff'   => $request->request->get('label_off', '')
                    );

                    \databox_status::updateStatus($databox_id, $bit, $properties);

                    if (null !== $request->request->get('delete_icon_off')) {
                        \databox_status::deleteIcon($databox_id, $bit, 'off');
                    }

                    if (null !== $file = $request->files->get('image_off')) {
                        try {
                            \databox_status::updateIcon($databox_id, $bit, 'off', $file);
                        } catch (\Exception_Forbidden $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=rights');
                        } catch (\Exception_InvalidArgument $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=unknow-error');
                        } catch (\Exception_Upload_FileTooBig $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=too-big');
                        } catch (\Exception_Upload_Error $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=upload-error');
                        } catch (\Exception_Upload_CannotWriteFile $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=wright-error');
                        } catch (\Exception $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=unknow-error');
                        }
                    }

                    if (null !== $request->request->get('delete_icon_on')) {
                        \databox_status::deleteIcon($databox_id, $bit, 'on');
                    }

                    if (null !== $file = $request->files->get('image_on')) {
                        try {
                            \databox_status::updateIcon($databox_id, $bit, 'on', $file);
                        } catch (\Exception_Forbidden $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=rights');
                        } catch (\Exception_InvalidArgument $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=unknow-error');
                        } catch (\Exception_Upload_FileTooBig $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=too-big');
                        } catch (\Exception_Upload_Error $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=upload-error');
                        } catch (\Exception_Upload_CannotWriteFile $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=wright-error');
                        } catch (\Exception $e) {

                            return $app->redirect('/admin/statusbit/' . $databox_id . '/status/' . $bit . '/?error=unknow-error');
                        }
                    }

                    return $app->redirect('/admin/statusbit/' . $databox_id . '/?success=1');
                })
            ->assert('databox_id', '\d+')
            ->assert('bit', '\d+')
            ->bind('database_submit_statusbit');

        return $controllers;
    }
}
