<?php
namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171023124154 extends AbstractMigration
{
    /**
     * Executes update SQL.
     */
    public function doUpSql(Schema $schema)
    {
        $this->addSql("TRUNCATE TABLE memcached");
    }
    /**
     * Execute downgrade SQL.
     */
    public function doDownSql(Schema $schema)
    {
        $this->addSql("TRUNCATE TABLE memcached");
    }
}