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
        $this->addSql("CREATE TABLE IF NOT EXISTS Users (id INT AUTO_INCREMENT NOT NULL, last_model INT DEFAULT NULL, model_of INT DEFAULT NULL, login VARCHAR(128) NOT NULL, email VARCHAR(128) DEFAULT NULL, password VARCHAR(128) DEFAULT NULL, nonce VARCHAR(64) DEFAULT NULL, salted_password TINYINT(1) NOT NULL, first_name VARCHAR(64) NOT NULL, last_name VARCHAR(64) NOT NULL, gender SMALLINT DEFAULT NULL, address LONGTEXT NOT NULL, city VARCHAR(64) NOT NULL, country VARCHAR(64) DEFAULT NULL, zip_code VARCHAR(32) NOT NULL, geoname_id INT DEFAULT NULL, locale VARCHAR(8) DEFAULT NULL, timezone VARCHAR(128) NOT NULL, job VARCHAR(128) NOT NULL, activity VARCHAR(256) NOT NULL, company VARCHAR(64) NOT NULL, phone VARCHAR(32) NOT NULL, fax VARCHAR(32) NOT NULL, admin TINYINT(1) NOT NULL, guest TINYINT(1) NOT NULL, mail_notifications TINYINT(1) NOT NULL, request_notifications TINYINT(1) NOT NULL, ldap_created TINYINT(1) NOT NULL, push_list LONGTEXT NOT NULL, can_change_profil TINYINT(1) NOT NULL, can_change_ftp_profil TINYINT(1) NOT NULL, last_connection DATETIME DEFAULT NULL, mail_locked TINYINT(1) NOT NULL, deleted TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_D5428AEDB5DE44C2 (last_model), INDEX IDX_D5428AEDC121714D (model_of), INDEX salted_password (salted_password), INDEX admin (admin), INDEX guest (guest), UNIQUE INDEX email_unique (email), UNIQUE INDEX login_unique (login), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE Users ADD CONSTRAINT FK_D5428AEDB5DE44C2 FOREIGN KEY (last_model) REFERENCES Users (id)");
        $this->addSql("ALTER TABLE Users ADD CONSTRAINT FK_D5428AEDC121714D FOREIGN KEY (model_of) REFERENCES Users (id)");
        $this->addSql('ALTER TABLE Users CHANGE login login VARCHAR(128) COLLATE utf8_bin NOT NULL, CHANGE password password VARCHAR(128) COLLATE utf8_bin DEFAULT NULL, CHANGE nonce nonce VARCHAR(64) COLLATE utf8_bin DEFAULT NULL, CHANGE salted_password salted_password TINYINT(1) DEFAULT \'1\' NOT NULL, CHANGE first_name first_name VARCHAR(64) DEFAULT \'\' NOT NULL, CHANGE last_name last_name VARCHAR(64) DEFAULT \'\' NOT NULL, CHANGE address address VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE city city VARCHAR(64) DEFAULT \'\' NOT NULL, CHANGE country country VARCHAR(64) DEFAULT \'\', CHANGE zip_code zip_code VARCHAR(32) DEFAULT \'\' NOT NULL, CHANGE timezone timezone VARCHAR(128) DEFAULT \'\' NOT NULL, CHANGE job job VARCHAR(128) DEFAULT \'\' NOT NULL, CHANGE activity activity VARCHAR(256) DEFAULT \'\' NOT NULL, CHANGE company company VARCHAR(64) DEFAULT \'\' NOT NULL, CHANGE phone phone VARCHAR(32) DEFAULT \'\' NOT NULL, CHANGE fax fax VARCHAR(32) DEFAULT \'\' NOT NULL, CHANGE admin admin TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE guest guest TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE mail_notifications mail_notifications TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE request_notifications request_notifications TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE ldap_created ldap_created TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE push_list push_list VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE can_change_profil can_change_profil TINYINT(1) DEFAULT \'1\' NOT NULL, CHANGE can_change_ftp_profil can_change_ftp_profil TINYINT(1) DEFAULT \'1\' NOT NULL, CHANGE mail_locked mail_locked TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE deleted deleted TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql('ALTER TABLE Users CHANGE login login VARCHAR(128) NOT NULL, CHANGE password password VARCHAR(128) DEFAULT NULL, CHANGE nonce nonce VARCHAR(64) DEFAULT NULL, CHANGE salted_password salted_password TINYINT(1) NOT NULL, CHANGE first_name first_name VARCHAR(64) NOT NULL, CHANGE last_name last_name VARCHAR(64) NOT NULL, CHANGE address address LONGTEXT NOT NULL, CHANGE city city VARCHAR(64) NOT NULL, CHANGE country country VARCHAR(64) DEFAULT NULL, CHANGE zip_code zip_code VARCHAR(32) NOT NULL, CHANGE timezone timezone VARCHAR(128) NOT NULL, CHANGE job job VARCHAR(128) NOT NULL, CHANGE activity activity VARCHAR(256) NOT NULL, CHANGE company company VARCHAR(64) NOT NULL, CHANGE phone phone VARCHAR(32) NOT NULL, CHANGE fax fax VARCHAR(32) NOT NULL, CHANGE admin admin TINYINT(1) NOT NULL, CHANGE guest guest TINYINT(1) NOT NULL, CHANGE mail_notifications mail_notifications TINYINT(1) NOT NULL, CHANGE request_notifications request_notifications TINYINT(1) NOT NULL, CHANGE ldap_created ldap_created TINYINT(1) NOT NULL, CHANGE push_list push_list LONGTEXT NOT NULL, CHANGE can_change_profil can_change_profil TINYINT(1) NOT NULL, CHANGE can_change_ftp_profil can_change_ftp_profil TINYINT(1) NOT NULL, CHANGE mail_locked mail_locked TINYINT(1) NOT NULL, CHANGE deleted deleted TINYINT(1) NOT NULL');
        $this->addSql("ALTER TABLE Users DROP FOREIGN KEY FK_D5428AEDB5DE44C2");
        $this->addSql("ALTER TABLE Users DROP FOREIGN KEY FK_D5428AEDC121714D");
        $this->addSql("DROP TABLE Users");
    }
}
