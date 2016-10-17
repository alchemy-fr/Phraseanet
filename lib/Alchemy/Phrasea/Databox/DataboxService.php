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
     * @param string $databaseName
     * @param User $owner
     * @param DataboxConnectionSettings $connectionSettings
     * @return \databox
     */
    public function mountDatabox($databaseName, User $owner, DataboxConnectionSettings $connectionSettings = null)
    {
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
}
