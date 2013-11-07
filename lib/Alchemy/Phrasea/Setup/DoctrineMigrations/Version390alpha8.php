<?php

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version390alpha8 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE IF NOT EXISTS Tasks (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, jobId VARCHAR(255) NOT NULL, settings LONGTEXT NOT NULL, completed TINYINT(1) NOT NULL, status VARCHAR(255) NOT NULL, crashed INT NOT NULL, single_run TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, last_execution DATETIME DEFAULT NULL, period INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE Tasks");
    }
}
