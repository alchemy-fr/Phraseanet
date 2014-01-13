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

class UserMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('Users');
    }

    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS Users (id INT AUTO_INCREMENT NOT NULL, model_of INT DEFAULT NULL, login VARCHAR(128) NOT NULL, email VARCHAR(128) DEFAULT NULL, password VARCHAR(128) DEFAULT NULL, nonce VARCHAR(16) DEFAULT NULL, salted_password TINYINT(1) NOT NULL, first_name VARCHAR(64) NOT NULL, last_name VARCHAR(64) NOT NULL, gender VARCHAR(8) DEFAULT NULL, address LONGTEXT NOT NULL, city VARCHAR(64) NOT NULL, country VARCHAR(64) NOT NULL, zip_code VARCHAR(32) NOT NULL, geoname_id INT DEFAULT NULL, locale VARCHAR(8) DEFAULT NULL, timezone VARCHAR(128) NOT NULL, job VARCHAR(128) NOT NULL, activity VARCHAR(256) NOT NULL, company VARCHAR(64) NOT NULL, phone VARCHAR(32) NOT NULL, fax VARCHAR(32) NOT NULL, admin TINYINT(1) NOT NULL, guest TINYINT(1) NOT NULL, mail_notifications TINYINT(1) NOT NULL, request_notifications TINYINT(1) NOT NULL, ldap_created TINYINT(1) NOT NULL, last_model VARCHAR(64) DEFAULT NULL, push_list LONGTEXT NOT NULL, can_change_profil TINYINT(1) NOT NULL, can_change_ftp_profil TINYINT(1) NOT NULL, last_connection DATETIME DEFAULT NULL, mail_locked TINYINT(1) NOT NULL, deleted TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_D5428AEDC121714D (model_of), INDEX salted_password (salted_password), INDEX admin (admin), INDEX guest (guest), UNIQUE INDEX email_unique (email), UNIQUE INDEX login_unique (login), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE Users ADD CONSTRAINT FK_D5428AEDC121714D FOREIGN KEY (model_of) REFERENCES Users (id)");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("ALTER TABLE Users DROP FOREIGN KEY FK_D5428AEDC121714D");
        $this->addSql("DROP TABLE Users");
    }
}
