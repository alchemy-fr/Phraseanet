<?php

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\DBAL\Connection;

/**
 * Class DataboxService
 * @package Alchemy\Phrasea\Databox
 */
class DataboxService
{
    /**
     * @var Application
     */
    private $app;

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
     * @param callable $connectionFactory
     * @param DataboxRepository $databoxRepository
     * @param PropertyAccess $defaultDbConfiguration
     * @param string $rootPath
     */
    public function __construct(
        Application $application,
        callable $connectionFactory,
        DataboxRepository $databoxRepository,
        PropertyAccess $defaultDbConfiguration,
        $rootPath
    ) {
        $this->app = $application;
        $this->connectionFactory = $connectionFactory;
        $this->databoxRepository = $databoxRepository;
        $this->configuration = $defaultDbConfiguration;
        $this->rootPath = $rootPath;
    }

    /**
     * @param User $owner
     * @param string $databaseName
     * @param string $dataTemplate
     * @param DataboxConnectionSettings|null $connectionSettings
     * @return \databox
     */
    public function createDatabox(
        $databaseName,
        $dataTemplate,
        User $owner,
        DataboxConnectionSettings $connectionSettings = null
    ) {
        $dataTemplate = new \SplFileInfo($this->rootPath . '/lib/conf.d/data_templates/' . $dataTemplate . '.xml');
        $connectionSettings = $connectionSettings ?: DataboxConnectionSettings::fromArray(
            $this->configuration->get(['main', 'database'])
        );

        $factory = $this->connectionFactory;
        /** @var Connection $connection */
        $connection = $factory([
            'host' => $connectionSettings->getHost(),
            'port' => $connectionSettings->getPort(),
            'user' => $connectionSettings->getUser(),
            'password' => $connectionSettings->getPassword(),
            'dbname' => $databaseName
        ]);

        $connection->connect();

        $databox = \databox::create($this->app, $connection, $dataTemplate);
        $databox->registerAdmin($owner);

        $connection->close();

        return $databox;
    }

    /**
     * @param $databaseName
     * @param DataboxConnectionSettings $connectionSettings
     * @return \databox
     */
    public function mountDatabox($databaseName, DataboxConnectionSettings $connectionSettings = null)
    {
        $connectionSettings = $connectionSettings ?: DataboxConnectionSettings::fromArray(
            $this->configuration->get(['main', 'database'])
        );
    }
}
