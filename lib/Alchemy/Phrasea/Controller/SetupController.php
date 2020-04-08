<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\StructureTemplate;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\Setup\RequirementCollectionInterface;
use Alchemy\Phrasea\Setup\Requirements\BinariesRequirements;
use Alchemy\Phrasea\Setup\Requirements\FilesystemRequirements;
use Alchemy\Phrasea\Setup\Requirements\LocalesRequirements;
use Alchemy\Phrasea\Setup\Requirements\PhpRequirements;
use Alchemy\Phrasea\Setup\Requirements\SystemRequirements;
use Doctrine\DBAL\Connection;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;

class SetupController extends Controller
{
    public function rootInstaller(Request $request)
    {
        $requirementsCollection = $this->getRequirementsCollection();

        return $this->render('/setup/index.html.twig', [
            'locale'                 => $this->app['locale'],
            'available_locales'      => Application::getAvailableLanguages(),
            'current_servername'     => $request->getScheme() . '://' . $request->getHttpHost() . '/',
            'requirementsCollection' => $requirementsCollection,
        ]);
    }

    /**
     * @return RequirementCollectionInterface[]
     */
    private function getRequirementsCollection()
    {
        return [
            new BinariesRequirements(),
            new FilesystemRequirements(),
            new LocalesRequirements(),
            new PhpRequirements(),
            new SystemRequirements(),
        ];
    }

    public function displayUpgradeInstructions()
    {
        return $this->render('/setup/upgrade-instructions.html.twig', [
            'locale'              => $this->app['locale'],
            'available_locales'   => Application::getAvailableLanguages(),
        ]);
    }

    public function getInstallForm(Request $request)
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
            $warnings[] = $this->app->trans('It is not recommended to install Phraseanet without HTTPS support');
        }

        /** @var StructureTemplate $st */
        $st = $this->app['phraseanet.structure-template'];

        return $this->render('/setup/step2.html.twig', [
            'locale'              => $this->app['locale'],
            'available_locales'   => Application::getAvailableLanguages(),
            'available_templates' => $st->getNames(),
            'elasticOptions'      =>  ElasticsearchOptions::fromArray([]),
            'warnings'            => $warnings,
            'error'               => $request->query->get('error'),
            'current_servername'  => $request->getScheme() . '://' . $request->getHttpHost() . '/',
            'discovered_binaries' => \setup::discover_binaries(),
            'rootpath'            => realpath(__DIR__ . '/../../../../'),
        ]);
    }

    public function doInstall(Request $request)
    {
        set_time_limit(360);

        $servername = $request->getScheme() . '://' . $request->getHttpHost() . '/';

        $dbConn = null;

        $database_host = $request->request->get('hostname');
        $database_port = $request->request->get('port');
        $database_user = $request->request->get('user');
        $database_password = $request->request->get('db_password');

        $appbox_name = $request->request->get('ab_name');
        $databox_name = $request->request->get('db_name');

        $elastic_settings = $request->request->get('elasticsearch_settings');

        $elastic_settings = [
            'host' => (string) $elastic_settings['host'],
            'port' => (int) $elastic_settings['port'],
            'index' => (string) (isset($elastic_settings['index_name']) ? $elastic_settings['index_name'] : ''),
            'shards' => (int) $elastic_settings['shards'],
            'replicas' => (int) $elastic_settings['replicas'],
            'minScore' => (int) $elastic_settings['min_score'],
            'highlight' => (bool) (isset($elastic_settings['highlight']) ? $elastic_settings['highlight'] : false)
        ];

        $elastic_settings = ElasticsearchOptions::fromArray($elastic_settings);

        try {
            $abInfo = [
                'host'     => $database_host,
                'port'     => $database_port,
                'user'     => $database_user,
                'password' => $database_password,
                'dbname'   => $appbox_name,
            ];

            /** @var Connection $abConn */
            $abConn = $this->app['dbal.provider']($abInfo);
            $abConn->connect();
        } catch (\Exception $e) {
            return $this->app->redirectPath('install_step2', [
                'error' => $this->app->trans('Appbox is unreachable'),
            ]);
        }

        try {
            if ($databox_name) {
                $dbInfo = [
                    'host'     => $database_host,
                    'port'     => $database_port,
                    'user'     => $database_user,
                    'password' => $database_password,
                    'dbname'   => $databox_name,
                ];

                /** @var Connection $dbConn */
                $dbConn = $this->app['dbal.provider']($dbInfo);
                $dbConn->connect();
            }
        } catch (\Exception $e) {
            return $this->app->redirectPath('install_step2', [
                'error' => $this->app->trans('Databox is unreachable'),
            ]);
        }

        $this->app['dbs.options'] = array_merge(
            $this->app['db.options.from_info']($dbInfo),
            $this->app['db.options.from_info']($abInfo),
            $this->app['dbs.options']
        );
        $this->app['orm.ems.options'] = array_merge(
            $this->app['orm.em.options.from_info']($dbInfo),
            $this->app['orm.em.options.from_info']($abInfo),
            $this->app['orm.ems.options']
        );

        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $template = $request->request->get('db_template');
        $storagePath = [
            'subdefs' => $request->request->get('datapath_noweb')
        ];

        try {
            $installer = $this->app['phraseanet.installer'];

            $binaryData = [];
            foreach ([
                'php_binary'         => $request->request->get('binary_php'),
                'swf_extract_binary' => $request->request->get('binary_swfextract'),
                'pdf2swf_binary'     => $request->request->get('binary_pdf2swf'),
                'swf_render_binary'  => $request->request->get('binary_swfrender'),
                'unoconv_binary'     => $request->request->get('binary_unoconv'),
                'ffmpeg_binary'      => $request->request->get('binary_ffmpeg'),
                'mp4box_binary'      => $request->request->get('binary_MP4Box'),
                'pdftotext_binary'   => $request->request->get('binary_xpdf'),
                     ] as $key => $path) {
                $binaryData[$key] = $path;
            }

            $user = $installer->install($email, $password, $abConn, $servername, $storagePath, $dbConn, $template, $binaryData);

            $this->app->getAuthenticator()->openAccount($user);

            if(empty($elastic_settings->getHost())){
                $elastic_settings = ElasticsearchOptions::fromArray([]);
            }

            $this->app['conf']->set(['main', 'search-engine', 'options'], $elastic_settings->toArray());

            return $this->app->redirectPath('admin', [
                'section' => 'taskmanager',
                'notice'  => 'install_success',
            ]);
        } catch (\Exception $e) {
            return $this->app->redirectPath('install_step2', [
                'error' => $this->app->trans('an error occured : %message%', ['%message%' => $e->getMessage()]),
            ]);
        }
    }
}
