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
class UserListMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('UsrLists');
    }

    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS UsrListOwners (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, list_id INT DEFAULT NULL, role VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_54E9FE23A76ED395 (user_id), INDEX IDX_54E9FE233DAE168B (list_id), UNIQUE INDEX unique_owner (user_id, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS UsrLists (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS UsrListsContent (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, list_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_661B8B9A76ED395 (user_id), INDEX IDX_661B8B93DAE168B (list_id), UNIQUE INDEX unique_usr_per_list (user_id, list_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE UsrListOwners ADD CONSTRAINT FK_54E9FE23A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("ALTER TABLE UsrListOwners ADD CONSTRAINT FK_54E9FE233DAE168B FOREIGN KEY (list_id) REFERENCES UsrLists (id)");
        $this->addSql("ALTER TABLE UsrListsContent ADD CONSTRAINT FK_661B8B9A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("ALTER TABLE UsrListsContent ADD CONSTRAINT FK_661B8B93DAE168B FOREIGN KEY (list_id) REFERENCES UsrLists (id)");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("ALTER TABLE UsrListOwners DROP FOREIGN KEY FK_54E9FE233DAE168B");
        $this->addSql("ALTER TABLE UsrListsContent DROP FOREIGN KEY FK_661B8B93DAE168B");
        $this->addSql("DROP TABLE UsrListOwners");
        $this->addSql("DROP TABLE UsrLists");
        $this->addSql("DROP TABLE UsrListsContent");
    }
}
