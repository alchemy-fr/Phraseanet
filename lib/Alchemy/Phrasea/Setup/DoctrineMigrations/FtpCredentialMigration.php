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

class FtpCredentialMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('FtpCredential');
    }

    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS FtpCredential (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, active TINYINT(1) NOT NULL, address VARCHAR(128) NOT NULL, login VARCHAR(128) NOT NULL, password VARCHAR(128) NOT NULL, reception_folder VARCHAR(128) NOT NULL, repository_prefix_name VARCHAR(128) NOT NULL, passive TINYINT(1) NOT NULL, tls TINYINT(1) NOT NULL, max_retry INT NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_62DA9661A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE FtpCredential ADD CONSTRAINT FK_62DA9661A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql('ALTER TABLE FtpCredential CHANGE active active TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE address address VARCHAR(128) DEFAULT \'\' NOT NULL, CHANGE login login VARCHAR(128) DEFAULT \'\' NOT NULL, CHANGE password password VARCHAR(128) DEFAULT \'\' NOT NULL, CHANGE reception_folder reception_folder VARCHAR(128) DEFAULT \'\' NOT NULL, CHANGE repository_prefix_name repository_prefix_name VARCHAR(128) DEFAULT \'\' NOT NULL, CHANGE passive passive TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE tls tls TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE max_retry max_retry INT DEFAULT 5 NOT NULL');
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql('ALTER TABLE FtpCredential CHANGE active active TINYINT(1) NOT NULL, CHANGE address address VARCHAR(128) NOT NULL, CHANGE login login VARCHAR(128) NOT NULL, CHANGE password password VARCHAR(128) NOT NULL, CHANGE reception_folder reception_folder VARCHAR(128) NOT NULL, CHANGE repository_prefix_name repository_prefix_name VARCHAR(128) NOT NULL, CHANGE passive passive TINYINT(1) NOT NULL, CHANGE tls tls TINYINT(1) NOT NULL, CHANGE max_retry max_retry INT NOT NULL');
        $this->addSql("DROP TABLE FtpCredential");
    }
}
