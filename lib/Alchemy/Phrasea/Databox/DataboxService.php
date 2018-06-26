<?php

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Configuration\StructureTemplate;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Utilities\StringHelper;
use Doctrine\DBAL\Connection;

/**
 * Class DataboxService
 * @package Alchemy\Phrasea\Databox
 */
class DataboxService
{

    const EMPTY_DB_NAME = 0;
    const INVALID_DB_NAME = 1;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var \appbox
     */
    private $applicationBox;

    /**
     * @var PropertyAccess
     */
    private $configuration;

    /**
     * @var callable
     */
    private $connectionFactory;

    /**
     * @var DataboxRepository
     */
    private $databoxRepository;

    /**
     * @var string
     */
    private $rootPath;

    /**
     * @param Application $application
     * @param \appbox $appbox
     * @param callable $connectionFactory
     * @param DataboxRepository $databoxRepository
     * @param PropertyAccess $defaultDbConfiguration
     * @param string $rootPath
     */
    public function __construct(
        Application $application,
        \appbox $appbox,
        callable $connectionFactory,
        DataboxRepository $databoxRepository,
        PropertyAccess $defaultDbConfiguration,
        $rootPath
    ) {
        $this->app = $application;
        $this->applicationBox = $appbox;
        $this->connectionFactory = $connectionFactory;
        $this->databoxRepository = $databoxRepository;
        $this->configuration = $defaultDbConfiguration;
        $this->rootPath = $rootPath;
    }

    /**
     * @param $databaseName
     * @param DataboxConnectionSettings|null $connectionSettings
     * @return bool
     */
    public function exists($databaseName, DataboxConnectionSettings $connectionSettings = null)
    {
        $connectionSettings = $connectionSettings ?: DataboxConnectionSettings::fromArray(
            $this->configuration->get(['main', 'database'])
        );
        $factory = $this->connectionFactory;

        // do not simply try to connect to the database, list
        /** @var Connection $connection */
        $connection = $factory([
            'host' => $connectionSettings->getHost(),
            'port' => $connectionSettings->getPort(),
            'user' => $connectionSettings->getUser(),
            'password' => $connectionSettings->getPassword(),
            'dbname' => null,
        ]);

        $ret = false;
        $databaseName = strtolower($databaseName);
        $sm = $connection->getSchemaManager();
        $databases = $sm->listDatabases();
        foreach($databases as $database) {
            if(strtolower($database) == $databaseName) {
                $ret = true;
                break;
            }
        }

        return $ret;
    }

    /**
     * @param Connection $connection
     * @param \SplFileInfo $template
     * @return \databox
     */
    public function createDataboxFromConnection($connection, $template)
    {
        return \databox::create($this->app, $connection, $template);
    }

    /**
     * @param $databaseName
     * @param $templateName
     * @param User $owner
     * @param DataboxConnectionSettings|null $connectionSettings
     * @return \databox
     * @throws \Exception_InvalidArgument
     */
    public function createDatabox(
        $databaseName,
        $templateName,
        User $owner,
        DataboxConnectionSettings $connectionSettings = null
    ) {
        $this->validateDatabaseName($databaseName);

        /** @var StructureTemplate $st */
        $st = $this->app['phraseanet.structure-template'];

        $template = $st->getByName($templateName);
        if(is_null($template)) {
            throw new \Exception_InvalidArgument(sprintf('Databox template "%s" not found.', $templateName));
        }

        // if no connectionSettings (host, user, ...) are provided, create dbox beside appBox
        $connectionSettings = $connectionSettings ?: DataboxConnectionSettings::fromArray(
            $this->configuration->get(['main', 'database'])
        );

        $factory = $this->connectionFactory;

        if(!$this->exists($databaseName, $connectionSettings)) {

            // use a tmp connection to create the database
            /** @var Connection $connection */
            $connection = $factory([
                'host'     => $connectionSettings->getHost(),
                'port'     => $connectionSettings->getPort(),
                'user'     => $connectionSettings->getUser(),
                'password' => $connectionSettings->getPassword(),
                'dbname'   => null
            ]);
            // the schemeManager does NOT quote identifiers, we MUST do it
            // see : http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-manager.html
            $connection->getSchemaManager()->createDatabase(StringHelper::SqlQuote($databaseName, StringHelper::SQL_IDENTIFIER));

            $connection->close();
            unset($connection);
        }

        /** @var Connection $connection */
        $connection = $factory([
            'host' => $connectionSettings->getHost(),
            'port' => $connectionSettings->getPort(),
            'user' => $connectionSettings->getUser(),
            'password' => $connectionSettings->getPassword(),
            'dbname' => $databaseName
        ]);

        $connection->connect();

        $databox = $this->createDataboxFromConnection($connection, $template);

        $databox->registerAdmin($owner);

        $connection->close();

        return $databox;
    }

    /**
     * @param string $databaseName
     * @param User $owner
     * @param DataboxConnectionSettings $connectionSettings
     * @return \databox
     */
    public function mountDatabox($databaseName, User $owner, DataboxConnectionSettings $connectionSettings = null)
    {
        $this->validateDatabaseName($databaseName);

        $connectionSettings = $connectionSettings ?: DataboxConnectionSettings::fromArray(
            $this->configuration->get(['main', 'database'])
        );

        $this->applicationBox->get_connection()->beginTransaction();

        try {
            $databox = \databox::mount(
                $this->app,
                $connectionSettings->getHost(),
                $connectionSettings->getPort(),
                $connectionSettings->getUser(),
                $connectionSettings->getPassword(),
                $databaseName
            );

            $databox->registerAdmin($owner);

            $this->applicationBox->get_connection()->commit();

            return $databox;
        }
        catch (\Exception $exception) {
            $this->applicationBox->get_connection()->rollBack();

            throw new \RuntimeException($exception->getMessage(), 0, $exception);
        }
    }

    private function validateDatabaseName($databaseName)
    {
        if (trim($databaseName) == '') {
            throw new \InvalidArgumentException('Database name cannot be empty.', self::EMPTY_DB_NAME);
        }

        if (\p4string::hasAccent($databaseName)) {
            throw new \InvalidArgumentException(
                'Database name cannot contain special characters.',
                self::INVALID_DB_NAME
            );
        }
    }
}
