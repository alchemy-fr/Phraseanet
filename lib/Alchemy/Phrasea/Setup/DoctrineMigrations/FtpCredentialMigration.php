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

class FtpCredentialMigration extends AbstractMigration
{
    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS FtpCredential (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, usrId INT NOT NULL, active TINYINT(1) NOT NULL, address VARCHAR(128) NOT NULL, login VARCHAR(128) NOT NULL, password VARCHAR(128) NOT NULL, reception_folder VARCHAR(128) NOT NULL, repository_prefix_name VARCHAR(128) NOT NULL, passive TINYINT(1) NOT NULL, tls TINYINT(1) NOT NULL, max_retry INT NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_62DA9661A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("DROP TABLE FtpCredential");
    }
}
