<?php

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Migration script for old guest account
 */
class Version20180614113404 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function doUpSql(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE Users SET guest = 1 WHERE login LIKE "guest%" OR login LIKE "(#deleted_guest%"');
    }

    /**
     * @param Schema $schema
     */
    public function doDownSql(Schema $schema)
    {
    }
}
