<?php

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration as BaseMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20150519173347 extends BaseMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->skipIf($schema->hasTable('Secrets'), 'Table already exists');

        $this->addSql('CREATE TABLE IF NOT EXISTS Secrets (id INT AUTO_INCREMENT NOT NULL, creator_id INT NOT NULL, created DATETIME NOT NULL, token VARCHAR(40) COLLATE utf8_bin NOT NULL, INDEX IDX_48F428861220EA6 (creator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Secrets ADD CONSTRAINT FK_48F428861220EA6 FOREIGN KEY (creator_id) REFERENCES Users (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE Secrets');
    }
}
