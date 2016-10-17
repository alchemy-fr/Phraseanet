<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Databox\DataboxConnectionSettings;
use Alchemy\Phrasea\Databox\DataboxService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DataboxesController extends Controller
{
    /**
     * Get Databases control panel
     *
     * @param Request $request
     * @return Response
     */
    public function getDatabases(Request $request)
    {
        $acl = $this->getAclForUser();
        $sbasIds = array_merge(
            array_keys($acl->get_granted_sbas(['bas_manage'])),
            array_keys($acl->get_granted_sbas(['bas_modify_struct']))
        );

        $sbas = [];
        foreach ($sbasIds as $sbasId) {
            $sbas[$sbasId] = [
                'version'     => 'unknown',
                'image'       => '/assets/common/images/icons/db-remove.png',
                'server_info' => '',
                'name'        => $this->app->trans('Unreachable server')
            ];

            try {
                $databox = $this->findDataboxById($sbasId);

                /** @var \PDO $pdoConnection */
                $pdoConnection = $databox->get_connection()->getWrappedConnection();
                $sbas[$sbasId] = [
                    'version'     => $databox->get_version(),
                    'image'       => '/assets/common/images/icons/foldph20close_0.gif',
                    'server_info' => $pdoConnection->getAttribute(\PDO::ATTR_SERVER_VERSION),
                    'name'        => \phrasea::sbas_labels($sbasId, $this->app)
                ];
            } catch (\Exception $e) {

            }
        }

        switch ($errorMsg = $request->query->get('error')) {
            case 'scheduler-started' :
                $errorMsg = $this->app->trans('Veuillez arreter le planificateur avant la mise a jour');
                break;
            case 'already-started' :
                $errorMsg = $this->app->trans('The upgrade is already started');
                break;
            case 'unknow' :
                $errorMsg = $this->app->trans('An error occured');
                break;
            case 'bad-email' :
                $errorMsg = $this->app->trans('Please fix the database before starting');
                break;
            case 'special-chars' :
                $errorMsg = $this->app->trans('Database name can not contains special characters');
                break;
            case 'base-failed' :
                $errorMsg = $this->app->trans('Base could not be created');
                break;
            case 'database-failed' :
                $errorMsg = $this->app->trans('Database does not exists or can not be accessed');
                break;
            case 'no-empty' :
                $errorMsg = $this->app->trans('Database can not be empty');
                break;
            case 'mount-failed' :
                $errorMsg = $this->app->trans('Database could not be mounted');
                break;
            case 'innodb-support' :
                $errorMsg = $this->app->trans('Database server does not support InnoDB storage engine');
                break;
        }

        return $this->render('admin/databases.html.twig', [
            'files'      => new \DirectoryIterator($this->app['root.path'] . '/lib/conf.d/data_templates'),
            'sbas'       => $sbas,
            'error_msg'  => $errorMsg,
            'advices'    => $request->query->get('advices', []),
            'reloadTree' => (Boolean) $request->query->get('reload-tree'),
        ]);
    }

    /**
     * Create a new databox
     *
     * @param Request $request The current HTTP request
     * @return RedirectResponse
     */
    public function createDatabase(Request $request)
    {
        if ('' === $dbName = $request->request->get('new_dbname', '')) {
            return $this->app->redirectPath('admin_databases', ['error' => 'no-empty']);
        }

        if (\p4string::hasAccent($dbName)) {
            return $this->app->redirectPath('admin_databases', ['error' => 'special-chars']);
        }

        /** @var DataboxService $databoxService */
        $databoxService = $this->app['databox.service'];
        $dataTemplate = $request->request->get('new_data_template');

        $connectionSettings = $request->request->get('new_settings') == false ? null : new DataboxConnectionSettings(
            $request->request->get('new_hostname'),
            $request->request->get('new_port'),
            $request->request->get('new_user'),
            $request->request->get('new_password')
        );

        try {
            $databox = $databoxService->createDatabox(
                $dbName,
                $dataTemplate,
                $this->getAuthenticatedUser(),
                $connectionSettings
            );

            return $this->app->redirectPath('admin_database', [
                'databox_id'  => $databox->get_sbas_id(),
                'success'     => 1,
                'reload-tree' => 1
            ]);
        }
        catch (DBALException $e) {
            return $this->app->redirectPath('admin_databases', ['success' => 0, 'error' => 'database-failed']);
        }
        catch (\Exception $e) {
            return $this->app->redirectPath('admin_databases', ['success' => 0, 'error' => 'base-failed']);
        }
    }

    /**
     * Mount a databox
     *
     * @param  Request $request The current HTTP request
     * @return RedirectResponse
     */
    public function databaseMount(Request $request)
    {
        if ('' === $dbName = trim($request->request->get('new_dbname', ''))) {
            return $this->app->redirectPath('admin_databases', ['success' => 0, 'error' => 'no-empty']);
        }

        if (\p4string::hasAccent($dbName)) {
            return $this->app->redirectPath('admin_databases', ['success' => 0, 'error' => 'special-chars']);
        }

        /** @var DataboxService $databoxService */
        $databoxService = $this->app['databox.service'];

        $connectionSettings = $request->request->get('new_settings') == false ? null : new DataboxConnectionSettings(
            $request->request->get('new_hostname'),
            $request->request->get('new_port'),
            $request->request->get('new_user'),
            $request->request->get('new_password')
        );

        try {
            $databox = $databoxService->mountDatabox($dbName, $this->app->getAuthenticatedUser(), $connectionSettings);

            return $this->app->redirectPath('admin_database', [
                'databox_id' => $databox->get_sbas_id(),
                'success' => 1,
                'reload-tree' => 1,
            ]);
        } catch (\Exception $exception) {
            return $this->app->redirectPath('admin_databases', [
                'success' => 0,
                'error' => 'mount-failed'
            ]);
        }
    }
}
