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

class UserNotificationSettingMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('UserNotificationSettings');
    }

    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS UserNotificationSettings (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(64) NOT NULL, value VARCHAR(128) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_CFF041AAA76ED395 (user_id), UNIQUE INDEX unique_index (user_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE UserNotificationSettings ADD CONSTRAINT FK_CFF041AAA76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("DROP TABLE UserNotificationSettings");
    }
}
