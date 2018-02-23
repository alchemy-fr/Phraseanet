<?php

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Update LazaretFiles
 */
class Version20180222113704 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function doUpSql(Schema $schema)
    {
        $this->addSql('ALTER TABLE LazaretFiles ADD record_ids VARCHAR(512) NOT NULL AFTER base_id');
    }

    /**
     * @param Schema $schema
     */
    public function doDownSql(Schema $schema)
    {
        $this->addSql('ALTER TABLE LazaretFiles DROP record_ids');
    }
}
