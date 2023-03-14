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

class TokenMigration extends AbstractMigration
{
    public function doUpSql(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE IF EXISTS Tokens");
        $this->addSql("CREATE TABLE IF NOT EXISTS Tokens (value VARCHAR(16) NOT NULL, user_id INT DEFAULT NULL, type VARCHAR(32) NOT NULL, data LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, expiration DATETIME DEFAULT NULL, INDEX IDX_ADF614B8A76ED395 (user_id), PRIMARY KEY(value)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE Tokens ADD CONSTRAINT FK_ADF614B8A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
    }

    public function doDownSql(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE Tokens");
    }
}
