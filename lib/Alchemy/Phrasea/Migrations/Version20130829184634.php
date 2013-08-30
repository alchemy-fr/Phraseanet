<?php

namespace Alchemy\Phrasea\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130829184634 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE OrderElements CHANGE deny denied TINYINT(1) DEFAULT NULL");
        $this->addSql("ALTER TABLE Baskets CHANGE is_read `read` TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE Orders CHANGE created_on created DATETIME NOT NULL");
        $this->addSql("ALTER TABLE ValidationParticipants ADD aware TINYINT(1) NOT NULL, ADD confirmed TINYINT(1) NOT NULL, DROP is_aware, DROP is_confirmed");
        $this->addSql("ALTER TABLE Sessions CHANGE screen_height screen_heigh INT DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("ALTER TABLE Baskets CHANGE read is_read TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE OrderElements CHANGE denied deny TINYINT(1) DEFAULT NULL");
        $this->addSql("ALTER TABLE Orders CHANGE created created_on DATETIME NOT NULL");
        $this->addSql("ALTER TABLE Sessions CHANGE screen_heigh screen_height INT DEFAULT NULL");
        $this->addSql("ALTER TABLE ValidationParticipants ADD is_aware TINYINT(1) NOT NULL, ADD is_confirmed TINYINT(1) NOT NULL, DROP aware, DROP confirmed");
    }
}
