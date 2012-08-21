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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 * 
 */
class Bas implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
                if (null !== $response = $app['phraseanet.core']['Firewall']->requireAdmin($app)) {
                    return $response;
                }

                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

        /**
         * Get a collection
         *
         * name         : admin_database_collection
         *
         * description  : Display collection information page
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{bas_id}/', $this->call('getCollection'))
            ->assert('bas_id', '\d+')
            ->bind('admin_database_collection');

        /**
         * Get a collection suggested values
         *
         * name         : admin_database_suggested_values
         *
         * description  : Display page to edit suggested values
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{bas_id}/suggested-values/', $this->call('getSuggestedValues'))
            ->assert('bas_id', '\d+')
            ->bind('admin_database_suggested_values');

        /**
         * Submit suggested values
         *
         * name         : admin_database_submit_suggested_values
         *
         * description  : Submit suggested values
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/suggested-values/', $this->call('submitSuggestedValues'))
            ->assert('bas_id', '\d+')
            ->bind('admin_database_submit_suggested_values');

        /**
         * Delete a collection
         *
         * name         : admin_collection_delete
         *
         * description  : Delete a collection
         *
         * method       : DELETE
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->delete('/{bas_id}/', $this->call('delete'))
            ->assert('bas_id', '\d+')->bind('admin_collection_delete');

        /**
         * Enable a collection
         *
         * name         : admin_collection_enable
         *
         * description  : Enable a collection
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/{bas_id}/enable/', $this->call('enable'))
            ->assert('bas_id', '\d+')->bind('admin_collection_enable');

        /**
         * Disable a collection
         *
         * name         : admin_collection_disabled
         *
         * description  : Disable a collection
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/{bas_id}/disabled/', $this->call('disabled'))
            ->assert('bas_id', '\d+')->bind('admin_collection_disabled');

        /**
         * Set new order admin
         *
         * name         : admin_collection_order_admins
         *
         * description  : Set new admins for handle items order
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/{bas_id}/order/admins/', $this->call('setOrderAdmins'))
            ->assert('bas_id', '\d+')->bind('admin_collection_order_admins');

        /**
         * Set publication watermark
         *
         * name         : admin_collection_submit_publication
         *
         * description  : Set publication watermark
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/{bas_id}/publication/display/', $this->call('setPublicationDisplay'))
            ->assert('bas_id', '\d+')->bind('admin_collection_submit_publication');

        /**
         * Rename a collection
         *
         * name         : admin_collection_rename
         *
         * description  : Rename a collection
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/{bas_id}/rename/', $this->call('rename'))
            ->assert('bas_id', '\d+')->bind('admin_collection_rename');

        /**
         * Empty a collection
         *
         * name         : admin_collection_empty
         *
         * description  : Empty a collection
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/{bas_id}/empty/', $this->call('emptyCollection'))
            ->assert('bas_id', '\d+')->bind('admin_collection_empty');

        /**
         * Unmount a collection
         *
         * name         : admin_collection_unmount
         *
         * description  : Unmount a collection
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/{bas_id}/unmount/', $this->call('unmount'))
            ->assert('bas_id', '\d+')->bind('admin_collection_unmount');

        /**
         * Set a collection mini logo
         *
         * name         : admin_collection_submit_logo
         *
         * description  : Set a collection mini logo
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : REDIRECT Response
         */
        $controllers->post('/{bas_id}/picture/mini-logo/', $this->call('setMiniLogo'))
            ->assert('bas_id', '\d+')->bind('admin_collection_submit_logo');

        /**
         * Delete the current collection mini logo
         *
         * name         : admin_collection_delete_logo
         *
         * description  : Delete the current collection mini logo
         *
         * method       : DELETE
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->delete('/{bas_id}/picture/mini-logo/', $this->call('deleteLogo'))
            ->assert('bas_id', '\d+')->bind('admin_collection_delete_logo');

        /**
         * Set a new logo
         *
         * name         : admin_collection_submit_logo
         *
         * description  : Set a new logo
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : REDIRECT Response
         */
        $controllers->post('/{bas_id}/picture/watermark/', $this->call('setWatermark'))
            ->assert('bas_id', '\d+')->bind('admin_collection_submit_watermark');

        /**
         * Delete a mini logo
         *
         * name         : admin_collection_delete_logo
         *
         * description  : Delete a mini logo
         *
         * method       : DELETE
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->delete('/{bas_id}/picture/watermark/', $this->call('deleteWatermark'))
            ->assert('bas_id', '\d+')->bind('admin_collection_delete_watermark');

        /**
         * Set a new stamp logo
         *
         * name         :
         *
         * description  : Set a new stamp
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : REDIRECT Response
         */
        $controllers->post('/{bas_id}/picture/stamp-logo/', $this->call('setStamp'))
            ->assert('bas_id', '\d+')->bind('admin_collection_submit_stamp');

        /**
         * Delete a stamp logo
         *
         * name         : admin_collection_delete_stamp
         *
         * description  : Delete a stamp
         *
         * method       : DELETE
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->delete('/{bas_id}/picture/stamp-logo/', $this->call('deleteStamp'))
            ->assert('bas_id', '\d+')->bind('admin_collection_delete_stamp');

        /**
         * Set a new banner
         *
         * name         : admin_collection_submit_banner
         *
         * description  : Set a new logo
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : REDIRECT Response
         */
        $controllers->post('/{bas_id}/picture/banner/', $this->call('setBanner'))
            ->assert('bas_id', '\d+')->bind('admin_collection_submit_banner');

        /**
         * Delete a banner
         *
         * name         : admin_collection_delete_banner
         *
         * description  : Delete a mini logo
         *
         * method       : DELETE
         *
         * parameters   : none
         *
         * return       : REDIRECT Response
         */
        $controllers->delete('/{bas_id}/picture/banner/', $this->call('deleteBanner'))
            ->assert('bas_id', '\d+')->bind('admin_collection_delete_banner');

        /**
         * Get document details in the requested collection
         *
         * name         : admin_document_details
         *
         * description  : Get documents collection details
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{bas_id}/informations/details/', $this->call('getDetails'))
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_document_details');

        return $controllers;
    }

    /**
     * Display collection information page
     *
     * @param  Application   $app        The silex application
     * @param  Request       $request    The current request
     * @param  integer       $bas_id     The collection base_id
     * @return Response
     */
    public function getCollection(Application $app, Request $request, $bas_id)
    {
        $collection = \collection::get_from_base_id($bas_id);

        $admins = array();
        if ($app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_base($bas_id, 'manage')) {
            $query = new \User_Query($app['phraseanet.appbox']);
            $admins = $query->on_base_ids(array($bas_id))
                ->who_have_right(array('order_master'))
                ->execute()
                ->get_results();
        }

        return new Response($app['twig']->render('admin/collection/collection.html.twig', array(
                    'collection' => $collection,
                    'admins'     => $admins,
                )));
    }

    /**
     * Set new admin to handle orders
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  RedirectResponse
     */
    public function setOrderAdmins(Application $app, Request $request, $bas_id)
    {
        if (count($admins = $request->get('admins', array())) > 0) {
            $new_admins = array();

            foreach ($admins as $admin) {
                $new_admins[] = $admin;
            }

            if (count($new_admins) > 0) {
                \set_exportorder::set_order_admins(array_filter($admins), $bas_id);
            }
        }

        return $app->redirect('/admin/bas/' . $bas_id . '/?operation=ok');
    }

    /**
     * Empty a collection
     *
     * @param Application   $app        The silex application
     * @param Request       $request    The current request
     * @param integer       $bas_id     The collection base_id
     * @return JsonResponse
     */
    public function emptyCollection(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $success = false;
        $message = _('An error occurred');

        try {
            $collection = \collection::get_from_base_id($bas_id);

            if ($collection->get_record_amount() <= 500) {
                $collection->empty_collection(500);
                $message = _('Collection empty successful');
            } else {
                $settings = '<?xml version="1.0" encoding="UTF-8"?><tasksettings><bas_id>' . $collection->get_base_id() . '</bas_id></tasksettings>';
                \task_abstract::create($app['phraseanet.appbox'], 'task_period_emptyColl', $settings);
                $message = _('A task has been creted, please run it to complete empty collection');
            }

            $success = true;
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $message));
    }

    /**
     * Delete the collection banner
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  JsonResponse
     */
    public function deleteBanner(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $success = false;
        $msg = _('An error occured');

        try {
            $collection = \collection::get_from_base_id($bas_id);
            $app['phraseanet.appbox']->write_collection_pic($collection, null, \collection::PIC_PRESENTATION);
            $success = true;
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     * Delete the collection stamp
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  JsonResponse
     */
    public function deleteStamp(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $success = false;
        $msg = _('An error occured');

        try {
            $collection = \collection::get_from_base_id($bas_id);
            $app['phraseanet.appbox']->write_collection_pic($collection, null, \collection::PIC_STAMP);
            $success = true;
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     * Delete the collection watermark
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  JsonResponse
     */
    public function deleteWatermark(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $success = false;
        $msg = _('An error occured');

        try {
            $collection = \collection::get_from_base_id($bas_id);
            $app['phraseanet.appbox']->write_collection_pic($collection, null, \collection::PIC_WM);
            $success = true;
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     * Delete the current collection logo
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  JsonResponse
     */
    public function deleteLogo(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $success = false;
        $msg = _('An error occured');

        try {
            $collection = \collection::get_from_base_id($bas_id);
            $collection->update_logo(null);
            $app['phraseanet.appbox']->write_collection_pic($collection, null, \collection::PIC_WM);
            $success = true;
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     * Set a collection banner
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  RedirectResponse
     */
    public function setBanner(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newBanner')) {
            $app->abort(400);
        }

        if ($file->getClientSize() > 1024 * 1024) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=too-big');
        }

        if ( ! $file->isValid()) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        try {
            $collection = \collection::get_from_base_id($bas_id);

            $app['phraseanet.appbox']->write_collection_pic($collection, $file, \collection::PIC_PRESENTATION);

            $app['phraseanet.core']['file-system']->remove($file->getPathname());
        } catch (\Exception $e) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        return $app->redirect('/admin/bas/' . $bas_id . '/?operation=ok');
    }

    /**
     * Set a collection stamp
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  RedirectResponse
     */
    public function setStamp(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newStamp')) {
            $app->abort(400);
        }

        if ($file->getClientSize() > 1024 * 1024) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=too-big');
        }

        if ( ! $file->isValid()) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        try {
            $collection = \collection::get_from_base_id($bas_id);

            $app['phraseanet.appbox']->write_collection_pic($collection, $file, \collection::PIC_STAMP);

            $app['phraseanet.core']['file-system']->remove($file->getPathname());
        } catch (\Exception $e) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        return $app->redirect('/admin/bas/' . $bas_id . '/?operation=ok');
    }

    /**
     * Set a collection watermark
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  RedirectResponse
     */
    public function setWatermark(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newWm')) {
            $app->abort(400);
        }

        if ($file->getClientSize() > 65535) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=too-big');
        }

        if ( ! $file->isValid()) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        try {
            $collection = \collection::get_from_base_id($bas_id);

            $app['phraseanet.appbox']->write_collection_pic($collection, $file, \collection::PIC_WM);

            $app['phraseanet.core']['file-system']->remove($file->getPathname());
        } catch (\Exception $e) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        return $app->redirect('/admin/bas/' . $bas_id . '/?operation=ok');
    }

    /**
     * Set collection minilogo
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  RedirectResponse
     */
    public function setMiniLogo(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newLogo')) {
            $app->abort(400);
        }

        if ($file->getClientSize() > 65535) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=too-big');
        }

        if ( ! $file->isValid()) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        try {
            $collection = \collection::get_from_base_id($bas_id);

            $app['phraseanet.appbox']->write_collection_pic($collection, $file, \collection::PIC_LOGO);

            $app['phraseanet.core']['file-system']->remove($file->getPathname());
        } catch (\Exception $e) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        return $app->redirect('/admin/bas/' . $bas_id . '/?operation=ok');
    }

    /**
     * Delete a Collection
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  JsonResponse
     */
    public function delete(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $success = false;
        $msg = _('An error occured');

        try {
            $collection = \collection::get_from_base_id($bas_id);

            if ($collection->get_record_amount() > 0) {
                $msg = _('admin::base:collection: vider la collection avant de la supprimer');
            } else {
                $collection->unmount_collection($app['phraseanet.appbox']);
                $collection->delete();
                $success = true;
                $msg = _('forms::operation effectuee OK');
            }
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     * Unmount a collection from application box
     *
     * @param Application   $app        The silex application
     * @param Request       $request    The current request
     * @param integer       $bas_id     The collection base_id
     * @return JsonResponse
     */
    public function unmount(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $success = false;
        $msg = _('An error occured');

        try {
            $collection = \collection::get_from_base_id($bas_id);
            $collection->unmount_collection($app['phraseanet.appbox']);
            $success = true;
            $msg = _('forms::operation effectuee OK');
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     * Rename a collection
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  JsonResponse
     */
    public function rename(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        if (null === $name = $request->get('name')) {
            $app->abort(400, _('Missing name format'));
        }

        $success = false;
        $msg = _('An error occured');

        try {
            $collection = \collection::get_from_base_id($bas_id);
            $collection->set_name($name);
            $success = true;
            $msg = _('forms::operation effectuee OK');
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     * Set public presentation watermark
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  JsonResponse
     */
    public function setPublicationDisplay(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        if (null === $watermark = $request->get('pub_wm')) {
            $app->abort(400, _('Missing pub_wm format'));
        }

        $success = false;
        $msg = _('An error occured');

        try {
            $collection = \collection::get_from_base_id($bas_id);
            $collection->set_public_presentation($watermark);
            $success = true;
            $msg = _('forms::operation effectuee OK');
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     * Enable a collection
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  JsonResponse
     */
    public function enable(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $success = false;
        $msg = _('An error occured');

        try {
            $collection = \collection::get_from_base_id($bas_id);
            $collection->enable($app['phraseanet.appbox']);
            $success = true;
            $msg = _('forms::operation effectuee OK');
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     * Disable a collection
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  JsonResponse
     */
    public function disabled(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $success = false;
        $msg = _('An error occured');

        try {
            $collection = \collection::get_from_base_id($bas_id);
            $collection->disable($app['phraseanet.appbox']);
            $success = true;
            $msg = _('forms::operation effectuee OK');
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     *
     * @param Application   $app        The silex application
     * @param Request       $request    The current request
     * @param integer       $bas_id     The collection base_id
     */
    public function getSuggestedValues(Application $app, Request $request, $bas_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox(\phrasea::sbasFromBas($bas_id));
        $collection = \collection::get_from_base_id($bas_id);
        $structFields = $suggestedValues = $basePrefs = array();

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
                            $f ++;
                        }
                    }
                }
            }

            $z = $sxe->xpath('/baseprefs');
            if ($z && is_array($z)) {
                foreach ($z[0] as $ki => $vi) {
                    $pref = array('status' => null, 'xml'    => null);

                    if ($ki == 'status') {
                        $pref['status'] = $vi;
                    } else if ($ki != 'sugestedValues') {
                        $pref['xml'] = $vi->asXML();
                    }

                    $basePrefs[] = $pref;
                }
            }
        }

        if ($updateMsg = $request->get('update')) {
            switch ($updateMsg) {
                case 'ok';
                    $updateMsg = _('forms::operation effectuee OK');
                    break;
            }
        }

        return new Response($app['twig']->render('admin/collection/suggested_value.html.twig', array(
                    'collection'      => $collection,
                    'databox'         => $databox,
                    'suggestedValues' => $suggestedValues,
                    'structFields'    => $structFields,
                    'basePrefs'       => $basePrefs,
                    'updateMsg'       => $updateMsg,
                )));
    }

    /**
     * Register suggested values
     *
     * @param   Application   $app        The silex application
     * @param   Request       $request    The current request
     * @param   integer       $bas_id     The collection base_id
     * @return  JsonResponse
     */
    public function submitSuggestedValues(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $success = false;
        $msg = _('An error occured');

        try {
            $collection = \collection::get_from_base_id($bas_id);

            if ($mdesc = \DOMDocument::loadXML($request->get('str'))) {
                $collection->set_prefs($mdesc);
                $msg = _('forms::operation effectuee OK');
                $success = true;
            } else {
                $msg = _('Coult not load XML');
                $success = false;
            }
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     * Get document details in the requested collection
     *
     * @param Application   $app        The silex application
     * @param Request       $request    The current request
     * @param integer       $bas_id     The collection base_id
     */
    public function getDetails(Application $app, Request $request, $bas_id)
    {
        $collection = \collection::get_from_base_id($bas_id);

        $out = array('total' => array('totobj' => 0, 'totsiz' => 0, 'mega'   => '0', 'giga'   => '0'), 'result' => array());

        foreach ($collection->get_record_details() as $vrow) {

            $last_k1 = $last_k2 = null;
            $outRow = array('midobj' => 0, 'midsiz' => 0);

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

        return new Response($app['twig']->render('admin/collection/details.html.twig', array(
                    'collection' => $collection,
                    'table'      => $out,
                )));
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
