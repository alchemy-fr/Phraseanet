<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class AggregateTokenMigration extends AbstractMigration
{
    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS AggregateTokens (id INT AUTO_INCREMENT NOT NULL, usr_id INT NOT NULL, value VARCHAR(12) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("DROP TABLE AggregateTokens");
    }
}
