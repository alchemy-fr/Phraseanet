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
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
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
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_access_to_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });


        /**
         * Get a collection
         *
         * name         : admin_database_collection
         *
         * description  : Get collection
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
         * Delete collection
         *
         * name         : admin_collection_delete
         *
         * description  : Delete collection
         *
         * method       : DELETE
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->delete('/{bas_id}/', $this->call('delete'))->bind('admin_collection_delete')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

        /**
         * Enable collection
         *
         * name         : admin_collection_enable
         *
         * description  : Enable collection
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/enable/', $this->call('enable'))->bind('admin_collection_enable')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

        /**
         * Disabled collection
         *
         * name         : admin_collection_disabled
         *
         * description  : Disabled collection
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/disabled/', $this->call('disabled'))->bind('admin_collection_disabled')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

        /**
         * Set new order admin
         *
         * name         : admin_collection_order_admins
         *
         * description  : Set new order admin
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/order/admins/', $this->call('setOrderAdmins'))->bind('admin_collection_order_admins')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

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
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/publication/display/', $this->call('setPublicationDisplay'))->bind('admin_collection_submit_publication')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

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
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/rename/', $this->call('rename'))->bind('admin_collection_rename')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

        /**
         * Rename a collection
         *
         * name         : admin_collection_empty
         *
         * description  : Rename a collection
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/empty/', $this->call('emptyCollection'))->bind('admin_collection_empty')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

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
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/unmount/', $this->call('unmount'))->bind('admin_collection_unmount')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

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
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/picture/mini-logo/', $this->call('setLogo'))->bind('admin_collection_submit_logo')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

        /**
         * Delete a mini logo
         *
         * name         : admin_collection_delete_logo
         *
         * description  : Delete a mini logo
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->delete('/{bas_id}/picture/mini-logo/', $this->call('deleteLogo'))->bind('admin_collection_delete_logo')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

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
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/picture/watermark/', $this->call('setWatermark'))->bind('admin_collection_submit_logo')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

        /**
         * Delete a mini logo
         *
         * name         : admin_collection_delete_logo
         *
         * description  : Delete a mini logo
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->delete('/{bas_id}/picture/watermark/', $this->call('deleteWatermark'))->bind('admin_collection_delete_logo')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

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
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/picture/stamp-logo/', $this->call('setStamp'))->bind('admin_collection_submit_stamp')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

        /**
         * Delete a stamp logo
         *
         * name         : admin_collection_delete_stamp
         *
         * description  : Delete a stamp
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->delete('/{bas_id}/picture/stamp-logo/', $this->call('deleteStamp'))->bind('admin_collection_delete_stamp')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

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
         * return       : HTML Response
         */
        $controllers->post('/{bas_id}/picture/banner/', $this->call('setBanner'))->bind('admin_collection_submit_banner')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

        /**
         * Delete a banner
         *
         * name         : admin_collection_delete_banner
         *
         * description  : Delete a mini logo
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->delete('/{bas_id}/picture/banner/', $this->call('deleteBanner'))->bind('admin_collection_delete_banner')->before(function() use ($app) {
                if ( ! $app['phraseanet.core']->getAUthenticatedUser()->ACL()->has_right_on_base($app['request']->get('bas_id'), 'canadmin')) {
                    $app->abort(403);
                }
            });

        return $controllers;
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     * @param integer $bas_id
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
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
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

        return $app->redirect('/admin/bas/'. $bas_id . '/');
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
     */
    public function emptyCollection(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $message = _('Collection empty successful');
        $success = false;

        try {
            $collection = \collection::get_from_base_id($bas_id);

            if ($collection->get_record_amount() <= 500) {
                $collection->empty_collection(500);
            } else {
                $settings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<bas_id>" . $collection->get_bas_id() . "</bas_id></tasksettings>";
                \task_abstract::create($app['phraseanet.appbox'], 'task_period_emptyColl', $settings);
                $message = _('A task has been creted, please run it to complete empty collection');
            }

            $success = true;
        } catch (\Exception $e) {
            $message = _('An error occurred');
        }

        return $app->json(array('success' => $success, 'message' => $message));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
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
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
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
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
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
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
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
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
     */
    public function setBanner(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newBanner')) {
            $app->abort(400);
        }

        /* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
        if ($file->getClientSize() > 1024 * 1024) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=too-big');
        }

        if ($file->isValid()) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        try {
            $collection = \collection::get_from_base_id($bas_id);

            $app['phraseanet.appbox']->write_collection_pic($collection, $file, \collection::PIC_PRESENTATION);

            $app['filesystem']->remove($file->getPathname());
        } catch (\Exception $e) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        return $app->redirect('/admin/bas/' . $bas_id . '/');
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
     */
    public function setStamp(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newStamp')) {
            $app->abort(400);
        }

        /* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
        if ($file->getClientSize() > 1024 * 1024) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=too-big');
        }

        if ($file->isValid()) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        try {
            $collection = \collection::get_from_base_id($bas_id);

            $app['phraseanet.appbox']->write_collection_pic($collection, $file, \collection::PIC_STAMP);

            $app['filesystem']->remove($file->getPathname());
        } catch (\Exception $e) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        return $app->redirect('/admin/bas/' . $bas_id . '/');
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
     */
    public function setWatermark(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newWm')) {
            $app->abort(400);
        }

        /* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
        if ($file->getClientSize() > 65535) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=too-big');
        }

        if ($file->isValid()) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        try {
            $collection = \collection::get_from_base_id($bas_id);

            $app['phraseanet.appbox']->write_collection_pic($collection, $file, \collection::PIC_WM);

            $app['filesystem']->remove($file->getPathname());
        } catch (\Exception $e) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        return $app->redirect('/admin/bas/' . $bas_id . '/');
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
     */
    public function setMiniLogo(Application $app, Request $request, $bas_id)
    {
        if (null === $file = $request->files->get('newLogo')) {
            $app->abort(400);
        }

        /* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
        if ($file->getClientSize() > 65535) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=too-big');
        }

        if ($file->isValid()) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        try {
            $collection = \collection::get_from_base_id($bas_id);

            $app['phraseanet.appbox']->write_collection_pic($collection, $file, \collection::PIC_LOGO);

            $app['filesystem']->remove($file->getPathname());
        } catch (\Exception $e) {

            return $app->redirect('/admin/bas/' . $bas_id . '/?upload-error=unknow-error');
        }

        return $app->redirect('/admin/bas/' . $bas_id . '/');
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
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
                $collection->unmount_collection($appbox);
                $collection->delete();
                $success = true;
                $msg = _('forms::operation effectuee OK');
            }
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
     */
    public function unmount(Application $app, Request $request, $bas_id)
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
            $collection->unmount_collection($app['phraseanet.appbox']);
            $success = true;
            $msg = _('forms::operation effectuee OK');
        } catch (\Exception $e) {

        }

        return $app->json(array('success' => $success, 'msg'     => $msg));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
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
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
     */
    public function setPublicationDisplay(Application $app, Request $request, $bas_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_base($bas_id, 'canadmin')) {
            $app->abort(403);
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
     *
     * @param \Silex\Application $application
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
     */
    public function enabled(Application $app, Request $request, $bas_id)
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
     *
     * @param \Silex\Application $application
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $bas_id
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
