<?php

namespace Alchemy\Phrasea\Core\Version;

use Alchemy\Phrasea\Core\Version;
use Doctrine\DBAL\Connection;

class AppboxVersionRepository implements VersionRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $result = $this->connection->fetchAssoc('SELECT version FROM sitepreff');

        if ($result !== false) {
            return $result['version'];
        }

        return VersionRepository::DEFAULT_VERSION;
    }

    /**
     * @param Version $version
     * @return bool
     */
    public function saveVersion(Version $version)
    {
        $statement = $this->connection->executeQuery(
            'UPDATE sitepreff SET version = :version WHERE id = 1',
            [ ':version' => $version->getNumber() ]
        );

        return $statement->rowCount() == 1;
    }
}
