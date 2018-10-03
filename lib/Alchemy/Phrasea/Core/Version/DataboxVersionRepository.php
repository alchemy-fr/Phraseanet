<?php

namespace Alchemy\Phrasea\Core\Version;

use Alchemy\Phrasea\Core\Version;
use Doctrine\DBAL\Connection;

class DataboxVersionRepository implements VersionRepository
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
        $result = $this->connection->fetchAssoc('SELECT value AS version FROM pref WHERE prop="version"');

        if ($result !== false) {
            return $result['version'];
        }

        return VersionRepository::DEFAULT_VERSION;
    }

    /**
     * @param Version $version
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function saveVersion(Version $version)
    {
        $this->connection->exec("DELETE FROM pref WHERE prop='version'");

        $statement = $this->connection->executeQuery(
            'INSERT INTO pref (prop, value, locale, updated_on) VALUES ("version", :version, "", NOW())',
            [ ':version' => $version->getNumber() ]
        );

        return $statement->rowCount() == 1;
    }
}
