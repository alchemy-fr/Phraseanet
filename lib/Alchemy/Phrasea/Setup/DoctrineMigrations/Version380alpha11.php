<?php

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version380alpha11 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE IF NOT EXISTS SessionModules (id INT AUTO_INCREMENT NOT NULL, session_id INT DEFAULT NULL, module_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_BA36EF49613FECDF (session_id), UNIQUE INDEX unique_module (session_id, module_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS Sessions (id INT AUTO_INCREMENT NOT NULL, usr_id INT NOT NULL, user_agent VARCHAR(512) NOT NULL, ip_address VARCHAR(40) DEFAULT NULL, platform VARCHAR(128) DEFAULT NULL, browser_name VARCHAR(128) DEFAULT NULL, browser_version VARCHAR(32) DEFAULT NULL, screen_width INT DEFAULT NULL, screen_height INT DEFAULT NULL, token VARCHAR(128) DEFAULT NULL, nonce VARCHAR(16) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_6316FF455F37A13B (token), INDEX usr_id (usr_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE SessionModules ADD CONSTRAINT FK_BA36EF49613FECDF FOREIGN KEY (session_id) REFERENCES Sessions (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE SessionModules DROP FOREIGN KEY FK_BA36EF49613FECDF");
        $this->addSql("DROP TABLE SessionModules");
        $this->addSql("DROP TABLE Sessions");
    }
}
