<?php

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Application;
use Doctrine\ORM\Tools\SchemaTool;

class Installer
{
    private $email;
    private $password;
    private $abConn;
    private $dbConn;
    private $template;
    private $phraseaIndexer;
    private $serverName;
    private $dataPath;
    private $registryData = array();

    public function __construct(Application $app, $email, $password, \connection_interface $abConn, $serverName, $dataPath, \connection_interface $dbConn = null, $template = null)
    {
        $this->app = $app;
        $this->email = $email;
        $this->password = $password;
        $this->abConn = $abConn;
        $this->dbConn = $dbConn;
        $this->template = $template;
        $this->serverName = $serverName;
        $this->dataPath = $dataPath;
    }

    public function addRegistryData($key, $path)
    {
        $this->registryData[$key] = $path;
    }

    public function install()
    {
        $this->rollbackInstall();

        try {

            $this->createConfigFile();
            $this->createAB();
            $this->populateRegistryData();
            $this->createUser();
            if ($this->dbConn) {
                $this->createDB();
            }
        } catch (\Exception $e) {
            $this->rollbackInstall();
            throw $e;
        }
    }

    public function setPhraseaIndexerPath($path)
    {
        $this->phraseaIndexer = $path;
    }

    private function populateRegistryData()
    {

        $this->app['phraseanet.registry']->set('GV_base_datapath_noweb', $this->dataPath, \registry::TYPE_STRING);
        $this->app['phraseanet.registry']->set('GV_ServerName', $this->serverName, \registry::TYPE_STRING);

        foreach ($this->registryData as $key => $value) {
            $this->app['phraseanet.registry']->set($key, $value, \registry::TYPE_STRING);
        }
    }

    private function createDB()
    {
        $template = new \SplFileInfo(__DIR__ . '/../../../conf.d/data_templates/' . $this->template . '-simple.xml');
        $databox = \databox::create($this->app, $this->dbConn, $template, $this->app['phraseanet.registry']);
        $this->app['phraseanet.user']->ACL()
            ->give_access_to_sbas(array($databox->get_sbas_id()))
            ->update_rights_to_sbas(
                $databox->get_sbas_id(), array(
                'bas_manage'        => 1, 'bas_modify_struct' => 1,
                'bas_modif_th'      => 1, 'bas_chupub'        => 1
                )
        );

        $collection = \collection::create($this->app, $databox, $this->app['phraseanet.appbox'], 'test', $this->app['phraseanet.user']);

        $this->app['phraseanet.user']->ACL()->give_access_to_base(array($collection->get_base_id()));
        $this->app['phraseanet.user']->ACL()->update_rights_to_base($collection->get_base_id(), array(
            'canpush'         => 1, 'cancmd'          => 1
            , 'canputinalbum'   => 1, 'candwnldhd'      => 1, 'candwnldpreview' => 1, 'canadmin'        => 1
            , 'actif'           => 1, 'canreport'       => 1, 'canaddrecord'    => 1, 'canmodifrecord'  => 1
            , 'candeleterecord' => 1, 'chgstatus'       => 1, 'imgtools'        => 1, 'manage'          => 1
            , 'modify_struct'   => 1, 'nowatermark'     => 1
            )
        );

        foreach (array('cindexer', 'subdef', 'writemeta') as $task) {
            $class_name = sprintf('task_period_%s', $task);
            if ($task === 'cindexer' && is_executable($this->phraseaIndexer)) {
                $credentials = $databox->get_appbox()->get_connection()->get_credentials();

                $host = $credentials['hostname'];
                $port = $credentials['port'];
                $user_ab = $credentials['user'];
                $password = $credentials['password'];
                $dbname = $credentials['dbname'];

                $settings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<binpath>"
                    . str_replace('/phraseanet_indexer', '', $this->phraseaIndexer)
                    . "</binpath><host>" . $host . "</host><port>"
                    . $port . "</port><base>"
                    . $dbname . "</base><user>"
                    . $user_ab . "</user><password>"
                    . $password . "</password><socket>25200</socket>"
                    . "<use_sbas>1</use_sbas><nolog>0</nolog><clng></clng>"
                    . "<winsvc_run>0</winsvc_run><charset>utf8</charset></tasksettings>";
            } else {
                $settings = null;
            }

            \task_abstract::create($this->app, $class_name, $settings);
        }
    }

