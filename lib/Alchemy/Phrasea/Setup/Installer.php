<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\StructureTemplate;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Core\Event\InstallFinishEvent;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\TaskManager\Job\JobInterface;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Tools\SchemaTool;

class Installer
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function install($email, $password, Connection $abConn, $serverName, array $storagePaths, Connection $dbConn = null, $templateName = null, array $binaryData = [])
    {
        $this->rollbackInstall($abConn, $dbConn);

        $this->createConfigFile($abConn, $serverName, $binaryData, $storagePaths);
        try {
            $this->createAB($abConn);
            $user = $this->createUser($email, $password);
            $this->createDefaultUsers();
            if (null !== $dbConn) {
                $this->createDB($dbConn, $templateName, $user);
            }
        } catch (\Exception $e) {
            $this->rollbackInstall($abConn, $dbConn);
            throw $e;
        }

        $this->app['dispatcher']->dispatch(PhraseaEvents::INSTALL_FINISH, new InstallFinishEvent($user));

        return $user;
    }

    private function createDB(Connection $dbConn = null, $templateName, User $admin)
    {
        /** @var StructureTemplate $st */
        $st = $this->app['phraseanet.structure-template'];
        $template = $st->getByName($templateName);
        if(is_null($template)) {
            throw new \Exception_InvalidArgument(sprintf('Databox template "%s" not found.', $templateName));
        }

        $databox = \databox::create($this->app, $dbConn, $template);

        $this->app->getAclForUser($admin)
            ->give_access_to_sbas([$databox->get_sbas_id()])
            ->update_rights_to_sbas(
                $databox->get_sbas_id(),
                [
                    \ACL::BAS_MANAGE        => true,
                    \ACL::BAS_MODIFY_STRUCT => true,
                    \ACL::BAS_MODIF_TH      => true,
                    \ACL::BAS_CHUPUB        => true
                ]
            );

        $collection = \collection::create($this->app, $databox, $this->app['phraseanet.appbox'], 'test', $admin);

        $this->app->getAclForUser($admin)
            ->give_access_to_base([$collection->get_base_id()]);

        $this->app->getAclForUser($admin)
            ->update_rights_to_base(
                $collection->get_base_id(),
                [
                    \ACL::CANPUSH            => true,
                    \ACL::CANCMD             => true,
                    \ACL::CANPUTINALBUM      => true,
                    \ACL::CANDWNLDHD         => true,
                    \ACL::CANDWNLDPREVIEW    => true,
                    \ACL::CANADMIN           => true,
                    \ACL::ACTIF              => true,
                    \ACL::CANREPORT          => true,
                    \ACL::CANADDRECORD       => true,
                    \ACL::CANMODIFRECORD     => true,
                    \ACL::CANDELETERECORD    => true,
                    \ACL::CHGSTATUS          => true,
                    \ACL::IMGTOOLS           => true,
                    \ACL::COLL_MANAGE        => true,
                    \ACL::COLL_MODIFY_STRUCT => true,
                    \ACL::NOWATERMARK        => true
                ]
            );
    }

    private function createUser($email, $password)
    {
        $user = $this->app['manipulator.user']->createUser($email, $password, $email, true);

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

    private function createAB(Connection $abConn)
    {
        // set default orm to the application box
        $this->app['orm.ems.default'] = $this->app['hash.dsn']($this->app['db.dsn']($abConn->getParams()));

        $metadata = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();

        if (!empty($metadata)) {
            // Create SchemaTool
            $tool = new SchemaTool($this->app['orm.em']);
            // Create schema
            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
        }

        $this->app->getApplicationBox()->insert_datas($this->app);
    }

    private function createConfigFile(Connection $abConn, $serverName, $binaryData, array $storagePaths)
    {
        $config = $this->app['configuration.store']->initialize()->getConfig();

        $config['main']['database']['host'] = $abConn->getHost();
        $config['main']['database']['port'] = $abConn->getPort();
        $config['main']['database']['user'] = $abConn->getUsername();
        $config['main']['database']['password'] = $abConn->getPassword();
        $config['main']['database']['dbname'] = $abConn->getDatabase();

        $config['main']['database']['driver'] = 'pdo_mysql';
        $config['main']['database']['charset'] = 'UTF8';

        $config['main']['binaries'] = array_merge($config['main']['binaries'], $binaryData);

        $config['servername'] = $serverName;
        $config['main']['key'] = $this->app['random.medium']->generateString(16);

        // define storage config
        $defaultStoragePaths = [
            'subdefs'           => __DIR__ . '/../../../../datas',
            'cache'             => __DIR__ . '/../../../../cache',
            'log'               => __DIR__ . '/../../../../logs',
            'download'          => __DIR__ . '/../../../../tmp/download',
            'lazaret'           => __DIR__ . '/../../../../tmp/lazaret',
            'caption'           => __DIR__ . '/../../../../tmp/caption',
            'worker_tmp_files'  => __DIR__ . '/../../../../tmp'
        ];

        $storagePaths = array_merge($defaultStoragePaths, $storagePaths);

        foreach ($storagePaths as $key => $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            $storagePaths[$key] = realpath($path);
        }

        $config['main']['storage'] = $storagePaths;

        $config['registry'] = $this->app['registry.manipulator']->getRegistryData();

        $this->app['configuration.store']->setConfig($config);
    }
}
