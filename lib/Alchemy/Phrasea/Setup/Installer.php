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

    public function install($email, $password, Connection $abConn, $serverName, $dataPath, Connection $dbConn = null, $template = null, array $binaryData = [])
    {
        $this->rollbackInstall($abConn, $dbConn);

        $this->createConfigFile($abConn, $serverName, $binaryData, $dataPath);
        try {
            $this->createAB($abConn);
            $user = $this->createUser($email, $password);
            $this->createDefaultUsers();
            if (null !== $dbConn) {
                $this->createDB($dbConn, $template, $user);
            }
        } catch (\Exception $e) {
            $this->rollbackInstall($abConn, $dbConn);
            throw $e;
        }

        $this->app['dispatcher']->dispatch(PhraseaEvents::INSTALL_FINISH, new InstallFinishEvent($user));

        return $user;
    }

    private function createDB(Connection $dbConn = null, $template, User $admin)
    {
        $template = new \SplFileInfo(__DIR__ . '/../../../conf.d/data_templates/' . $template . '-simple.xml');
        $databox = \databox::create($this->app, $dbConn, $template);

        $this->app->getAclForUser($admin)
            ->give_access_to_sbas([$databox->get_sbas_id()])
            ->update_rights_to_sbas(
                $databox->get_sbas_id(), [
                    'bas_manage'        => 1,
                    'bas_modify_struct' => 1,
                    'bas_modif_th'      => 1,
                    'bas_chupub'        => 1
                ]
        );

        $collection = \collection::create($this->app, $databox, $this->app['phraseanet.appbox'], 'test', $admin);

        $this->app->getAclForUser($admin)->give_access_to_base([$collection->get_base_id()]);
        $this->app->getAclForUser($admin)->update_rights_to_base($collection->get_base_id(), [
                \ACL::CANPUSH         => 1,
                \ACL::CANCMD          => 1,
                \ACL::CANPUTINALBUM   => 1,
                \ACL::CANDWNLDHD      => 1,
                \ACL::CANDWNLDPREVIEW => 1,
                \ACL::CANADMIN        => 1,
                \ACL::ACTIF           => 1,
                \ACL::CANREPORT       => 1,
                \ACL::CANADDRECORD    => 1,
                \ACL::CANMODIFRECORD  => 1,
                \ACL::CANDELETERECORD => 1,
                \ACL::CHGSTATUS       => 1,
                \ACL::IMGTOOLS        => 1,
                \ACL::MANAGE          => 1,
                \ACL::MODIFY_STRUCT   => 1,
                \ACL::NOWATERMARK     => 1
            ]
        );

        foreach (['Subdefs', 'WriteMetadata'] as $jobName) {
            /** @var JobInterface $job */
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

    private function createConfigFile(Connection $abConn, $serverName, $binaryData, $dataPath)
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

        if (null === $dataPath = realpath($dataPath)) {
            throw new \InvalidArgumentException(sprintf('Path %s does not exist.', $dataPath));
        }

        $config['main']['storage']['subdefs'] = $dataPath;

        $config['main']['storage']['cache'] = realpath(__DIR__ . '/../../../../cache');
        $config['main']['storage']['log'] = realpath(__DIR__ . '/../../../../logs');
        $config['main']['storage']['download'] = realpath(__DIR__ . '/../../../../tmp/download');
        $config['main']['storage']['lazaret'] = realpath(__DIR__ . '/../../../../tmp/lazaret');
        $config['main']['storage']['caption'] = realpath(__DIR__ . '/../../../../tmp/caption');

        $config['registry'] = $this->app['registry.manipulator']->getRegistryData();

        $this->app['configuration.store']->setConfig($config);
    }
}
