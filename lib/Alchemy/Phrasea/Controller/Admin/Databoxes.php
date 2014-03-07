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

use Doctrine\DBAL\DBALException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Databoxes implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.admin.databoxes'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAccessToModule('admin');
        });

        $controllers->get('/', 'controller.admin.databoxes:getDatabases')
            ->bind('admin_databases');

        $controllers->post('/', 'controller.admin.databoxes:createDatabase')
            ->bind('admin_database_new')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireAdmin();
            });

        return $controllers;
    }

    /**
     * Get Databases control panel
     *
     * @param           $app     Application $app
     * @param           $request Request $request
     * @return Response
     */
    public function getDatabases(Application $app, Request $request)
    {
        $sbasIds = array_merge(
            array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_sbas(['bas_manage']))
            , array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_sbas(['bas_modify_struct']))
        );

        $sbas = [];
        foreach ($sbasIds as $sbasId) {
            $sbas[$sbasId] = [
                'version'     => 'unknown',
                'image'       => '/skins/icons/db-remove.png',
                'server_info' => '',
                'name'        => $app->trans('Unreachable server')
            ];

            try {
                $databox = $app['phraseanet.appbox']->get_databox($sbasId);

                $sbas[$sbasId] = [
                    'version'     => $app['phraseanet.appbox']->get_version(),
                    'image'       => '/skins/icons/foldph20close_0.gif',
                    'server_info' => $databox->get_connection()->getWrappedConnection()->getAttribute(\PDO::ATTR_SERVER_VERSION),
                    'name'        => \phrasea::sbas_labels($sbasId, $app)
                ];
            } catch (\Exception $e) {

            }
        }

        switch ($errorMsg = $request->query->get('error')) {
            case 'scheduler-started' :
                $errorMsg = $app->trans('Veuillez arreter le planificateur avant la mise a jour');
                break;
            case 'already-started' :
                $errorMsg = $app->trans('The upgrade is already started');
                break;
            case 'unknow' :
                $errorMsg = $app->trans('An error occured');
                break;
            case 'bad-email' :
                $errorMsg = $app->trans('Please fix the database before starting');
                break;
            case 'base-failed' :
                $errorMsg = $app->trans('Base could not be created');
                break;
            case 'database-failed' :
                $errorMsg = $app->trans('Database does not exists or can not be accessed');
                break;
            case 'no-empty' :
                $errorMsg = $app->trans('Database can not be empty');
                break;
        }

        return $app['twig']->render('admin/databases.html.twig', [
            'files'             => new \DirectoryIterator($app['root.path'] . '/lib/conf.d/data_templates'),
            'sbas'              => $sbas,
            'error_msg'         => $errorMsg,
            'advices'           => $request->query->get('advices', []),
            'reloadTree'        => (Boolean) $request->query->get('reload-tree'),
        ]);
    }

    /**
     * Create a new databox
     *
     * @param Application $app     The silex application
     * @param Request     $request The current HTTP request
     *
     * @return RedirectResponse
     */
    public function createDatabase(Application $app, Request $request)
    {
        if ('' === $dbName = $request->request->get('new_dbname', '')) {
            return $app->redirectPath('admin_databases', ['error' => 'no-empty']);
        }

        if ((null === $request->request->get('new_settings')) && (null !== $dataTemplate = $request->request->get('new_data_template'))) {
            $dataTemplate = new \SplFileInfo($app['root.path'] . '/lib/conf.d/data_templates/' . $dataTemplate . '.xml');

            try {
                $connbas = $app['dbal.conn'];
                $connbas->connect();
            } catch (DBALException $e) {
                return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'database-failed']);
            }

            try {
                $base = \databox::create($app, $dataTemplate, $dbName);
                $base->registerAdmin($app['authentication']->getUser());
                $app['acl']->get($app['authentication']->getUser())->delete_data_from_cache();

                return $app->redirectPath('admin_database', ['databox_id' => $base->get_sbas_id(), 'success' => 1, 'reload-tree' => 1]);
            } catch (\Exception $e) {
                return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'base-failed']);
            }
        }

        if (
            null !== $request->request->get('new_settings')
            && (null !== $hostname = $request->request->get('new_hostname'))
            && (null !== $port = $request->request->get('new_port'))
            && (null !== $userDb = $request->request->get('new_user'))
            && (null !== $passwordDb = $request->request->get('new_password'))
            && (null !== $dataTemplate = $request->request->get('new_data_template'))) {

            try {
                $data_template = new \SplFileInfo($app['root.path'] . '/lib/conf.d/data_templates/' . $dataTemplate . '.xml');
                $connbas = $app['dbal.conn'];
                $connbas->connect();
                try {
                    $base = \databox::create($app, $data_template, $dbName);
                    $base->registerAdmin($app['authentication']->getUser());

                    return $app->redirectPath('admin_database', ['databox_id' => $base->get_sbas_id(), 'success' => 1, 'reload-tree' => 1]);
                } catch (\Exception $e) {
                    return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'base-failed']);
                }
            } catch (\Exception $e) {
                return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'database-failed']);
            }
        }
    }
}
