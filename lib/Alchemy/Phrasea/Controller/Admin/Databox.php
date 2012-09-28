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
class Databox implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {

            $response = $app['firewall']->requireAccessToModule('admin')
                ->requireAccessToSbas($request->attributes->get('databox_id'));

            if ($response instanceof Response) {
                return $response;
            }
        });

        /**
         * Get admin database
         *
         * name         : admin_database
         *
         * description  : Get database informations
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{databox_id}/', $this->call('getDatabase'))
            ->assert('databox_id', '\d+')
            ->bind('admin_database');

        /**
         * Delete a database
         *
         * name         : admin_database_delete
         *
         * description  : Delete a database
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/{databox_id}/delete/', $this->call('deleteBase'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_delete');

        /**
         * Unmount a database
         *
         * name         : admin_database_unmount
         *
         * description  : unmount one database
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/{databox_id}/unmount/', $this->call('unmountDatabase'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_unmount');

        /**
         * Empty a database
         *
         * name         : admin_database_empty
         *
         * description  : empty one database
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/{databox_id}/empty/', $this->call('emptyDatabase'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_empty');

        /**
         * Reorder database collection
         *
         * name         : admin_database_display_collections_order
         *
         * description  : Reorder database collection
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{databox_id}/collections/order/', $this->call('getReorder'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_display_collections_order');

        /**
         * Reorder database collection
         *
         * name         : admin_database_submit_collections_order
         *
         * description  : Reorder database collection
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{databox_id}/collections/order/', $this->call('setReorder'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_submit_collections_order');

        /**
         * Create new collection
         *
         * name         : admin_database_submit_collection
         *
         * description  : Create a new collection
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{databox_id}/collection/', $this->call('createCollection'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_submit_collection');

        /**
         * Get database CGU
         *
         * name         : admin_database_display_cgus
         *
         * description  : Get database CGU
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{databox_id}/cgus/', $this->call('getDatabaseCGU'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_modify_struct');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_display_cgus');

        /**
         * Update database CGU
         *
         * name         : admin_database_submit_cgus
         *
         * description  : Update database CGU
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{databox_id}/cgus/', $this->call('updateDatabaseCGU'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_modify_struct');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_submit_cgus');

        /**
         * Update document information
         *
         * name         : admin_database_display_document_information
         *
         * description  : Update document information
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{databox_id}/informations/documents/', $this->call('progressBarInfos'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_display_document_information');

        /**
         * Get document details
         *
         * name         : admin_database_display_document_details
         *
         * description  : Get document details
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{databox_id}/informations/details/', $this->call('getDetails'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_display_document_details');

        /**
         * Mount collection on collection
         *
         * name         : admin_database_mount_collection
         *
         * description  : Mount collection
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{databox_id}/collection/{collection_id}/mount/', $this->call('mountCollection'))
            ->assert('databox_id', '\d+')
            ->assert('collection_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_mount_collection');

        /**
         * Get a new collection form
         *
         * name         : admin_database_display_new_collection_form
         *
         * description  : New collection form
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{databox_id}/collection/', $this->call('getNewCollection'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_display_new_collection_form');

        /**
         * Add databox logo
         *
         * name         : admin_database_submit_logo
         *
         * description  : add logo to databox
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{databox_id}/logo/', $this->call('sendLogoPdf'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_submit_logo');

        /**
         * Delete databox logo
         *
         * name         : admin_database_delete_logo
         *
         * description  : delete logo databox
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{databox_id}/logo/delete/', $this->call('deleteLogoPdf'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_delete_logo');

        /**
         * Clear databox logs
         *
         * name         : admin_database_clear_logs
         *
         * description  : Clear databox logs
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{databox_id}/clear-logs/', $this->call('clearLogs'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_clear_logs');

        /**
         * Reindex database
         *
         * name         : admin_database_reindex
         *
         * description  : Reindex database
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{databox_id}/reindex/', $this->call('reindex'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_reindex');

        /**
         * Set database indexable
         *
         * name         : admin_database_set_indexable
         *
         * description  : Set database indexable
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{databox_id}/indexable/', $this->call('setIndexable'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_set_indexable');

        /**
         * Set database name
         *
         * name         : admin_database_rename
         *
         * description  : Set database indexable
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{databox_id}/view-name/', $this->call('changeViewName'))
            ->assert('databox_id', '\d+')
            ->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');

                if ($response instanceof Response) {
                    return $response;
                }
            })->bind('admin_database_rename');

        return $controllers;
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDatabase(Application $app, Request $request, $databox_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox($databox_id);

        switch ($errorMsg = $request->query->get('error')) {
            case 'file-error':
                $errorMsg = _('Error while sending the file');
                break;
            case 'file-invalid':
                $errorMsg = _('Invalid file format');
                break;
            case 'file-too-big':
                $errorMsg = _('The file is too big');
                break;
        }

        return $app['twig']->render('admin/databox/databox.html.twig', array(
            'databox'    => $databox,
            'showDetail' => (int) $request->query->get("sta") < 1,
            'errorMsg'   => $errorMsg,
            'reloadTree' => $request->query->get('reload-tree') === '1'
        ));
    }

    /**
     * Get databox CGU's
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   Response
     */
    public function getDatabaseCGU(Application $app, Request $request, $databox_id)
    {
        return $app['twig']->render('admin/databox/cgus.html.twig', array(
            'languages'      => $app->getAvailableLanguages(),
            'cgus'           => $app['phraseanet.appbox']->get_databox($databox_id)->get_cgus(),
            'current_locale' => $app['locale']
        ));
    }

    /**
     * Delete a databox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   JsonResponse|RedirectResponse
     */
    public function deleteBase(Application $app, Request $request, $databox_id)
    {
        $success = false;
        $msg = _('An error occured');
        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);

            if ($databox->get_record_amount() > 0) {
                $msg = _('admin::base: vider la base avant de la supprimer');
            } else {
                $databox->unmount_databox($app['phraseanet.appbox']);
                $app['phraseanet.appbox']->write_databox_pic($app['media-alchemyst'], $app['filesystem'], $databox, null, \databox::PIC_PDF);
                $databox->delete();
                $success = true;
                $msg = _('Successful removal');
            }
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success' => $success,
                'msg'     => $msg,
                'sbas_id' => $databox->get_sbas_id()
            ));
        }

        return $app->redirect('/admin/databox/' . $databox->get_sbas_id() . '/?success=' . (int) $success . ($databox->get_record_amount() > 0 ? '&error=databox-not-empty' : ''));
    }

    /**
     * Reindex databox content
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   JsonResponse|RedirectResponse
     */
    public function reindex(Application $app, Request $request, $databox_id)
    {
        $success = false;

        try {
            $app['phraseanet.appbox']->get_databox($databox_id)->reindex();
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success' => $success,
                'msg'     => $success ? _('Successful update') : _('An error occured'),
                'sbas_id' => $databox_id
            ));
        }

        return $app->redirect('/admin/databox/' . $databox_id . '/?success=' . (int) $success);
    }

    /**
     * Make a databox indexable
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   JsonResponse|RedirectResponse
     */
    public function setIndexable(Application $app, Request $request, $databox_id)
    {
        $success = false;

        try {
            $app['phraseanet.appbox']->set_databox_indexable($app['phraseanet.appbox']->get_databox($databox_id), !!$request->request->get('indexable', false));
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success' => $success,
                'msg'     => $success ? _('Successful update') : _('An error occured'),
                'sbas_id' => $databox_id
            ));
        }

        return $app->redirect('/admin/databox/' . $databox_id . '/?success=' . (int) $success);
    }

    /**
     * Update databox CGU's
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   RedirectResponse
     */
    public function updateDatabaseCGU(Application $app, Request $request, $databox_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox($databox_id);

        try {
            foreach ($request->request->get('TOU', array()) as $loc => $terms) {
                $databox->update_cgus($loc, $terms, !!$request->request->get('valid', false));
            }
        } catch (\Exception $e) {
            return $app->redirect('/admin/databox/' . $databox_id . '/cgus/?success=0');
        }

        return $app->redirect('/admin/databox/' . $databox_id . '/cgus/?success=1');
    }

    /**
     * Mount a collection on a databox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   RedirectResponse
     */
    public function mountCollection(Application $app, Request $request, $databox_id, $collection_id)
    {
        $appbox = $app['phraseanet.appbox'];
        $user = $app['phraseanet.user'];

        $appbox->get_connection()->beginTransaction();
        try {
            $baseId = \collection::mount_collection($app, $app['phraseanet.appbox']->get_databox($databox_id), $collection_id, $user);

            if (null == $othCollSel = $request->request->get("othcollsel")) {
                $app->abort(400);
            }

            $query = new \User_Query($app);
            $n = 0;

            while ($n < $query->on_base_ids(array($othCollSel))->get_total()) {
                $results = $query->limit($n, 50)->execute()->get_results();

                foreach ($results as $user) {
                    $user->ACL()->duplicate_right_from_bas($othCollSel, $baseId);
                }

                $n += 50;
            }

            $appbox->get_connection()->commit();

            return $app->redirect('/admin/databox/' . $databox_id . '/?mount=ok');
        } catch (\Exception $e) {
            $appbox->get_connection()->rollBack();

            return $app->redirect('/admin/databox/' . $databox_id . '/?mount=ko');
        }
    }

    /**
     * Set a new logo for a databox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   RedirectResponse
     */
    public function sendLogoPdf(Application $app, Request $request, $databox_id)
    {
        try {
            if (null !== ($file = $request->files->get('newLogoPdf')) && $file->isValid()) {

                if ($file->getClientSize() < 65536) {
                    $databox = $app['phraseanet.appbox']->get_databox($databox_id);
                    $app['phraseanet.appbox']->write_databox_pic($app['media-alchemyst'], $app['filesystem'], $databox, $file, \databox::PIC_PDF);
                    unlink($file->getPathname());

                    return $app->redirect('/admin/databox/' . $databox_id . '/?success=1');
                } else {
                    return $app->redirect('/admin/databox/' . $databox_id . '/?success=0&error=file-too-big');
                }
            } else {
                return $app->redirect('/admin/databox/' . $databox_id . '/?success=0&error=file-invalid');
            }
        } catch (\Exception $e) {
            return $app->redirect('/admin/databox/' . $databox_id . '/??success=0&error=file-error');
        }
    }

    /**
     * Delete an existing logo for a databox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   JsonResponse|RedirectResponse
     */
    public function deleteLogoPdf(Application $app, Request $request, $databox_id)
    {
        $success = false;

        try {
            $app['phraseanet.appbox']->write_databox_pic($app['media-alchemyst'], $app['filesystem'], $app['phraseanet.appbox']->get_databox($databox_id), null, \databox::PIC_PDF);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success' => $success,
                'msg'     => $success ? _('Successful removal') : _('An error occured'),
                'sbas_id' => $databox_id
            ));
        }

        return $app->redirect('/admin/databox/' . $databox_id . '/?success=' . (int) $success);
    }

    /**
     * Clear databox logs
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   JsonResponse|RedirectResponse
     */
    public function clearLogs(Application $app, Request $request, $databox_id)
    {
        $success = false;

        try {
            $app['phraseanet.appbox']->get_databox($databox_id)->clear_logs();
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success' => $success,
                'msg'     => $success ? _('Successful update') : _('An error occured'),
                'sbas_id' => $databox_id
            ));
        }

        return $app->redirect('/admin/databox/' . $databox_id . '/?success=' . (int) $success);
    }

    /**
     * Change the name of a databox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   JsonResponse|RedirectResponse
     */
    public function changeViewName(Application $app, Request $request, $databox_id)
    {
        if (null === $viewName = $request->request->get('viewname')) {
            $app->abort(400, _('Missing view name parameter'));
        }

        $success = false;

        try {
            $app['phraseanet.appbox']->set_databox_viewname($app['phraseanet.appbox']->get_databox($databox_id), $viewName);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success' => $success,
                'msg'     => $success ? _('Successful update') : _('An error occured'),
                'sbas_id' => $databox_id
            ));
        }

        return $app->redirect('/admin/databox/' . $databox_id . '/?success=' . (int) $success);
    }

    /**
     * Unmount a databox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   JsonResponse|RedirectResponse
     */
    public function unmountDatabase(Application $app, Request $request, $databox_id)
    {
        $success = false;

        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);
            $databox->unmount_databox($app['phraseanet.appbox']);

            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success' => $success,
                'msg'     => $success ? _('The publication has been stopped') : _('An error occured'),
                'sbas_id' => $databox_id
            ));
        }

        return $app->redirect('/admin/databox/' . $databox_id . '/?success=' . (int) $success . '&reload-tree=1');
    }

    /**
     * Empty a databox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   JsonResponse|RedirectResponse
     */
    public function emptyDatabase(Application $app, Request $request, $databox_id)
    {
        $msg = _('An error occurred');
        $success = false;

        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);

            foreach ($databox->get_collections() as $collection) {
                if ($collection->get_record_amount() <= 500) {
                    $collection->empty_collection(500);
                    $msg = _('Base empty successful');
                } else {
                    $settings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><tasksettings><base_id>" . $collection->get_base_id() . "</base_id></tasksettings>";
                    \task_abstract::create($app, 'task_period_emptyColl', $settings);
                    $msg = _('A task has been creted, please run it to complete empty collection');
                }
            }

            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success' => $success,
                'msg'     => $msg,
                'sbas_id' => $databox_id
            ));
        }

        return $app->redirect('/admin/databox/' . $databox_id . '/?success=' . (int) $success);
    }

    /**
     * Get number of indexed items for a databox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   JsonResponse
     */
    public function progressBarInfos(Application $app, Request $request, $databox_id)
    {
        if (!$app['request']->isXmlHttpRequest() || 'json' !== $app['request']->getRequestFormat()) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $appbox = $app['phraseanet.appbox'];

        $ret = array(
            'success'           => false,
            'msg'               => _('An error occured'),
            'sbas_id'           => null,
            'indexable'         => false,
            'records'           => 0,
            'xml_indexed'       => 0,
            'thesaurus_indexed' => 0,
            'viewname'          => null,
            'printLogoURL'      => null
        );

        try {
            $databox = $appbox->get_databox($databox_id);
            $datas = $databox->get_indexed_record_amount();

            $ret['indexable'] = $appbox->is_databox_indexable($databox);
            $ret['viewname'] = (($databox->get_dbname() == $databox->get_viewname()) ? _('admin::base: aucun alias') : $databox->get_viewname());
            $ret['records'] = $databox->get_record_amount();
            $ret['sbas_id'] = $databox_id;
            $ret['xml_indexed'] = $datas['xml_indexed'];
            $ret['thesaurus_indexed'] = $datas['thesaurus_indexed'];

            if ($app['filesystem']->exists($app['phraseanet.registry']->get('GV_RootPath') . 'config/minilogos/logopdf_' . $databox_id . '.jpg')) {
                $ret['printLogoURL'] = '/custom/minilogos/logopdf_' . $databox_id . '.jpg';
            }

            $ret['success'] = true;
            $ret['msg'] = _('Successful update');
        } catch (\Exception $e) {

        }

        return $app->json($ret);
    }

    /**
     * Display page for reaorder collections on a databox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   Response
     */
    public function getReorder(Application $app, Request $request, $databox_id)
    {
        return $app['twig']->render('admin/collection/reorder.html.twig', array(
            'databox' => $app['phraseanet.appbox']->get_databox($databox_id),
        ));
    }

    /**
     * Apply collection reorder changes
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   JsonResponse|RedirectResponse
     */
    public function setReorder(Application $app, Request $request, $databox_id)
    {
        $success = false;

        try {
            foreach ($request->request->get('order', array()) as $order => $baseId) {
                $collection = \collection::get_from_base_id($app, $baseId);
                $app['phraseanet.appbox']->set_collection_order($collection, $order);
                unset($collection);
            }

            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success' => $success,
                'msg'     => $success ? _('Successful update') : _('An error occured'),
                'sbas_id' => $databox_id
            ));
        }

        return $app->redirect('/admin/databox/' . $databox_id . '/collections/order?success=' . (int) $success);
    }

    /**
     * Display page to create a new collection
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   Response
     */
    public function getNewCollection(Application $app, Request $request, $databox_id)
    {
        return $app['twig']->render('admin/collection/create.html.twig');
    }

    /**
     * Create a new collection
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   Response
     */
    public function createCollection(Application $app, Request $request, $databox_id)
    {
        if (($name = trim($request->request->get('name', ''))) === '') {

            return $app->redirect('/admin/databox/' . $databox_id . '/collection/error=name');
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);
            $collection = \collection::create($app, $databox, $app['phraseanet.appbox'], $name, $app['phraseanet.user']);

            if (($request->request->get('ccusrothercoll') === "on")
                && ($othcollsel = $request->request->get('othcollsel') !== null)) {
                $query = new \User_Query($app);
                $total = $query->on_base_ids(array($othcollsel))->get_total();
                $n = 0;
                while ($n < $total) {
                    $results = $query->limit($n, 20)->execute()->get_results();
                    foreach ($results as $user) {
                        $user->ACL()->duplicate_right_from_bas($othcollsel, $collection->get_base_id());
                    }
                    $n += 20;
                }
            }

            return $app->redirect('/admin/collection/' . $collection->get_base_id() . '/?success=1&reload-tree=1');
        } catch (\Exception $e) {
            return $app->redirect('/admin/databox/' . $databox_id . '/collection/error=error');
        }
    }

    /**
     * Display page to get some details on a appbox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   Response
     */
    public function getDetails(Application $app, Request $request, $databox_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox($databox_id);

        $out = array('total' => array('totobj' => 0, 'totsiz' => 0, 'mega'   => '0', 'giga'   => '0'), 'result' => array());

        foreach ($databox->get_record_details($request->query->get('sort')) as $vgrp) {

            $last_k1 = $last_k2 = null;
            $outRow = array('midobj' => 0, 'midsiz' => 0);

            foreach ($vgrp as $vrow) {
                if ($vrow["n"] > 0 || $last_k1 !== $vrow["coll_id"]) {

                    $outRow['midobj'] += $vrow["n"];

                    if (extension_loaded("bcmath")) {
                        $outRow['midsiz'] = bcadd($outRow['midsiz'], $vrow["siz"], 0);
                    } else {
                        $outRow['midsiz'] += $vrow["siz"];
                    }

                    if ($last_k1 !== $vrow["coll_id"]) {
                        if ((int) $vrow["lostcoll"] <= 0) {
                            $outRow['asciiname'] = $vrow["asciiname"];
                        } else {
                            $outRow['asciiname'] = _('admin::base: enregistrements orphelins') . ' ' . sprintf("(coll_id=%d)", (int) $vrow["coll_id"]);
                        }

                        $last_k1 = (int) $vrow["coll_id"];
                    }
                    if ($last_k2 !== $vrow["name"]) {
                        $outRow['name'] = $vrow["name"];
                        $last_k2 = $vrow["name"];
                    }

                    $outRow['n'] = $vrow["n"];

                    if (extension_loaded("bcmath")) {
                        $mega = bcdiv($vrow["siz"], 1024 * 1024, 5);
                    } else {
                        $mega = $vrow["siz"] / (1024 * 1024);
                    }

                    if (extension_loaded("bcmath")) {
                        $giga = bcdiv($vrow["siz"], 1024 * 1024 * 1024, 5);
                    } else {
                        $giga = $vrow["siz"] / (1024 * 1024 * 1024);
                    }

                    $outRow['mega'] = sprintf("%.2f", $mega);
                    $outRow['giga'] = sprintf("%.2f", $giga);
                }

                $last_k1 = null;
            }

            $out['result'][] = $outRow;
        }

        $out['total']['totobj'] += $outRow['midobj'];

        if (extension_loaded("bcmath")) {
            $out['total']['totsiz'] = bcadd($out['total']['totsiz'], $outRow['midsiz'], 0);
        } else {
            $out['total']['totsiz'] += $outRow['midsiz'];
        }

        if (extension_loaded("bcmath")) {
            $out['total']['mega'] = bcdiv($out['total']['totsiz'], 1024 * 1024, 5);
        } else {
            $out['total']['mega'] = $out['total']['totsiz'] / (1024 * 1024);
        }

        if (extension_loaded("bcmath")) {
            $out['total']['giga'] = bcdiv($out['total']['totsiz'], 1024 * 1024 * 1024, 5);
        } else {
            $out['total']['giga'] = $out['total']['totsiz'] / (1024 * 1024 * 1024);
        }

        return $app['twig']->render('admin/databox/details.html.twig', array(
            'databox' => $databox,
            'table'   => $out,
            'bcmath'  => extension_loaded("bcmath"),
        ));
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
