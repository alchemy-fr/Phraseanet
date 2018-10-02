<?php

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration as BaseMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160520165600 extends BaseMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE FeedEntries CHANGE subtitle subtitle VARCHAR(1024) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE FeedEntries CHANGE subtitle subtitle VARCHAR(128) NOT NULL COLLATE utf8_unicode_ci');
    }
}
