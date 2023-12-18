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

class UserQueryMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('UserQueries');
    }

    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS UserQueries (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, query VARCHAR(256) NOT NULL, created DATETIME NOT NULL, INDEX IDX_5FB80D87A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE UserQueries ADD CONSTRAINT FK_5FB80D87A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("DROP TABLE UserQueries");
    }
}
