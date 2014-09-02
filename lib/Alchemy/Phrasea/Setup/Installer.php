<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Core\Event\InstallFinishEvent;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Tools\SchemaTool;

class Installer
{
    private $app;
    private $phraseaIndexer;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function install($email, $password, Connection $abConn, $serverName, $dataPath, Connection $dbConn = null, $template = null, array $binaryData = [])
    {
        $this->rollbackInstall($abConn, $dbConn);

        try {
            $this->createConfigFile($abConn, $serverName, $binaryData);
            $this->createAB();
            $this->populateRegistryData($serverName, $dataPath, $binaryData);
            $user = $this->createUser($email, $password);
            $this->createDefaultUsers();
            if (null !== $dbConn) {
                $this->createDB($dbConn, $template);
            }
        } catch (\Exception $e) {
            $this->rollbackInstall($abConn, $dbConn);
            throw $e;
        }

        $this->app['dispatcher']->dispatch(PhraseaEvents::INSTALL_FINISH, new InstallFinishEvent($user));

        return $user;
    }

    public function setPhraseaIndexerPath($path)
    {
        $this->phraseaIndexer = $path;
    }

    private function populateRegistryData($serverName, $dataPath)
    {
        if (null === realpath($dataPath)) {
            throw new \InvalidArgumentException(sprintf('Path %s does not exist.', $dataPath));
        }

        $dataPath = realpath($dataPath) . DIRECTORY_SEPARATOR;

        $this->app['conf']->set(['main', 'storage', 'subdefs', 'default-dir'], $dataPath);
        $this->app['conf']->set('servername', $serverName);
        $this->app['conf']->set('registry', $this->app['registry.manipulator']->getRegistryData());
    }

    private function createDB(Connection $dbConn = null, $template)
    {
        $template = new \SplFileInfo(__DIR__ . '/../../../conf.d/data_templates/' . $template . '-simple.xml');
        $databox = \databox::create($this->app, $dbConn, $template);
        $this->app['acl']->get($this->app['authentication']->getUser())
            ->give_access_to_sbas([$databox->get_sbas_id()])
            ->update_rights_to_sbas(
                $databox->get_sbas_id(), [
                    'bas_manage'        => 1, 'bas_modify_struct' => 1,
                    'bas_modif_th'      => 1, 'bas_chupub'        => 1
                ]
        );

        $collection = \collection::create($this->app, $databox, $this->app['phraseanet.appbox'], 'test', $this->app['authentication']->getUser());

        $this->app['acl']->get($this->app['authentication']->getUser())->give_access_to_base([$collection->get_base_id()]);
        $this->app['acl']->get($this->app['authentication']->getUser())->update_rights_to_base($collection->get_base_id(), [
            'canpush'         => 1, 'cancmd'          => 1
            , 'canputinalbum'   => 1, 'candwnldhd'      => 1, 'candwnldpreview' => 1, 'canadmin'        => 1
            , 'actif'           => 1, 'canreport'       => 1, 'canaddrecord'    => 1, 'canmodifrecord'  => 1
            , 'candeleterecord' => 1, 'chgstatus'       => 1, 'imgtools'        => 1, 'manage'          => 1
            , 'modify_struct'   => 1, 'nowatermark'     => 1
            ]
        );

        foreach (['PhraseanetIndexer', 'Subdefs', 'WriteMetadata'] as $jobName) {
            $job = $this->app['task-manager.job-factory']->create($jobName);
            $this->app['manipulator.task']->create(
                $job->getName(),
                $job->getJobId(),
                $job->getEditor()->getDefaultSettings($this->app['conf']),
                $job->getEditor()->getDefaultPeriod()
            );
        }
    }

    private function createUser($email, $password)
    {
        $user = $this->app['manipulator.user']->createUser($email, $password, $email, true);
        $this->app['authentication']->openAccount($user);

        return $user;
    }

    private function createDefaultUsers()
    {
        $this->app['manipulator.user']->createUser(User::USER_AUTOREGISTER, User::USER_AUTOREGISTER);
        $this->app['manipulator.user']->createUser(User::USER_GUEST, User::USER_GUEST);
    }

    private function rollbackInstall(Connection $abConn, Connection $dbConn = null)
    {
        $structure = simplexml_load_file(__DIR__ . "/../../../conf.d/bases_structure.xml");

        if (!$structure) {
            throw new \RuntimeException('Unable to load schema');
        }

        $appbox = $structure->appbox;
        $databox = $structure->databox;

        foreach ($appbox->tables->table as $table) {
            try {
                $sql = 'DROP TABLE IF EXISTS `' . $table['name'] . '`';
                $stmt = $abConn->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            } catch (DBALException $e) {

            }
        }
        if (null !== $dbConn) {
            foreach ($databox->tables->table as $table) {
                try {
                    $sql = 'DROP TABLE IF EXISTS `' . $table['name'] . '`';
                    $stmt = $dbConn->prepare($sql);
                    $stmt->execute();
                    $stmt->closeCursor();
                } catch (DBALException $e) {

                }
            }
        }

        $this->app['configuration.store']->delete();

        return;
    }

    private function createAB()
    {
        $metadatas = $this->app['EM']->getMetadataFactory()->getAllMetadata();

        if (!empty($metadatas)) {
            // Create SchemaTool
            $tool = new SchemaTool($this->app['EM']);
            // Create schema
            $tool->dropSchema($metadatas);
            $tool->createSchema($metadatas);
        }

        $this->app['phraseanet.appbox']->insert_datas($this->app);
    }

    private function createConfigFile(Connection $abConn, $serverName, $binaryData)
    {
        $config = $this->app['configuration.store']->initialize()->getConfig();

        $config['main']['database']['host'] = $abConn->getHost();
        $config['main']['database']['port'] = $abConn->getPort();
        $config['main']['database']['user'] = $abConn->getUsername();
        $config['main']['database']['password'] = $abConn->getPassword();
        $config['main']['database']['dbname'] = $abConn->getDatabase();

        $config['main']['database']['driver'] = 'pdo_mysql';
        $config['main']['database']['charset'] = 'UTF8';

        $config['main']['binaries'] = $binaryData;

        $config['servername'] = $serverName;
        $config['main']['key'] = $this->app['random.medium']->generateString(16);

        $this->app['configuration.store']->setConfig($config);
    }
}
