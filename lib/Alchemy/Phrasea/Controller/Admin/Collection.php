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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Collection implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.admin.collection'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAccessToModule('admin')
                ->requireRightOnBase($app['request']->attributes->get('bas_id'), 'canadmin');
        });

        $controllers->get('/{bas_id}/', 'controller.admin.collection:getCollection')
            ->assert('bas_id', '\d+')
            ->bind('admin_display_collection');

        $controllers->get('/{bas_id}/suggested-values/', 'controller.admin.collection:getSuggestedValues')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_display_suggested_values');

        $controllers->post('/{bas_id}/suggested-values/', 'controller.admin.collection:submitSuggestedValues')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_suggested_values');

        $controllers->post('/{bas_id}/delete/', 'controller.admin.collection:delete')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_delete');

        $controllers->post('/{bas_id}/enable/', 'controller.admin.collection:enable')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_enable');

        $controllers->post('/{bas_id}/disabled/', 'controller.admin.collection:disabled')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_disable');

        $controllers->post('/{bas_id}/order/admins/', 'controller.admin.collection:setOrderAdmins')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_order_admins');

        $controllers->post('/{bas_id}/publication/display/', 'controller.admin.collection:setPublicationDisplay')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_publication');

        $controllers->post('/{bas_id}/rename/', 'controller.admin.collection:rename')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_rename');

        $controllers->post('/{bas_id}/labels/', 'controller.admin.collection:labels')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_labels');

        $controllers->post('/{bas_id}/empty/', 'controller.admin.collection:emptyCollection')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_empty');

        $controllers->post('/{bas_id}/unmount/', 'controller.admin.collection:unmount')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_unmount');

        $controllers->post('/{bas_id}/picture/mini-logo/', 'controller.admin.collection:setMiniLogo')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_logo');

        $controllers->post('/{bas_id}/picture/mini-logo/delete/', 'controller.admin.collection:deleteLogo')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_delete_logo');

        $controllers->post('/{bas_id}/picture/watermark/', 'controller.admin.collection:setWatermark')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_watermark');

        $controllers->post('/{bas_id}/picture/watermark/delete/', 'controller.admin.collection:deleteWatermark')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_delete_watermark');

        $controllers->post('/{bas_id}/picture/stamp-logo/', 'controller.admin.collection:setStamp')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_stamp');

        $controllers->post('/{bas_id}/picture/stamp-logo/delete/', 'controller.admin.collection:deleteStamp')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_delete_stamp');

        $controllers->post('/{bas_id}/picture/banner/', 'controller.admin.collection:setBanner')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_banner');

        $controllers->post('/{bas_id}/picture/banner/delete/', 'controller.admin.collection:deleteBanner')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_delete_banner');

        $controllers->get('/{bas_id}/informations/details/', 'controller.admin.collection:getDetails')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_display_document_details');

        return $controllers;
    }

    /**
     * Display collection information page
     *
     * @param  Application $app     The silex application
     * @param  Request     $request The current request
     * @param  integer     $bas_id  The collection base_id
     * @return Response
     */
    public function getCollection(Application $app, Request $request, $bas_id)
    {
        $collection = \collection::get_from_base_id($app, $bas_id);

        $admins = [];

        if ($app['acl']->get($app['authentication']->getUser())->has_right_on_base($bas_id, 'manage')) {
            $query = new \User_Query($app);
            $admins = $query->on_base_ids([$bas_id])
                ->who_have_right(['order_master'])
                ->execute()
                ->get_results();
        }

        switch ($errorMsg = $request->query->get('error')) {
            case 'file-error':
                $errorMsg = $app->trans('Error while sending the file');
                break;
            case 'file-invalid':
                $errorMsg = $app->trans('Invalid file format');
                break;
            case 'file-file-too-big':
                $errorMsg = $app->trans('The file is too big');
                break;
            case 'collection-not-empty':
                $errorMsg = $app->trans('Empty the collection before removing');
                break;
        }

        return $app['twig']->render('admin/collection/collection.html.twig', [
            'collection' => $collection,
            'admins'     => $admins,
            'errorMsg'   => $errorMsg,
            'reloadTree' => $request->query->get('reload-tree') === '1'
        ]);
    }

    /**
     * Set new admin to handle orders
     *
     * @param  Application      $app     The silex application
     * @param  Request          $request The current request
     * @param  integer          $bas_id  The collection base_id
     * @return RedirectResponse
     */
    public function setOrderAdmins(Application $app, Request $request, $bas_id)
    {
        $success = false;

        if (count($admins = $request->request->get('admins', [])) > 0) {
            $newAdmins = [];

            foreach ($admins as $admin) {
                $newAdmins[] = $admin;
            }

            if (count($newAdmins) > 0) {
                $conn = $app['phraseanet.appbox']->get_connection();
                $conn->beginTransaction();

                try {
                    $userQuery = new \User_Query($app);

                    $result = $userQuery->on_base_ids([$bas_id])
                            ->who_have_right(['order_master'])
                            ->execute()->get_results();

                    foreach ($result as $user) {
                        $app['acl']->get($user)->update_rights_to_base($bas_id, ['order_master' => false]);
                    }

                    foreach (array_filter($newAdmins) as $admin) {
                        $user = \User_Adapter::getInstance($admin, $app);
                        $app['acl']->get($user)->update_rights_to_base($bas_id, ['order_master' => true]);
                    }
                    $conn->commit();

                    $success = true;
                } catch (\Exception $e) {
                    $conn->rollBack();
                }
            }
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $bas_id,
            'success' => (int) $success,
        ]);
    }

    /**
     * Empty a collection
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function emptyCollection(Application $app, Request $request, $bas_id)
    {
        $success = false;
        $msg = $app->trans('An error occurred');

        $collection = \collection::get_from_base_id($app, $bas_id);
        try {

            if ($collection->get_record_amount() <= 500) {
                $collection->empty_collection(500);
                $msg = $app->trans('Collection empty successful');
            } else {
                $app['manipulator.task']->createEmptyCollectionJob($collection);
                $msg = $app->trans('A task has been creted, please run it to complete empty collection');
            }

            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                    'success' => $success,
                    'msg'     => $msg,
                    'bas_id'  => $collection->get_base_id()
                ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_base_id(),
            'success' => (int) $success,
        ]);
    }

    /**
     * Delete the collection banner
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function deleteBanner(Application $app, Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $app['phraseanet.appbox']->write_collection_pic($app['media-alchemyst'], $app['filesystem'], $collection, null, \collection::PIC_PRESENTATION);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful removal') : $app->trans('An error occured'),
                'bas_id'  => $collection->get_base_id()
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_base_id(),
            'success' => (int) $success,
        ]);
    }

    /**
     * Delete the collection stamp
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function deleteStamp(Application $app, Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $app['phraseanet.appbox']->write_collection_pic($app['media-alchemyst'], $app['filesystem'], $collection, null, \collection::PIC_STAMP);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful removal') : $app->trans('An error occured'),
                'bas_id'  => $collection->get_base_id()
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_base_id(),
            'success' => (int) $success,
        ]);
    }

    /**
     * Delete the collection watermark
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function deleteWatermark(Application $app, Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $app['phraseanet.appbox']->write_collection_pic($app['media-alchemyst'], $app['filesystem'], $collection, null, \collection::PIC_WM);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful removal') : $app->trans('An error occured'),
                'bas_id'  => $collection->get_base_id()
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_base_id(),
            'success' => (int) $success,
        ]);
    }

    /**
     * Delete the current collection logo
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function deleteLogo(Application $app, Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $collection->update_logo(null);
            $app['phraseanet.appbox']->write_collection_pic($app['media-alchemyst'], $app['filesystem'], $collection, null, \collection::PIC_LOGO);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful removal') : $app->trans('An error occured'),
                'bas_id'  => $collection->get_base_id()
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_base_id(),
            'success' => (int) $success,
        ]);
    }

    /**
     * Set a collection banner
     *
     * @param  Application      $app     The silex application
     * @param  Request          $request The current request
     * @param  integer          $bas_id  The collection base_id
     * @return RedirectResponse
     */
    public function setBanner(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newBanner')) {
            $app->abort(400);
        }

        if ($file->getClientSize() > 1024 * 1024) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-too-big',
            ]);
        }

        if (!$file->isValid()) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-invalid',
            ]);
        }

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $app['phraseanet.appbox']->write_collection_pic($app['media-alchemyst'], $app['filesystem'], $collection, $file, \collection::PIC_PRESENTATION);

            $app['filesystem']->remove($file->getPathname());
        } catch (\Exception $e) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-error',
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $bas_id,
            'success' => 1,
        ]);
    }

    /**
     * Set a collection stamp
     *
     * @param  Application      $app     The silex application
     * @param  Request          $request The current request
     * @param  integer          $bas_id  The collection base_id
     * @return RedirectResponse
     */
    public function setStamp(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newStamp')) {
            $app->abort(400);
        }

        if ($file->getClientSize() > 1024 * 1024) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-too-big',
            ]);
        }

        if (!$file->isValid()) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-invalid',
            ]);
        }

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $app['phraseanet.appbox']->write_collection_pic($app['media-alchemyst'], $app['filesystem'], $collection, $file, \collection::PIC_STAMP);

            $app['filesystem']->remove($file->getPathname());
        } catch (\Exception $e) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-error',
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $bas_id,
            'success' => 1,
        ]);
    }

    /**
     * Set a collection watermark
     *
     * @param  Application      $app     The silex application
     * @param  Request          $request The current request
     * @param  integer          $bas_id  The collection base_id
     * @return RedirectResponse
     */
    public function setWatermark(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newWm')) {
            $app->abort(400);
        }

        if ($file->getClientSize() > 65535) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-too-big',
            ]);
        }

        if (!$file->isValid()) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-invalid',
            ]);
        }

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $app['phraseanet.appbox']->write_collection_pic($app['media-alchemyst'], $app['filesystem'], $collection, $file, \collection::PIC_WM);
            $app['filesystem']->remove($file->getPathname());
        } catch (\Exception $e) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-error',
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $bas_id,
            'success' => 1,
        ]);
    }

    /**
     * Set collection minilogo
     *
     * @param  Application      $app     The silex application
     * @param  Request          $request The current request
     * @param  integer          $bas_id  The collection base_id
     * @return RedirectResponse
     */
    public function setMiniLogo(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newLogo')) {
            $app->abort(400);
        }

        if ($file->getClientSize() > 65535) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-too-big',
            ]);
        }

        if (!$file->isValid()) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-invalid',
            ]);
        }

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $app['phraseanet.appbox']->write_collection_pic($app['media-alchemyst'], $app['filesystem'], $collection, $file, \collection::PIC_LOGO);
            $app['filesystem']->remove($file->getPathname());
        } catch (\Exception $e) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $bas_id,
                'success' => 0,
                'error'   => 'file-error',
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $bas_id,
            'success' => 1,
        ]);
    }

    /**
     * Delete a Collection
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function delete(Application $app, Request $request, $bas_id)
    {
        $success = false;
        $msg = $app->trans('An error occured');

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            if ($collection->get_record_amount() > 0) {
                $msg = $app->trans('Empty the collection before removing');
            } else {
                $collection->unmount_collection($app);
                $collection->delete();
                $success = true;
                $msg = $app->trans('Successful removal');
            }
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $msg
            ]);
        }

        if ($collection->get_record_amount() > 0) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'  => $collection->get_sbas_id(),
                'success' => 0,
                'error'   => 'collection-not-empty',
            ]);
        }

        if ($success) {
            return $app->redirectPath('admin_display_collection', [
                'bas_id'      => $collection->get_sbas_id(),
                'success'     => 1,
                'reload-tree' => 1,
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_sbas_id(),
            'success' => 0,
        ]);
    }

    /**
     * Unmount a collection from application box
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function unmount(Application $app, Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $collection->unmount_collection($app);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('The publication has been stopped') : $app->trans('An error occured')
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'  => $collection->get_sbas_id(),
            'success' => (int) $success,
        ]);
    }

    /**
     * Rename a collection
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function rename(Application $app, Request $request, $bas_id)
    {
        if (trim($name = $request->request->get('name')) === '') {
            $app->abort(400, $app->trans('Missing name parameter'));
        }

        $success = false;

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $collection->set_name($name);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful update') : $app->trans('An error occured')
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'      => $collection->get_base_id(),
            'success'     => (int) $success,
            'reload-tree' => 1,
        ]);
    }

    public function labels(Application $app, Request $request, $bas_id)
    {
        if (null === $labels = $request->request->get('labels')) {
            $app->abort(400, $app->trans('Missing labels parameter'));
        }
        if (false === is_array($labels)) {
            $app->abort(400, $app->trans('Invalid labels parameter'));
        }

        $collection = \collection::get_from_base_id($app, $bas_id);
        $success = true;

        try {
            foreach ($app['locales.available'] as $code => $language) {
                if (!isset($labels[$code])) {
                    continue;
                }
                $value = $labels[$code] ?: null;
                $collection->set_label($code, $value);
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

        return $app->redirectPath('admin_display_collection', [
            'bas_id'      => $collection->get_base_id(),
            'success'     => (int) $success,
            'reload-tree' => 1,
        ]);
    }

    /**
     * Set public presentation watermark
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function setPublicationDisplay(Application $app, Request $request, $bas_id)
    {
        if (null === $watermark = $request->request->get('pub_wm')) {
            $app->abort(400, 'Missing public watermark setting');
        }

        $success = false;

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $collection->set_public_presentation($watermark);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful update') : $app->trans('An error occured')
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'      => $collection->get_sbas_id(),
            'success'     => (int) $success,
        ]);
    }

    /**
     * Enable a collection
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function enable(Application $app, Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $collection->enable($app['phraseanet.appbox']);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful update') : $app->trans('An error occured')
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'      => $collection->get_sbas_id(),
            'success'     => (int) $success,
        ]);
    }

    /**
     * Disable a collection
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function disabled(Application $app, Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $collection->disable($app['phraseanet.appbox']);
            $success = true;
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful update') : $app->trans('An error occured')
            ]);
        }

        return $app->redirectPath('admin_display_collection', [
            'bas_id'      => $collection->get_sbas_id(),
            'success'     => (int) $success,
        ]);
    }

    /**
     * Display suggested values
     *
     * @param Application $app     The silex application
     * @param Request     $request The current request
     * @param integer     $bas_id  The collection base_id
     */
    public function getSuggestedValues(Application $app, Request $request, $bas_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox(\phrasea::sbasFromBas($app, $bas_id));
        $collection = \collection::get_from_base_id($app, $bas_id);
        $structFields = $suggestedValues = $basePrefs = [];

        foreach ($databox->get_meta_structure() as $meta) {
            if ($meta->is_readonly()) {
                continue;
            }

            $structFields[$meta->get_name()] = $meta;
        }

        if ($sxe = simplexml_load_string($collection->get_prefs())) {
            $z = $sxe->xpath('/baseprefs/sugestedValues');
            if ($z && is_array($z)) {
                $f = 0;
                foreach ($z[0] as $ki => $vi) {
                    if ($vi && isset($structFields[$ki])) {
                        foreach ($vi->value as $oneValue) {
                            $suggestedValues[] = [
                                'key'   => $ki, 'value' => $f, 'name'  => (string) $oneValue
                            ];
                            $f++;
                        }
                    }
                }
            }

            $z = $sxe->xpath('/baseprefs');
            if ($z && is_array($z)) {
                foreach ($z[0] as $ki => $vi) {
                    $pref = ['status' => null, 'xml'    => null];

                    if ($ki == 'status') {
                        $pref['status'] = $vi;
                    } elseif ($ki != 'sugestedValues') {
                        $pref['xml'] = $vi->asXML();
                    }

                    $basePrefs[] = $pref;
                }
            }
        }

        return $app['twig']->render('admin/collection/suggested_value.html.twig', [
            'collection'      => $collection,
            'databox'         => $databox,
            'suggestedValues' => $suggestedValues,
            'structFields'    => $structFields,
            'basePrefs'       => $basePrefs,
        ]);
    }

    /**
     * Register suggested values
     *
     * @param  Application                   $app     The silex application
     * @param  Request                       $request The current request
     * @param  integer                       $bas_id  The collection base_id
     * @return JsonResponse|RedirectResponse
     */
    public function submitSuggestedValues(Application $app, Request $request, $bas_id)
    {
        $success = false;

        $collection = \collection::get_from_base_id($app, $bas_id);

        try {
            $domdoc = new \DOMDocument();
            if ($domdoc->loadXML($request->request->get('str'))) {
                $collection->set_prefs($domdoc);
                $success = true;
            }
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $success ? $app->trans('Successful update') : $app->trans('An error occured'),
                'bas_id'  => $collection->get_base_id()
            ]);
        }

        return $app->redirectPath('admin_collection_display_suggested_values', [
            'bas_id'      => $collection->get_sbas_id(),
            'success'     => (int) $success,
        ]);
    }

    /**
     * Get document details in the requested collection
     *
     * @param  Application $app     The silex application
     * @param  Request     $request The current request
     * @param  integer     $bas_id  The collection base_id
     * @return Response
     */
    public function getDetails(Application $app, Request $request, $bas_id)
    {
        $collection = \collection::get_from_base_id($app, $bas_id);

        $out = ['total' => ['totobj' => 0, 'totsiz' => 0, 'mega'   => '0', 'giga'   => '0'], 'result' => []];

        foreach ($collection->get_record_details() as $vrow) {

            $last_k1 = $last_k2 = null;
            $outRow = ['midobj' => 0, 'midsiz' => 0];

            if ($vrow['amount'] > 0 || $last_k1 !== $vrow['coll_id']) {

                if (extension_loaded('bcmath')) {
                    $outRow['midsiz'] = bcadd($outRow['midsiz'], $vrow['size'], 0);
                } else {
                    $outRow['midsiz'] += $vrow['size'];
                }

                if ($last_k2 !== $vrow['name']) {
                    $outRow['name'] = $vrow['name'];
                    $last_k2 = $vrow['name'];
                }

                if (extension_loaded('bcmath')) {
                    $mega = bcdiv($vrow['size'], 1024 * 1024, 5);
                } else {
                    $mega = $vrow['size'] / (1024 * 1024);
                }

                if (extension_loaded('bcmath')) {
                    $giga = bcdiv($vrow['size'], 1024 * 1024 * 1024, 5);
                } else {
                    $giga = $vrow['size'] / (1024 * 1024 * 1024);
                }

                $outRow['mega'] = sprintf('%.2f', $mega);
                $outRow['giga'] = sprintf('%.2f', $giga);
                $outRow['amount'] = $vrow['amount'];
            }

            $out['total']['totobj'] += $outRow['amount'];

            if (extension_loaded('bcmath')) {
                $out['total']['totsiz'] = bcadd($out['total']['totsiz'], $outRow['midsiz'], 0);
            } else {
                $out['total']['totsiz'] += $outRow['midsiz'];
            }

            if (extension_loaded('bcmath')) {
                $mega = bcdiv($outRow['midsiz'], 1024 * 1024, 5);
            } else {
                $mega = $outRow['midsiz'] / (1024 * 1024);
            }

            if (extension_loaded('bcmath')) {
                $giga = bcdiv($outRow['midsiz'], 1024 * 1024 * 1024, 5);
            } else {
                $giga = $outRow['midsiz'] / (1024 * 1024 * 1024);
            }

            $outRow['mega_mid_size'] = sprintf('%.2f', $mega);
            $outRow['giga_mid_size'] = sprintf('%.2f', $giga);

            $out['result'][] = $outRow;
        }

        if (extension_loaded('bcmath')) {
            $out['total']['mega'] = bcdiv($out['total']['totsiz'], 1024 * 1024, 5);
        } else {
            $out['total']['mega'] = $out['total']['totsiz'] / (1024 * 1024);
        }

        if (extension_loaded('bcmath')) {
            $out['total']['giga'] = bcdiv($out['total']['totsiz'], 1024 * 1024 * 1024, 5);
        } else {
            $out['total']['giga'] = $out['total']['totsiz'] / (1024 * 1024 * 1024);
        }

        return $app['twig']->render('admin/collection/details.html.twig', [
            'collection' => $collection,
            'table'      => $out,
        ]);
    }
}
