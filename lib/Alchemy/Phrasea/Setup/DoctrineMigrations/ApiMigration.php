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

class ApiMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('ApiApplications');
    }

    public function doUpSql(Schema $schema)
    {
        $this->addSql("DROP TABLE IF EXISTS ApiLogs");
        $this->addSql("DROP TABLE IF EXISTS ApiOauthCodes");
        $this->addSql("DROP TABLE IF EXISTS ApiOauthRefreshTokens");
        $this->addSql("DROP TABLE IF EXISTS ApiOauthTokens");
        $this->addSql("DROP TABLE IF EXISTS ApiAccounts");
        $this->addSql("DROP TABLE IF EXISTS ApiApplications");

        $this->addSql("CREATE TABLE IF NOT EXISTS ApiLogs (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, route VARCHAR(128) DEFAULT NULL, method VARCHAR(16) DEFAULT NULL, created DATETIME NOT NULL, status_code INT DEFAULT NULL, format VARCHAR(64) DEFAULT NULL, resource VARCHAR(64) DEFAULT NULL, general VARCHAR(64) DEFAULT NULL, aspect VARCHAR(64) DEFAULT NULL, action VARCHAR(64) DEFAULT NULL, error_code INT DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, INDEX IDX_91E90F309B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS ApiApplications (id INT AUTO_INCREMENT NOT NULL, creator_id INT DEFAULT NULL, type VARCHAR(128) NOT NULL, name VARCHAR(128) NOT NULL, description LONGTEXT NOT NULL, website VARCHAR(128) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, client_id VARCHAR(32) NOT NULL, client_secret VARCHAR(32) NOT NULL, nonce VARCHAR(64) NOT NULL, redirect_uri VARCHAR(128) NOT NULL, activated TINYINT(1) NOT NULL, grant_password TINYINT(1) NOT NULL, webhook_url VARCHAR(128) DEFAULT NULL, INDEX IDX_53F7BBE661220EA6 (creator_id), UNIQUE INDEX client_id (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS ApiOauthCodes (code VARCHAR(128) NOT NULL, account_id INT NOT NULL, redirect_uri VARCHAR(128) NOT NULL, expires DATETIME DEFAULT NULL, scope VARCHAR(128) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_BE6B11809B6B5FBA (account_id), PRIMARY KEY(code)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS ApiOauthRefreshTokens (refresh_token VARCHAR(128) NOT NULL, account_id INT NOT NULL, expires DATETIME NOT NULL, scope VARCHAR(128) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_7DA42A5A9B6B5FBA (account_id), PRIMARY KEY(refresh_token)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS ApiOauthTokens (oauth_token VARCHAR(128) NOT NULL, account_id INT NOT NULL, session_id INT DEFAULT NULL, expires DATETIME DEFAULT NULL, last_used DATETIME NOT NULL, scope VARCHAR(128) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_4FD469539B6B5FBA (account_id), INDEX session_id (session_id), PRIMARY KEY(oauth_token)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS ApiAccounts (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, application_id INT NOT NULL, revoked TINYINT(1) NOT NULL, api_version VARCHAR(16) NOT NULL, created DATETIME NOT NULL, INDEX IDX_2C54E637A76ED395 (user_id), INDEX IDX_2C54E6373E030ACD (application_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE ApiLogs ADD CONSTRAINT FK_91E90F309B6B5FBA FOREIGN KEY (account_id) REFERENCES ApiAccounts (id)");
        $this->addSql("ALTER TABLE ApiApplications ADD CONSTRAINT FK_53F7BBE661220EA6 FOREIGN KEY (creator_id) REFERENCES Users (id)");
        $this->addSql("ALTER TABLE ApiOauthCodes ADD CONSTRAINT FK_BE6B11809B6B5FBA FOREIGN KEY (account_id) REFERENCES ApiAccounts (id)");
        $this->addSql("ALTER TABLE ApiOauthRefreshTokens ADD CONSTRAINT FK_7DA42A5A9B6B5FBA FOREIGN KEY (account_id) REFERENCES ApiAccounts (id)");
        $this->addSql("ALTER TABLE ApiOauthTokens ADD CONSTRAINT FK_4FD469539B6B5FBA FOREIGN KEY (account_id) REFERENCES ApiAccounts (id)");
        $this->addSql("ALTER TABLE ApiAccounts ADD CONSTRAINT FK_2C54E637A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("ALTER TABLE ApiAccounts ADD CONSTRAINT FK_2C54E6373E030ACD FOREIGN KEY (application_id) REFERENCES ApiApplications (id)");

        $this->addSql('ALTER TABLE ApiAccounts CHANGE revoked revoked TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE ApiApplications CHANGE client_id client_id VARCHAR(128) NOT NULL, CHANGE client_secret client_secret VARCHAR(128) NOT NULL');
        $this->addSql('ALTER TABLE ApiOauthCodes CHANGE expires expires INT NOT NULL');
        $this->addSql('ALTER TABLE ApiOauthRefreshTokens CHANGE expires expires INT NOT NULL');
        $this->addSql('ALTER TABLE ApiOauthTokens CHANGE expires expires INT DEFAULT NULL');
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql('ALTER TABLE ApiAccounts CHANGE revoked revoked TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE ApiApplications CHANGE client_id client_id VARCHAR(32) NOT NULL, CHANGE client_secret client_secret VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE ApiOauthCodes CHANGE expires expires DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE ApiOauthRefreshTokens CHANGE expires expires DATETIME NOT NULL');
        $this->addSql('ALTER TABLE ApiOauthTokens CHANGE expires expires DATETIME DEFAULT NULL');

        $this->addSql("ALTER TABLE ApiAccounts DROP FOREIGN KEY FK_2C54E6373E030ACD");
        $this->addSql("ALTER TABLE ApiLogs DROP FOREIGN KEY FK_91E90F309B6B5FBA");
        $this->addSql("ALTER TABLE ApiOauthCodes DROP FOREIGN KEY FK_BE6B11809B6B5FBA");
        $this->addSql("ALTER TABLE ApiOauthRefreshTokens DROP FOREIGN KEY FK_7DA42A5A9B6B5FBA");
        $this->addSql("ALTER TABLE ApiOauthTokens DROP FOREIGN KEY FK_4FD469539B6B5FBA");
        $this->addSql("DROP TABLE ApiLogs");
        $this->addSql("DROP TABLE ApiApplications");
        $this->addSql("DROP TABLE ApiOauthCodes");
        $this->addSql("DROP TABLE ApiOauthRefreshTokens");
        $this->addSql("DROP TABLE ApiOauthTokens");
        $this->addSql("DROP TABLE ApiAccounts");
    }
}
