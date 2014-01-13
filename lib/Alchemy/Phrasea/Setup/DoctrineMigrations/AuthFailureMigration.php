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

class AuthFailureMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('AuthFailures');
    }

    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS AuthFailures (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(128) NOT NULL, ip VARCHAR(128) DEFAULT NULL, locked TINYINT(1) NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("DROP TABLE AuthFailures");
    }
}
