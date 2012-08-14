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
use Alchemy\Phrasea\Core\Configuration;
use Alchemy\Phrasea\Core;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\File\File as SymfoFile;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Database implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
                return $app['phraseanet.core']['Firewall']->requireAdmin($app);
            });


        /**
         * Create Database
         *
         * name         : admin_database_new
         *
         * description  : Create Database
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/', $this->call('createDatabase'))
            ->bind('admin_database_new');

        /**
         * Get admin database
         *
         * name         : admin_get_database
         *
         * description  : Display admin dashboard
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{databox_id}/', $this->call('getDatabase'))
            ->assert('databox_id', '\d+')
            ->bind('admin_get_database');

        /**
         * Delete a database
         *
         * name         : admin_delete_databases
         *
         * description  : Delete a database
         *
         * method       : DELETE
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->delete('/{databox_id}/', $this->call('deleteBase'))
            ->assert('databox_id', '\d+')
            ->bind('admin_delete_databases');

        /**
         * mount a database
         *
         * name         : admin_database_mount
         *
         * description  : Upgrade all databases
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/mount/', $this->call('databaseMount'))
            ->bind('admin_database_mount');

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
            ->bind('admin_database_unmount');

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
            ->bind('admin_database_empty');

        /**
         * Reorder database collection
         *
         * name         : admin_database_collections_order
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
            ->bind('admin_database_collections_order');

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
            ->bind('admin_database_submit_collections_order');

        /**
         * Get database CGU
         *
         * name         : admin_database_cgu
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
            ->bind('admin_database_cgu');

        /**
         * Update database CGU
         *
         * name         : admin_update_database_cgu
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
            ->bind('admin_update_database_cgu');

        /**
         * Update document information
         *
         * name         : admin_document_information
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
            ->bind('admin_document_information');

        /**
         * Get document details
         *
         * name         : admin_document_details
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
            ->bind('admin_document_details');

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
            ->bind('admin_database_mount_collection');

        /**
         * Get a new collection form
         *
         * name         : admin_database_get_new_collection
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
            ->bind('admin_database_get_new_collection');

        /**
         * Add logo databox
         *
         * name         : admin_submit_database_logo
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
            ->bind('admin_submit_database_logo');

        /**
         * Delete logo databox
         *
         * name         : admin_delete_database_logo
         *
         * description  : delete logo databox
         *
         * method       : DELETE
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->delete('/{databox_id}/logo/', $this->call('deleteLogoPdf'))
            ->assert('databox_id', '\d+')
            ->bind('admin_delete_database_logo');

        return $controllers;

        /**
         * Clear databox logs
         *
         * name         : admin_delete_database_clear_logs
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
            ->bind('admin_delete_database_clear_logs');

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
            ->bind('admin_database_reindex');

        /**
         * Set database indexable
         *
         * name         : admin_database_indexable
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
            ->bind('admin_database_indexable');

        /**
         * Set database name
         *
         * name         : admin_database_submit_name
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
            ->bind('admin_database_submit_name');

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

        switch ($uploadErrorLogoMsg = $request->get('upload-logo')) {
            case 'error':
                $uploadErrorLogoMsg = _('forms::erreur lors de l\'envoi du fichier');
                break;
            case 'error-send':
                $uploadErrorLogoMsg = _('forms::erreur lors de l\'envoi du fichier');
                break;
            case 'error-invalid':
                $uploadErrorLogoMsg = _('Invalid file format');
                break;
        }

        return new Response($app['twig']->render('admin/databox/databox.html.twig', array(
                    'databox'            => $databox,
                    'showDetail'         => (int) $request->get("sta") < 1,
                    'uploadErrorLogoMsg' => $uploadErrorLogoMsg
                )));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDatabaseCGU(Application $app, Request $request, $databox_id)
    {
        if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_sbas($databox_id, 'bas_modify_struct')) {
            $app->abort(403);
        }


        return new Response($app['twig']->render('admin/databox/cgus.html.twig', array(
                    'languages'      => Core::getAvailableLanguages(),
                    'cgus'           => $app['phraseanet.appbox']->get_databox($databox_id)->get_cgus(),
                    'current_locale' => \Session_Handler::get_locale()
                )));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function deleteBase(Application $app, Request $request, $databox_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $ret = array('sbas_id' => null, 'err'     => false, 'errmsg'  => null);

        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);
            if ($databox->get_record_amount() == 0) {
                $databox->unmount_databox($app['phraseanet.appbox']);
                $app['phraseanet.appbox']->write_databox_pic($databox, null, \databox::PIC_PDF);
                $databox->delete();
                $ret['sbas_id'] = $databox_id;
            } else {
                $ret['err'] = true;
                $ret['errmsg'] = _('admin::base: vider la base avant de la supprimer');
            }
        } catch (\Exception $e) {
            $ret['err'] = true;
            $ret['errmsg'] = _('An error occured');
        }

        return $app->json($ret);
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function reindex(Application $app, Request $request, $databox_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $app['phraseanet.appbox']->get_databox($databox_id)->reindex();

        return $app->json(array('sbas_id' => $databox_id));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function setIndexable(Application $app, Request $request, $databox_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $app['phraseanet.appbox']->set_databox_indexable($app['phraseanet.appbox']->get_databox($databox_id),  ! ! $request->get('indexable', false));

        return $app->json(array('sbas_id' => $databox_id));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateDatabaseCGU(Application $app, Request $request, $databox_id)
    {
        if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_sbas($databox_id, 'bas_modify_struct')) {
            $app->abort(403);
        }

        $databox = $app['phraseanet.appbox']->get_databox($databox_id);

        foreach ($request->get('TOU', array()) as $loc => $terms) {
            $databox->update_cgus($loc, $terms,  ! ! $request->get('valid', false));
        }

        return $app->redirect('/admin/database/' . $databox_id . '/cgus/');
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createDatabase(Application $app, Request $request)
    {
        if ('' === $dbName = $request->get('new_dbname', '')) {

            return $app->redirect('/admin/databases/?error=no-empty');
        }

        if (\p4string::hasAccent($dbName)) {

            return $app->redirect('/admin/databases/?error=special-chars');
        }

        $appbox = $app['phraseanet.appbox'];
        $registry = $app['phraseanet.core']['Registry'];

        if ((null === $request->get('new_settings')) && (null !== $dataTemplate = $request->get('new_data_template'))) {
            try {
                $configuration = Configuration::build();
                $choosenConnexion = $configuration->getPhraseanet()->get('database');
                $connexion = $configuration->getConnexion($choosenConnexion);

                $hostname = $connexion->get('host');
                $port = $connexion->get('port');
                $user = $connexion->get('user');
                $password = $connexion->get('password');

                $dataTemplate = new \SplFileInfo($registry->get('GV_RootPath') . 'lib/conf.d/data_templates/' . $dataTemplate . '.xml');
                $connbas = new \connection_pdo('databox_creation', $hostname, $port, $user, $password, $dbName, array(), $registry);

                try {
                    $base = \databox::create($appbox, $connbas, $dataTemplate, $registry);
                    $base->registerAdmin($app['phraseanet.core']->getAuthenticatedUser());

                    return $app->redirect('/admin/databases/?success=base-ok&sbas-id=' . $base->get_sbas_id());
                } catch (\Exception $e) {

                    return $app->redirect('/admin/databases/?error=base-failed');
                }
            } catch (\Exception $e) {

                return $app->redirect('/admin/databases/?error=database-failed');
            }
        }

        if (
            null !== $request->get('new_settings')
            && (null !== $hostname = $request->get('new_hostname'))
            && (null !== $port = $request->get('new_port'))
            && (null !== $userDb = $request->get('new_user'))
            && (null !== $passwordDb = $request->get('new_password'))
            && (null !== $dataTemplate = $request->get('new_data_template'))) {

            try {
                $data_template = new \SplFileInfo($registry->get('GV_RootPath') . 'lib/conf.d/data_templates/' . $dataTemplate . '.xml');
                $connbas = new \connection_pdo('databox_creation', $hostname, $port, $userDb, $passwordDb, $dbName, array(), $registry);
                try {
                    $base = \databox::create($appbox, $connbas, $data_template, $registry);
                    $base->registerAdmin($app['phraseanet.core']->getAuthenticatedUser());

                    return $app->redirect('/admin/databases/?success=base-ok&sbas-id=' . $base->get_sbas_id());
                } catch (\Exception $e) {

                    return $app->redirect('/admin/databases/?error=base-failed');
                }
            } catch (\Exception $e) {

                return $app->redirect('/admin/databases/?error=database-failed');
            }
        }
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function databaseMount(Application $app, Request $request)
    {
        if ('' !== $dbName = $request->get('new_dbname', '')) {

            return $app->redirect('/admin/databases/?error=no-empty');
        }

        if (\p4string::hasAccent($dbName)) {

            return $app->redirect('/admin/databases/?error=special-chars');
        }

        $appbox = $app['phraseanet.appbox'];
        $registry = $app['phraseanet.core']['Registry'];

        if ((null === $request->get('new_settings'))) {
            try {
                $configuration = Configuration::build();
                $connexion = $configuration->getConnexion();

                $hostname = $connexion->get('host');
                $port = $connexion->get('port');
                $user = $connexion->get('user');
                $password = $connexion->get('password');

                $appbox->get_connection()->beginTransaction();
                $base = \databox::mount($appbox, $hostname, $port, $user, $password, $dbName, $registry);
                $base->registerAdmin($app['phraseanet.core']->getAuthenticatedUser());
                $appbox->get_connection()->commit();

                return $app->redirect('/admin/databases/?success=mount-ok&sbas-id=' . $base->get_sbas_id());
            } catch (\Exception $e) {
                $appbox->get_connection()->rollBack();

                return $app->redirect('/admin/databases/?error=mount-failed');
            }
        }

        if (
            null !== $request->get('new_settings')
            && (null !== $hostname = $request->get('new_hostname'))
            && (null !== $port = $request->get('new_port'))
            && (null !== $userDb = $request->get('new_user'))
            && (null !== $passwordDb = $request->get('new_password'))) {

            try {
                $appbox->get_connection()->beginTransaction();
                $base = \databox::mount($appbox, $hostname, $port, $userDb, $passwordDb, $dbName, $registry);
                $base->registerAdmin($app['phraseanet.core']->getAuthenticatedUser());
                $appbox->get_connection()->commit();

                return $app->redirect('/admin/databases/?success=mount-ok&sbas-id=' . $base->get_sbas_id());
            } catch (\Exception $e) {
                $appbox->get_connection()->rollBack();

                return $app->redirect('/admin/databases/?error=mount-failed');
            }
        }
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     * @param integer $collection_id
     */
    public function mountCollection(Application $app, Request $request, $databox_id, $collection_id)
    {
        $appbox = $app['phraseanet.appbox'];
        $user = $app['phraseanet.core']->getAuthenticatedUser();

        if ($user->ACL()->has_right_on_sbas($databox_id, 'bas_manage')) {
            $app->abort(403);
        }

        $appbox->get_connection()->beginTransaction();
        try {
            $baseId = \collection::mount_collection($databox_id, $collection_id, $user);

            if (null == $othCollSel = $request->get("othcollsel")) {
                $app->abort(400);
            }

            $query = new \User_Query($appbox);
            $n = 0;

            while ($n < $query->on_base_ids(array($othCollSel))->get_total()) {
                $results = $query->limit($n, 50)->execute()->get_results();

                foreach ($results as $user) {
                    $user->ACL()->duplicate_right_from_bas($othCollSel, $baseId);
                }

                $n += 50;
            }

            $appbox->get_connection()->commit();

            return $app->redirect('/admin/database/' . $databox_id . '/?mount=ok');
        } catch (\Exception $e) {
            $appbox->get_connection()->rollBack();

            return $app->redirect('/admin/database/' . $databox_id . '/?mount=ko');
        }
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function sendLogoPdf(Application $app, Request $request, $databox_id)
    {
        try {
            if (null !== ($file = $request->files->get('newLogoPdf')) && $file->isValid()) {

                if ($file->getClientSize < 65536) {
                    $databox = $app['phraseanet.appbox']->get_databox($databox_id);
                    $app['phraseanet.appbox']->write_databox_pic($databox, $file, \databox::PIC_PDF);
                    unlink($file->getPathname());
                } else {

                    return $app->redirect('/admin/database/' . $databox_id . '/?upload-logo=error');
                }
            } else {

                return $app->redirect('/admin/database/' . $databox_id . '/?upload-logo=error-send');
            }
        } catch (\InvalidArgumentException $e) {

            return $app->redirect('/admin/database/' . $databox_id . '/?upload-logo=error-invalid');
        } catch (\Exception $e) {

            return $app->redirect('/admin/database/' . $databox_id . '/?upload-logo=error');
        }
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function deleteLogoPdf(Application $app, Request $request, $databox_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $app['phraseanet.appbox']->write_databox_pic($app['phraseanet.appbox']->get_databox($databox_id), null, \databox::PIC_PDF);

        return $app->json(array('sbas_id' => $databox_id));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function clearLog(Application $app, Request $request, $databox_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $app['phraseanet.appbox']->get_databox($databox_id)->clear_logs();

        return $app->json(array('sbas_id' => $databox_id));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function changeViewName(Application $app, Request $request, $databox_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        if (null === $viewName = $request->get('viewname')) {
            $app->abort(400, _('Missing view name parameter'));
        }

        $app['phraseanet.appbox']->set_databox_viewname($app['phraseanet.appbox']->get_databox($databox_id), $viewName);

        return $app->json(array('sbas_id' => $databox_id));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function unmountDatabase(Application $app, Request $request, $databox_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $databox = $app['phraseanet.appbox']->get_databox($databox_id);
        $databox->unmount_databox($app['phraseanet.appbox']);

        return $app->json(array('sbas_id' => $databox_id));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function emptyDatabase(Application $app, Request $request, $databox_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $message = _('Base empty successful');
        $success = false;

        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);

            foreach ($databox->get_collections() as $collection) {
                if ($collection->get_record_amount() <= 500) {
                    $collection->empty_collection(500);
                } else {
                    $settings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><tasksettings><base_id>" . $collection->get_base_id() . "</base_id></tasksettings>";
                    \task_abstract::create($app['phraseanet.appbox'], 'task_period_emptyColl', $settings);
                    $message = _('A task has been creted, please run it to complete empty collection');
                }
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
     * @param integer $databox_id
     * @param integer $collection_id
     */
    public function emptyCollection(Application $app, Request $request, $databox_id, $collection_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $message = _('Collection empty successful');
        $success = false;

        try {
            $databox = $app['phraseanet.appbox']->get_databox($databox_id);
            $collection = \collection::get_from_coll_id($databox, $collection_id);

            if ($collection->get_record_amount() <= 500) {
                $collection->empty_collection(500);
            } else {
                $settings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<base_id>" . $collection->get_base_id() . "</base_id></tasksettings>";
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
     * @param integer $databox_id
     */
    public function progressBarInfos(Application $app, Request $request, $databox_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $appbox = $app['phraseanet.appbox'];

        $ret = array(
            'sbas_id'           => null,
            'indexable'         => false,
            'records'           => 0,
            'xml_indexed'       => 0,
            'thesaurus_indexed' => 0,
            'viewname'          => null,
            'printLogoURL'      => NULL
        );

        $databox = $appbox->get_databox($databox_id);
        $datas = $databox->get_indexed_record_amount();

        $ret['indexable'] = $appbox->is_databox_indexable($databox);
        $ret['viewname'] = (($databox->get_dbname() == $databox->get_viewname()) ? _('admin::base: aucun alias') : $databox->get_viewname());
        $ret['records'] = $databox->get_record_amount();
        $ret['sbas_id'] = $databox_id;
        $ret['xml_indexed'] = $datas['xml_indexed'];
        $ret['thesaurus_indexed'] = $datas['thesaurus_indexed'];

        if ($app['phraseanet.core']['file-system']->exists($app['phraseanet.core']['Registry']->get('GV_RootPath') . 'config/minilogos/logopdf_' . $databox_id . '.jpg')) {
            $ret['printLogoURL'] = '/print/' . $databox_id;
        }

        return $app->json($ret);
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function getReorder(Application $app, Request $request, $databox_id)
    {
        return new Response($app['twig']->render('admin/collection/reorder.html.twig', array(
                    'databox' => $app['phraseanet.appbox']->get_databox($databox_id),
                )));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function setReorder(Application $app, Request $request, $databox_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        foreach ($request->get('order', array()) as $order => $baseId) {
            $collection = \collection::get_from_base_id($baseId);
            $app['phraseanet.appbox']->set_collection_order($collection, $order);
            unset($collection);
        }

        return $app->json(array('sbas_id' => $databox_id));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function getNewCollection(Application $app, Request $request, $databox_id)
    {
        return new Response($app['twig']->render('admin/collection/create.html.twig'));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $databox_id
     */
    public function getDetails(Application $app, Request $request, $databox_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox($databox_id);

        $out = array('total' => array('totobj' => 0, 'totsiz' => 0, 'mega'   => '0', 'giga'   => '0'), 'result' => array());

        foreach ($databox->get_record_details($request->get('sort')) as $vgrp) {

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

        return new Response($app['twig']->render('admin/databox/details.html.twig', array(
                    'databox' => $databox,
                    'table'   => $out,
                    'bcmath'  => extension_loaded("bcmath"),
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
