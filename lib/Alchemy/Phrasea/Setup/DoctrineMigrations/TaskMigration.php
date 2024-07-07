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

class TaskMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('Tasks');
    }

    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS Tasks (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, jobId VARCHAR(255) NOT NULL, settings LONGTEXT NOT NULL, completed TINYINT(1) NOT NULL, status VARCHAR(255) NOT NULL, crashed INT NOT NULL, single_run TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, last_execution DATETIME DEFAULT NULL, period INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("DROP TABLE Tasks");
    }
}
