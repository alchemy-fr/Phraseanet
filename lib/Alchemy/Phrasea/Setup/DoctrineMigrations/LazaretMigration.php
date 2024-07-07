<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class LazaretMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('LazaretFiles');
    }

    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS LazaretChecks (id INT AUTO_INCREMENT NOT NULL, lazaret_file_id INT DEFAULT NULL, checkClassname VARCHAR(512) NOT NULL, INDEX IDX_CE873ED44CF84ADD (lazaret_file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS LazaretSessions (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_40A81317A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS LazaretAttributes (id INT AUTO_INCREMENT NOT NULL, lazaret_file_id INT DEFAULT NULL, name VARCHAR(64) NOT NULL, value VARCHAR(2048) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_5FF72F9B4CF84ADD (lazaret_file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS LazaretFiles (id INT AUTO_INCREMENT NOT NULL, lazaret_session_id INT DEFAULT NULL, filename VARCHAR(512) NOT NULL, thumbFilename VARCHAR(512) NOT NULL, originalName VARCHAR(256) NOT NULL, base_id INT NOT NULL, uuid VARCHAR(36) NOT NULL, sha256 VARCHAR(64) NOT NULL, forced TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_D30BD768EE187C01 (lazaret_session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE LazaretChecks ADD CONSTRAINT FK_CE873ED44CF84ADD FOREIGN KEY (lazaret_file_id) REFERENCES LazaretFiles (id)");
        $this->addSql("ALTER TABLE LazaretSessions ADD CONSTRAINT FK_40A81317A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("ALTER TABLE LazaretAttributes ADD CONSTRAINT FK_5FF72F9B4CF84ADD FOREIGN KEY (lazaret_file_id) REFERENCES LazaretFiles (id)");
        $this->addSql("ALTER TABLE LazaretFiles ADD CONSTRAINT FK_D30BD768EE187C01 FOREIGN KEY (lazaret_session_id) REFERENCES LazaretSessions (id)");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("ALTER TABLE LazaretFiles DROP FOREIGN KEY FK_D30BD768EE187C01");
        $this->addSql("ALTER TABLE LazaretChecks DROP FOREIGN KEY FK_CE873ED44CF84ADD");
        $this->addSql("ALTER TABLE LazaretAttributes DROP FOREIGN KEY FK_5FF72F9B4CF84ADD");
        $this->addSql("DROP TABLE LazaretChecks");
        $this->addSql("DROP TABLE LazaretSessions");
        $this->addSql("DROP TABLE LazaretAttributes");
        $this->addSql("DROP TABLE LazaretFiles");
    }
}