    private function createUser()
    {
        $user = \User_Adapter::create($this->app, $this->email, $this->password, $this->email, true);

        $this->app['session']->set('usr_id', $user->get_id());

        \phrasea::start($this->app['phraseanet.configuration']);
    }

    private function rollbackInstall()
    {
        $structure = simplexml_load_file(__DIR__ . "/../../../conf.d/bases_structure.xml");

        if (!$structure) {
            throw new \RuntimeException('Unable to load schema');
        }

        $appbox = $structure->appbox;
        $databox = $structure->databox;

        foreach ($appbox->tables->table as $table) {
            try {
                $sql = 'DROP TABLE `' . $table['name'] . '`';
                $stmt = $this->abConn->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            } catch (\PDOException $e) {

            }
        }
        if ($this->dbConn) {
            foreach ($databox->tables->table as $table) {
                try {
                    $sql = 'DROP TABLE `' . $table['name'] . '`';
                    $stmt = $this->dbConn->prepare($sql);
                    $stmt->execute();
                    $stmt->closeCursor();
                } catch (\PDOException $e) {

                }
            }
        }

        $this->app['phraseanet.configuration']->delete();

        return;
    }

    private function createAB()
    {
        $this->app['phraseanet.appbox']->insert_datas();

        $metadatas = $this->app['EM']->getMetadataFactory()->getAllMetadata();

        if (!empty($metadatas)) {
            // Create SchemaTool
            $tool = new SchemaTool($this->app['EM']);
            // Create schema
            $tool->dropSchema($metadatas);
            $tool->createSchema($metadatas);
        }

        $this->app['phraseanet.registry'] = new \registry($this->app);
    }

    private function createConfigFile()
    {
        $this->app['phraseanet.configuration']->initialize();

        $connexionINI = array();

        foreach ($this->abConn->get_credentials() as $key => $value) {
            $key = $key == 'hostname' ? 'host' : $key;
            $connexionINI[$key] = (string) $value;
        }

        $connexionINI['driver'] = 'pdo_mysql';
        $connexionINI['charset'] = 'UTF8';

        $serverName = $this->serverName;

        $connexion = array(
            'main_connexion' => $connexionINI,
            'test_connexion' => array(
                'driver'  => 'pdo_sqlite',
                'path'    => '/tmp/db.sqlite',
                'charset' => 'UTF8'
            ));

        $cacheService = "array_cache";

        $this->app['phraseanet.configuration']->setConnexions($connexion);

        $services = $this->app['phraseanet.configuration']->getServices();

        foreach ($services as $serviceName => $service) {
            foreach ($service as $name => $desc) {
                if ($name === "doctrine_prod") {

                    $services[$serviceName]["doctrine_prod"]["options"]["cache"] = array(
                        "query"    => $cacheService,
                        "result"   => $cacheService,
                        "metadata" => $cacheService
                    );
                }
            }
        }
        $this->app['phraseanet.configuration']->setServices($services);

        $arrayConf = $this->app['phraseanet.configuration']->getConfigurations();

        foreach ($arrayConf as $key => $value) {
            if (is_array($value) && array_key_exists('phraseanet', $value)) {
                $arrayConf[$key]["phraseanet"]["servername"] = $serverName;
            }

            if (is_array($value) && $key === 'prod') {
                $arrayConf[$key]["cache"] = $cacheService;
            }
        }

        $this->app['phraseanet.configuration']->setConfigurations($arrayConf);
    }
}
