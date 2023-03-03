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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class UserAuthProviderMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('UsrAuthProviders');
    }

    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS UsrAuthProviders (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, provider VARCHAR(32) NOT NULL, distant_id VARCHAR(192) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_947F003FA76ED395 (user_id), UNIQUE INDEX unique_provider_per_user (user_id, provider), UNIQUE INDEX provider_ids (provider, distant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE UsrAuthProviders ADD CONSTRAINT FK_947F003FA76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("DROP TABLE UsrAuthProviders");
    }
}
