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

class FtpExportMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('FtpExports');
    }

    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS FtpExportElements (id INT AUTO_INCREMENT NOT NULL, export_id INT DEFAULT NULL, record_id INT NOT NULL, base_id INT NOT NULL, subdef VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL, folder VARCHAR(255) DEFAULT NULL, error TINYINT(1) NOT NULL, done TINYINT(1) NOT NULL, businessfields TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_7BF0AE1264CDAF82 (export_id), INDEX done (done), INDEX error (error), UNIQUE INDEX unique_ftp_export (export_id, base_id, record_id, subdef), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS FtpExports (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, crash INT NOT NULL, nbretry INT NOT NULL, mail LONGTEXT DEFAULT NULL, addr LONGTEXT NOT NULL, use_ssl TINYINT(1) NOT NULL, login LONGTEXT DEFAULT NULL, pwd LONGTEXT DEFAULT NULL, passif TINYINT(1) NOT NULL, destfolder LONGTEXT NOT NULL, sendermail LONGTEXT DEFAULT NULL, text_mail_sender LONGTEXT DEFAULT NULL, text_mail_receiver LONGTEXT DEFAULT NULL, foldertocreate LONGTEXT DEFAULT NULL, logfile TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_CFCEEE7AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE FtpExportElements ADD CONSTRAINT FK_7BF0AE1264CDAF82 FOREIGN KEY (export_id) REFERENCES FtpExports (id)");
        $this->addSql("ALTER TABLE FtpExports ADD CONSTRAINT FK_CFCEEE7AA76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("ALTER TABLE FtpExportElements DROP FOREIGN KEY FK_7BF0AE1264CDAF82");
        $this->addSql("DROP TABLE FtpExportElements");
        $this->addSql("DROP TABLE FtpExports");
    }
}
