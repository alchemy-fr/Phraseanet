<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Helper\DatabaseHelper;
use Alchemy\Phrasea\Helper\PathHelper;
use Alchemy\Phrasea\Setup\Requirements\BinariesRequirements;
use Alchemy\Phrasea\Setup\Requirements\FilesystemRequirements;
use Alchemy\Phrasea\Setup\Requirements\LocalesRequirements;
use Alchemy\Phrasea\Setup\Requirements\PhpRequirements;
use Alchemy\Phrasea\Setup\Requirements\PhraseaRequirements;
use Alchemy\Phrasea\Setup\Requirements\SystemRequirements;
use Silex\ControllerProviderInterface;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;

class Setup implements ControllerProviderInterface
{
    public function connect(SilexApplication $app)
    {
        $app['controller.setup'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->get('/', function (Application $app) {
            return $app->redirectPath('install_root');
        })->bind('setup');

        $controllers->get('/installer/', 'controller.setup:rootInstaller')
            ->bind('install_root');

        $controllers->get('/upgrade-instructions/', 'controller.setup:displayUpgradeInstructions')
            ->bind('setup_upgrade_instructions');

        $controllers->get('/installer/step2/', 'controller.setup:getInstallForm')
            ->bind('install_step2');

        $controllers->post('/installer/install/', 'controller.setup:doInstall')
            ->bind('install_do_install');

        $controllers->get('/connection_test/mysql/', function (Application $app, Request $request) {
            $dbHelper = new DatabaseHelper($app, $request);

            return $app->json($dbHelper->checkConnection());
        });

        $controllers->get('/test/path/', function (Application $app, Request $request) {
            $pathHelper = new PathHelper($app, $request);

            return $app->json($pathHelper->checkPath());
        });

        $controllers->get('/test/url/', function (Application $app, Request $request) {
            $pathHelper = new PathHelper($app, $request);

            return $app->json($pathHelper->checkUrl());
        });

        return $controllers;
    }

    public function rootInstaller(Application $app, Request $request)
    {
        $requirementsCollection = $this->getRequirementsCollection();

        return $app['twig']->render('/setup/index.html.twig', [
            'locale'                 => $app['locale'],
            'available_locales'      => Application::getAvailableLanguages(),
            'current_servername'     => $request->getScheme() . '://' . $request->getHttpHost() . '/',
            'requirementsCollection' => $requirementsCollection,
        ]);
    }

    private function getRequirementsCollection()
    {
        return [
            new BinariesRequirements(),
            new FilesystemRequirements(),
            new LocalesRequirements(),
            new PhpRequirements(),
            new PhraseaRequirements(),
            new SystemRequirements(),
        ];
    }

    public function displayUpgradeInstructions(Application $app, Request $request)
    {
        return $app['twig']->render('/setup/upgrade-instructions.html.twig', [
            'locale'              => $app['locale'],
            'available_locales'   => Application::getAvailableLanguages(),
        ]);
    }

    public function getInstallForm(Application $app, Request $request)
    {
        $warnings = [];

        $requirementsCollection = $this->getRequirementsCollection();

        foreach ($requirementsCollection as $requirements) {
            foreach ($requirements->getRequirements() as $requirement) {
                if (!$requirement->isFulfilled() && !$requirement->isOptional()) {
                    $warnings[] = $requirement->getTestMessage();
                }
            }
        }

        if ($request->getScheme() == 'http') {
            $warnings[] = $app->trans('It is not recommended to install Phraseanet without HTTPS support');
        }

        return $app['twig']->render('/setup/step2.html.twig', [
            'locale'              => $app['locale'],
            'available_locales'   => Application::getAvailableLanguages(),
            'available_templates' => ['en', 'fr'],
            'warnings'            => $warnings,
            'error'               => $request->query->get('error'),
            'current_servername'  => $request->getScheme() . '://' . $request->getHttpHost() . '/',
            'discovered_binaries' => \setup::discover_binaries(),
            'rootpath'            => realpath(__DIR__ . '/../../../../'),
        ]);
    }

    public function doInstall(Application $app, Request $request)
    {
        set_time_limit(360);

        $servername = $request->getScheme() . '://' . $request->getHttpHost() . '/';

        $abConn = $dbConn = null;

        $hostname = $request->request->get('ab_hostname');
        $port = $request->request->get('ab_port');
        $user_ab = $request->request->get('ab_user');
        $ab_password = $request->request->get('ab_password');

        $appbox_name = $request->request->get('ab_name');
        $databox_name = $request->request->get('db_name');

        try {
            $abConn = $app['dbal.provider']->get([
                'host'     => $hostname,
                'port'     => $port,
                'user'     => $user_ab,
                'password' => $ab_password,
                'dbname'   => $appbox_name,
            ]);
            $abConn->connect();
        } catch (\Exception $e) {
            return $app->redirectPath('install_step2', [
                'error' => $app->trans('Appbox is unreachable'),
            ]);
        }

        try {
            if ($databox_name) {
                $dbConn = $app['dbal.provider']->get([
                    'host'     => $hostname,
                    'port'     => $port,
                    'user'     => $user_ab,
                    'password' => $ab_password,
                    'dbname'   => $databox_name,
                ]);
                $dbConn->connect();
            }
        } catch (\Exception $e) {
            return $app->redirectPath('install_step2', [
                'error' => $app->trans('Databox is unreachable'),
            ]);
        }

        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $template = $request->request->get('db_template');
        $dataPath = $request->request->get('datapath_noweb');

        try {
            $installer = $app['phraseanet.installer'];
            $installer->setPhraseaIndexerPath($request->request->get('binary_phraseanet_indexer'));

            $binaryData = [];
            foreach ([
                'php_binary'         => $request->request->get('binary_php'),
                'phraseanet_indexer' => $request->request->get('binary_phraseanet_indexer'),
                'swf_extract_binary' => $request->request->get('binary_swfextract'),
                'pdf2swf_binary'     => $request->request->get('binary_pdf2swf'),
                'swf_render_binary'  => $request->request->get('binary_swfrender'),
                'unoconv_binary'     => $request->request->get('binary_unoconv'),
                'ffmpeg_binary'      => $request->request->get('binary_ffmpeg'),
                'mp4box_binary'      => $request->request->get('binary_MP4Box'),
                'pdftotext_binary'   => $request->request->get('binary_xpdf'),
                'recess_binary'      => $request->request->get('binary_recess'),
            ] as $key => $path) {
                $binaryData[$key] = $path;
            }

            $user = $installer->install($email, $password, $abConn, $servername, $dataPath, $dbConn, $template, $binaryData);

            $app['authentication']->openAccount($user);

            return $app->redirectPath('admin', [
                'section' => 'taskmanager',
                'notice'  => 'install_success',
            ]);
        } catch (\Exception $e) {
            return $app->redirectPath('install_step2', [
                'error' => $app->trans('an error occured : %message%', ['%message%' => $e->getMessage()]),
            ]);
        }
    }
}
