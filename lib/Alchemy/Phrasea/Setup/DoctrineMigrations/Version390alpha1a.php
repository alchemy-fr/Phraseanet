<?php

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version390alpha1a extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE IF NOT EXISTS FtpCredential (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, usrId INT NOT NULL, active TINYINT(1) NOT NULL, address VARCHAR(128) NOT NULL, login VARCHAR(128) NOT NULL, password VARCHAR(128) NOT NULL, reception_folder VARCHAR(128) NOT NULL, repository_prefix_name VARCHAR(128) NOT NULL, passive TINYINT(1) NOT NULL, tls TINYINT(1) NOT NULL, max_retry INT NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_62DA9661A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE FtpCredential ADD CONSTRAINT FK_62DA9661A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE FtpCredential");
    }
}
