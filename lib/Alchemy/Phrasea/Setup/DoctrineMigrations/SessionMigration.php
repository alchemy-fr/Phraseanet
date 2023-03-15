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

class SessionMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return false;
    }

    public function doUpSql(Schema $schema)
    {
        if (! $schema->hasTable('Sessions')) {
            $this->addSql("CREATE TABLE IF NOT EXISTS Sessions (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, user_agent VARCHAR(512) NOT NULL, ip_address VARCHAR(40) DEFAULT NULL, platform VARCHAR(128) DEFAULT NULL, browser_name VARCHAR(128) DEFAULT NULL, browser_version VARCHAR(32) DEFAULT NULL, screen_width INT DEFAULT NULL, screen_height INT DEFAULT NULL, token VARCHAR(128) DEFAULT NULL, nonce VARCHAR(16) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_6316FF455F37A13B (token), INDEX IDX_6316FF45A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
            $this->addSql("CREATE TABLE IF NOT EXISTS SessionModules (id INT AUTO_INCREMENT NOT NULL, session_id INT DEFAULT NULL, module_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_BA36EF49613FECDF (session_id), UNIQUE INDEX unique_module (session_id, module_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
            $this->addSql("ALTER TABLE SessionModules ADD CONSTRAINT FK_BA36EF49613FECDF FOREIGN KEY (session_id) REFERENCES Sessions (id)");
            $this->addSql("ALTER TABLE Sessions ADD CONSTRAINT FK_6316FF45A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        }
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("ALTER TABLE SessionModules DROP FOREIGN KEY FK_BA36EF49613FECDF");
        $this->addSql("DROP TABLE SessionModules");
        $this->addSql("DROP TABLE Sessions");
    }
}
