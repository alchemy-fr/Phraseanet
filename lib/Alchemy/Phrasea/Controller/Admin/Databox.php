<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Databox implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.admin.databox'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAccessToModule('admin')
                ->requireAccessToSbas($request->attributes->get('databox_id'));
        });

        $controllers->get('/{databox_id}/', 'controller.admin.databox:getDatabase')
            ->assert('databox_id', '\d+')
            ->bind('admin_database');

        $controllers->post('/{databox_id}/delete/', 'controller.admin.databox:deleteBase')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_delete');

        $controllers->post('/{databox_id}/unmount/', 'controller.admin.databox:unmountDatabase')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_unmount');

        $controllers->post('/{databox_id}/empty/', 'controller.admin.databox:emptyDatabase')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_empty');

        $controllers->get('/{databox_id}/collections/order/', 'controller.admin.databox:getReorder')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_display_collections_order');

        $controllers->post('/{databox_id}/collections/order/', 'controller.admin.databox:setReorder')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_submit_collections_order');

        $controllers->post('/{databox_id}/collection/', 'controller.admin.databox:createCollection')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })
            ->bind('admin_database_submit_collection');

        $controllers->get('/{databox_id}/cgus/', 'controller.admin.databox:getDatabaseCGU')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_modify_struct');
            })->bind('admin_database_display_cgus');

        $controllers->post('/{databox_id}/labels/', 'controller.admin.databox:setLabels')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_databox_labels');

        $controllers->post('/{databox_id}/cgus/', 'controller.admin.databox:updateDatabaseCGU')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_modify_struct');
            })->bind('admin_database_submit_cgus');

        $controllers->get('/{databox_id}/informations/documents/', 'controller.admin.databox:progressBarInfos')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_display_document_information');

        $controllers->get('/{databox_id}/informations/details/', 'controller.admin.databox:getDetails')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_display_document_details');

        $controllers->post('/{databox_id}/collection/{collection_id}/mount/', 'controller.admin.databox:mountCollection')
            ->assert('databox_id', '\d+')
            ->assert('collection_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_mount_collection');

        $controllers->get('/{databox_id}/collection/', 'controller.admin.databox:getNewCollection')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_display_new_collection_form');

        $controllers->post('/{databox_id}/logo/', 'controller.admin.databox:sendLogoPdf')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_submit_logo');

        $controllers->post('/{databox_id}/logo/delete/', 'controller.admin.databox:deleteLogoPdf')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_delete_logo');

        $controllers->post('/{databox_id}/clear-logs/', 'controller.admin.databox:clearLogs')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_clear_logs');

        $controllers->post('/{databox_id}/reindex/', 'controller.admin.databox:reindex')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_reindex');

        $controllers->post('/{databox_id}/indexable/', 'controller.admin.databox:setIndexable')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_set_indexable');

        $controllers->post('/{databox_id}/view-name/', 'controller.admin.databox:changeViewName')
            ->assert('databox_id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas($request->attributes->get('databox_id'), 'bas_manage');
            })->bind('admin_database_rename');

        return $controllers;
    }

    /**
     *
     * @param Application $app
     * @param Request     $request
     * @param integer     $databox_id
     *
     * @return Response
     */
    public function getDatabase(Application $app, Request $request, $databox_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox($databox_id);

        switch ($errorMsg = $request->query->get('error')) {
            case 'file-error':
                $errorMsg = $app->trans('Error while sending the file');
                break;
            case 'file-invalid':
                $errorMsg = $app->trans('Invalid file format');
                break;
            case 'file-too-big':
                $errorMsg = $app->trans('The file is too big');
                break;
        }

        return $app['twig']->render('admin/databox/databox.html.twig', [
            'databox'    => $databox,
            'showDetail' => (int) $request->query->get("sta") < 1,
            'errorMsg'   => $errorMsg,
            'reloadTree' => $request->query->get('reload-tree') === '1'
        ]);
    }

    /**
     * Get databox CGU's
     *
     * @param  Application $app        The silex application
     * @param  Request     $request    The current HTTP request
     * @param  integer     $databox_id The requested databox
     * @return Response
     */
    public function getDatabaseCGU(Application $app, Request $request, $databox_id)
    {
        return $app['twig']->render('admin/databox/cgus.html.twig', [
            'languages'      => $app['locales.available'],
            'cgus'           => $app['phraseanet.appbox']->get_databox($databox_id)->get_cgus(),
            'current_locale' => $app['locale']
        ]);
    }

    /**
     * Delete a databox
     *
     * @param  Application                   $app        The silex application
     * @param  Request                       $request    The current HTTP request
     * @param  integer                       $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function deleteBase(Application $app, Request $request, $databox_id)
    {
        $success = false;
        $msg = $app->trans('An error occured');
        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);

            if ($databox->get_record_amount() > 0) {
                $msg = $app->trans('admin::base: vider la base avant de la supprimer');
            } else {
                $databox->unmount_databox();
                $app['phraseanet.appbox']->write_databox_pic($app['media-alchemyst'], $app['filesystem'], $databox, null, \databox::PIC_PDF);
                $databox->delete();
                $success = true;
                $msg = $app->trans('Successful removal');
            }
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $msg,
                'sbas_id' => $databox->get_sbas_id()
            ]);
        }

        $params = [
            'databox_id' => $databox->get_sbas_id(),
            'success'    => (int) $success,
        ];

        if ($databox->get_record_amount() > 0) {
            $params['error'] = 'databox-not-empty';
        }

        return $app->redirectPath('admin_database', $params);
    }

    public function setLabels(Application $app, Request $request, $databox_id)
    {
        if (null === $labels = $request->request->get('labels')) {
            $app->abort(400, $app->trans('Missing labels parameter'));
        }
        if (false === is_array($labels)) {
            $app->abort(400, $app->trans('Invalid labels parameter'));
        }

        $databox = $app['phraseanet.appbox']->get_databox($databox_id);
        $success = true;

        try {
            foreach ($app['locales.available'] as $code => $language) {
                if (!isset($labels[$code])) {
                    continue;
                }
                $value = $labels[$code] ?: null;
                $databox->set_label($code, $value);
            }
        } catch (\Exception $e) {
            $success = false;
        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful update') : $app->trans('An error occured')
            ]);
        }

        return $app->redirect('/admin/databox/' . $databox->get_sbas_id() . '/?success=' . (int) $success . '&reload-tree=1');
    }

    /**
     * Reindex databox content
     *
     * @param  Application                   $app        The silex application
     * @param  Request                       $request    The current HTTP request
     * @param  integer                       $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
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
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful update') : $app->trans('An error occured'),
                'sbas_id' => $databox_id
            ]);
        }

        return $app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'success'    => (int) $success,
        ]);
    }

    /**
     * Make a databox indexable
     *
     * @param  Application                   $app        The silex application
     * @param  Request                       $request    The current HTTP request
     * @param  integer                       $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
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
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful update') : $app->trans('An error occured'),
                'sbas_id' => $databox_id
            ]);
        }

        return $app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'success'    => (int) $success,
        ]);
    }

    /**
     * Update databox CGU's
     *
     * @param  Application      $app        The silex application
     * @param  Request          $request    The current HTTP request
     * @param  integer          $databox_id The requested databox
     * @return RedirectResponse
     */
    public function updateDatabaseCGU(Application $app, Request $request, $databox_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox($databox_id);

        try {
            foreach ($request->request->get('TOU', []) as $loc => $terms) {
                $databox->update_cgus($loc, $terms, !!$request->request->get('valid', false));
            }
        } catch (\Exception $e) {
            return $app->redirectPath('admin_database_display_cgus', [
                'databox_id' => $databox_id,
                'success'    => 0,
            ]);
        }

        return $app->redirectPath('admin_database_display_cgus', [
            'databox_id' => $databox_id,
            'success'    => 1,
        ]);
    }

    /**
     * Mount a collection on a databox
     *
     * @param  Application      $app           The silex application
     * @param  Request          $request       The current HTTP request
     * @param  integer          $databox_id    The requested databox
     * @param  integer          $collection_id The requested collection id
     * @return RedirectResponse
     */
    public function mountCollection(Application $app, Request $request, $databox_id, $collection_id)
    {
        $app['phraseanet.appbox']->get_connection()->beginTransaction();
        try {
            $baseId = \collection::mount_collection($app, $app['phraseanet.appbox']->get_databox($databox_id), $collection_id, $app['authentication']->getUser());

            $othCollSel = (int) $request->request->get("othcollsel") ?: null;

            if (null !== $othCollSel) {
                $query = $app['phraseanet.user-query'];
                $n = 0;

                while ($n < $query->on_base_ids([$othCollSel])->get_total()) {
                    $results = $query->limit($n, 50)->execute()->get_results();

                    foreach ($results as $user) {
                        $app['acl']->get($user)->duplicate_right_from_bas($othCollSel, $baseId);
                    }

                    $n += 50;
                }
            }

            $app['phraseanet.appbox']->get_connection()->commit();

            return $app->redirectPath('admin_database', [
                'databox_id' => $databox_id,
                'mount'      => 'ok',
            ]);
        } catch (\Exception $e) {
            $app['phraseanet.appbox']->get_connection()->rollBack();

            return $app->redirectPath('admin_database', [
                'databox_id' => $databox_id,
                'mount'      => 'ko',
            ]);
        }
    }

    /**
     * Set a new logo for a databox
     *
     * @param  Application      $app        The silex application
     * @param  Request          $request    The current HTTP request
     * @param  integer          $databox_id The requested databox
     * @return RedirectResponse
     */
    public function sendLogoPdf(Application $app, Request $request, $databox_id)
    {
        try {
            if (null !== ($file = $request->files->get('newLogoPdf')) && $file->isValid()) {

                if ($file->getClientSize() < 65536) {
                    $databox = $app['phraseanet.appbox']->get_databox($databox_id);
                    $app['phraseanet.appbox']->write_databox_pic($app['media-alchemyst'], $app['filesystem'], $databox, $file, \databox::PIC_PDF);
                    unlink($file->getPathname());

                    return $app->redirectPath('admin_database', [
                        'databox_id' => $databox_id,
                        'success'    => '1',
                    ]);
                } else {
                    return $app->redirectPath('admin_database', [
                        'databox_id' => $databox_id,
                        'success'    => '0',
                        'error'      => 'file-too-big',
                    ]);
                }
            } else {
                return $app->redirectPath('admin_database', [
                    'databox_id' => $databox_id,
                    'success'    => '0',
                    'error'      => 'file-invalid',
                ]);
            }
        } catch (\Exception $e) {
            return $app->redirectPath('admin_database', [
                'databox_id' => $databox_id,
                'success'    => '0',
                'error'      => 'file-error',
            ]);
        }
    }

    /**
     * Delete an existing logo for a databox
     *
     * @param  Application                   $app        The silex application
     * @param  Request                       $request    The current HTTP request
     * @param  integer                       $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
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
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful removal') : $app->trans('An error occured'),
                'sbas_id' => $databox_id
            ]);
        }

        return $app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'error'      => 'file-too-big',
        ]);
    }

    /**
     * Clear databox logs
     *
     * @param  Application                   $app        The silex application
     * @param  Request                       $request    The current HTTP request
     * @param  integer                       $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
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
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful update') : $app->trans('An error occured'),
                'sbas_id' => $databox_id
            ]);
        }

        return $app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'error'      => 'file-too-big',
        ]);
    }

    /**
     * Change the name of a databox
     *
     * @param  Application                   $app        The silex application
     * @param  Request                       $request    The current HTTP request
     * @param  integer                       $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function changeViewName(Application $app, Request $request, $databox_id)
    {
        if (null === $viewName = $request->request->get('viewname')) {
            $app->abort(400, $app->trans('Missing view name parameter'));
        }

        $success = false;

        try {
            $app['phraseanet.appbox']->get_databox($databox_id)->set_viewname($viewName);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful update') : $app->trans('An error occured'),
                'sbas_id' => $databox_id
            ]);
        }

        return $app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'error'      => 'file-too-big',
        ]);
    }

    /**
     * Unmount a databox
     *
     * @param  Application                   $app        The silex application
     * @param  Request                       $request    The current HTTP request
     * @param  integer                       $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function unmountDatabase(Application $app, Request $request, $databox_id)
    {
        $success = false;

        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);
            $databox->unmount_databox();

            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('The publication has been stopped') : $app->trans('An error occured'),
                'sbas_id' => $databox_id
            ]);
        }

        return $app->redirectPath('admin_databases', [
            'reload-tree' => 1,
        ]);
    }

    /**
     * Empty a databox
     *
     * @param  Application                   $app        The silex application
     * @param  Request                       $request    The current HTTP request
     * @param  integer                       $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function emptyDatabase(Application $app, Request $request, $databox_id)
    {
        $msg = $app->trans('An error occurred');
        $success = false;
        $taskCreated = false;

        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);

            foreach ($databox->get_collections() as $collection) {
                if ($collection->get_record_amount() <= 500) {
                    $collection->empty_collection(500);
                } else {
                    $app['manipulator.task']->createEmptyCollectionJob($collection);
                }
            }

            $msg = $app->trans('Base empty successful');
            $success = true;

            if ($taskCreated) {
                $msg = $app->trans('A task has been created, please run it to complete empty collection');
            }
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $msg,
                'sbas_id' => $databox_id
            ]);
        }

        return $app->redirectPath('admin_database', [
            'databox_id' => $databox_id,
            'error'      => 'file-too-big',
        ]);
    }

    /**
     * Get number of indexed items for a databox
     *
     * @param  Application  $app        The silex application
     * @param  Request      $request    The current HTTP request
     * @param  integer      $databox_id The requested databox
     * @return JsonResponse
     */
    public function progressBarInfos(Application $app, Request $request, $databox_id)
    {
        if (!$app['request']->isXmlHttpRequest() || 'json' !== $app['request']->getRequestFormat()) {
            $app->abort(400, $app->trans('Bad request format, only JSON is allowed'));
        }

        $app['phraseanet.appbox'] = $app['phraseanet.appbox'];

        $ret = [
            'success'           => false,
            'msg'               => $app->trans('An error occured'),
            'sbas_id'           => null,
            'indexable'         => false,
            'records'           => 0,
            'xml_indexed'       => 0,
            'thesaurus_indexed' => 0,
            'viewname'          => null,
            'printLogoURL'      => null
        ];

        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);
            $datas = $databox->get_indexed_record_amount();

            $ret['indexable'] = $app['phraseanet.appbox']->is_databox_indexable($databox);
            $ret['viewname'] = (($databox->get_dbname() == $databox->get_viewname()) ? $app->trans('admin::base: aucun alias') : $databox->get_viewname());
            $ret['records'] = $databox->get_record_amount();
            $ret['sbas_id'] = $databox_id;
            $ret['xml_indexed'] = $datas['xml_indexed'];
            $ret['thesaurus_indexed'] = $datas['thesaurus_indexed'];
            $ret['jeton_subdef'] = $datas['jeton_subdef'];
            if ($app['filesystem']->exists($app['root.path'] . '/config/minilogos/logopdf_' . $databox_id . '.jpg')) {
                $ret['printLogoURL'] = '/custom/minilogos/logopdf_' . $databox_id . '.jpg';
            }

            $ret['success'] = true;
            $ret['msg'] = $app->trans('Successful update');
        } catch (\Exception $e) {

        }

        return $app->json($ret);
    }

    /**
     * Display page for reaorder collections on a databox
     *
     * @param  Application $app        The silex application
     * @param  Request     $request    The current HTTP request
     * @param  integer     $databox_id The requested databox
     * @return Response
     */
    public function getReorder(Application $app, Request $request, $databox_id)
    {
        return $app['twig']->render('admin/collection/reorder.html.twig', [
            'collections' => $app['acl']->get($app['authentication']->getUser())->get_granted_base([], [$databox_id]),
        ]);
    }

    /**
     * Apply collection reorder changes
     *
     * @param  Application                   $app        The silex application
     * @param  Request                       $request    The current HTTP request
     * @param  integer                       $databox_id The requested databox
     * @return JsonResponse|RedirectResponse
     */
    public function setReorder(Application $app, Request $request, $databox_id)
    {
        try {
            foreach ($request->request->get('order', []) as $data) {
                $collection = \collection::get_from_base_id($app, $data['id']);
                $collection->set_ord($data['offset']);
            }
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful update') : $app->trans('An error occured'),
                'sbas_id' => $databox_id
            ]);
        }

        return $app->redirectPath('admin_database_display_collections_order', [
            'databox_id' => $databox_id,
            'success'    => (int) $success,
        ]);
    }

    /**
     * Display page to create a new collection
     *
     * @param  Application $app        The silex application
     * @param  Request     $request    The current HTTP request
     * @param  integer     $databox_id The requested databox
     * @return Response
     */
    public function getNewCollection(Application $app, Request $request, $databox_id)
    {
        return $app['twig']->render('admin/collection/create.html.twig');
    }

    /**
     * Create a new collection
     *
     * @param  Application $app        The silex application
     * @param  Request     $request    The current HTTP request
     * @param  integer     $databox_id The requested databox
     * @return Response
     */
    public function createCollection(Application $app, Request $request, $databox_id)
    {
        if (($name = trim($request->request->get('name', ''))) === '') {
            return $app->redirectPath('admin_database_display_new_collection_form', [
                'databox_id' => $databox_id,
                'error'      => 'name',
            ]);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);
            $collection = \collection::create($app, $databox, $app['phraseanet.appbox'], $name, $app['authentication']->getUser());

            if (($request->request->get('ccusrothercoll') === "on")
                && (null !== $othcollsel = $request->request->get('othcollsel'))) {
                $query = $app['phraseanet.user-query'];
                $total = $query->on_base_ids([$othcollsel])->get_total();
                $n = 0;
                while ($n < $total) {
                    $results = $query->limit($n, 20)->execute()->get_results();
                    foreach ($results as $user) {
                        $app['acl']->get($user)->duplicate_right_from_bas($othcollsel, $collection->get_base_id());
                    }
                    $n += 20;
                }
            }

            return $app->redirectPath('admin_display_collection', ['bas_id' => $collection->get_base_id(), 'success' => 1, 'reload-tree' => 1]);
        } catch (\Exception $e) {
            return $app->redirectPath('admin_database_submit_collection', ['databox_id' => $databox_id, 'error' => 'error']);
        }
    }

    /**
     * Display page to get some details on a appbox
     *
     * @param  Application $app        The silex application
     * @param  Request     $request    The current HTTP request
     * @param  integer     $databox_id The requested databox
     * @return Response
     */
    public function getDetails(Application $app, Request $request, $databox_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox($databox_id);

        $details = [];
        $total = ['total_subdefs' => 0, 'total_size' => 0];

        foreach ($databox->get_record_details($request->query->get('sort')) as $collName => $colDetails) {
            $details[$collName] = [
                'total_subdefs' => 0,
                'total_size' => 0,
                'medias' => []
            ];

            foreach ($colDetails as $subdefName => $subdefDetails) {
                $details[$collName]['total_subdefs'] += $subdefDetails['n'];
                $total['total_subdefs'] += $subdefDetails['n'];
                $details[$collName]['total_size'] += $subdefDetails['siz'];
                $total['total_size'] += $subdefDetails['siz'];

                $details[$collName]['medias'][] = [
                    'subdef_name' => $subdefName,
                    'total_subdefs' => $subdefDetails['n'],
                    'total_size' => $subdefDetails['siz'],
                ];
            }
        }

        return $app['twig']->render('admin/databox/details.html.twig', [
            'databox' => $databox,
            'table'   => $details,
            'total'   => $total
        ]);
    }
}
