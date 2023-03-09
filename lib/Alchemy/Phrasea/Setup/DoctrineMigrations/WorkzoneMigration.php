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

class WorkzoneMigration extends AbstractMigration
{
    public function isAlreadyApplied()
    {
        return $this->tableExists('StoryWZ');
    }

    public function doUpSql(Schema $schema)
    {

        $this->addSql("CREATE TABLE IF NOT EXISTS Baskets (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, pusher_id INT DEFAULT NULL, name VARCHAR(128) NOT NULL, description LONGTEXT DEFAULT NULL, is_read TINYINT(1) NOT NULL, archived TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_13461873A76ED395 (user_id), INDEX IDX_13461873C2D98306 (pusher_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS StoryWZ (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, sbas_id INT NOT NULL, record_id INT NOT NULL, created DATETIME NOT NULL, INDEX IDX_E0D2CBAEA76ED395 (user_id), UNIQUE INDEX user_story (user_id, sbas_id, record_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS ValidationSessions (id INT AUTO_INCREMENT NOT NULL, initiator_id INT NOT NULL, basket_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, expires DATETIME DEFAULT NULL, INDEX IDX_5B9DFB067DB3B714 (initiator_id), UNIQUE INDEX UNIQ_5B9DFB061BE1FB52 (basket_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS ValidationDatas (id INT AUTO_INCREMENT NOT NULL, participant_id INT DEFAULT NULL, basket_element_id INT DEFAULT NULL, agreement TINYINT(1) DEFAULT NULL, note LONGTEXT DEFAULT NULL, updated DATETIME NOT NULL, INDEX IDX_70E84DDC9D1C3019 (participant_id), INDEX IDX_70E84DDCE989605 (basket_element_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS BasketElements (id INT AUTO_INCREMENT NOT NULL, basket_id INT DEFAULT NULL, record_id INT NOT NULL, sbas_id INT NOT NULL, ord INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_C0B7ECB71BE1FB52 (basket_id), UNIQUE INDEX unique_recordcle (basket_id, sbas_id, record_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS ValidationParticipants (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, is_aware TINYINT(1) NOT NULL, is_confirmed TINYINT(1) NOT NULL, can_agree TINYINT(1) NOT NULL, can_see_others TINYINT(1) NOT NULL, reminded DATETIME DEFAULT NULL, validation_session_id INT DEFAULT NULL, INDEX IDX_17850D7BF25B0F5B (validation_session_id), INDEX IDX_17850D7BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE Baskets ADD CONSTRAINT FK_13461873A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("ALTER TABLE Baskets ADD CONSTRAINT FK_13461873C2D98306 FOREIGN KEY (pusher_id) REFERENCES Users (id)");
        $this->addSql("ALTER TABLE StoryWZ ADD CONSTRAINT FK_E0D2CBAEA76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("ALTER TABLE ValidationSessions ADD CONSTRAINT FK_5B9DFB067DB3B714 FOREIGN KEY (initiator_id) REFERENCES Users (id)");
        $this->addSql("ALTER TABLE ValidationSessions ADD CONSTRAINT FK_5B9DFB061BE1FB52 FOREIGN KEY (basket_id) REFERENCES Baskets (id)");
        $this->addSql("ALTER TABLE ValidationDatas ADD CONSTRAINT FK_70E84DDC9D1C3019 FOREIGN KEY (participant_id) REFERENCES ValidationParticipants (id)");
        $this->addSql("ALTER TABLE ValidationDatas ADD CONSTRAINT FK_70E84DDCE989605 FOREIGN KEY (basket_element_id) REFERENCES BasketElements (id)");
        $this->addSql("ALTER TABLE BasketElements ADD CONSTRAINT FK_C0B7ECB71BE1FB52 FOREIGN KEY (basket_id) REFERENCES Baskets (id)");
        $this->addSql("ALTER TABLE ValidationParticipants ADD CONSTRAINT FK_17850D7BF25B0F5B FOREIGN KEY (validation_session_id) REFERENCES ValidationSessions (id)");
        $this->addSql("ALTER TABLE ValidationParticipants ADD CONSTRAINT FK_17850D7BA76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
    }

    public function doDownSql(Schema $schema)
    {

        $this->addSql("ALTER TABLE ValidationSessions DROP FOREIGN KEY FK_5B9DFB061BE1FB52");
        $this->addSql("ALTER TABLE Orders DROP FOREIGN KEY FK_E283F8D81BE1FB52");
        $this->addSql("ALTER TABLE BasketElements DROP FOREIGN KEY FK_C0B7ECB71BE1FB52");
        $this->addSql("ALTER TABLE ValidationParticipants DROP FOREIGN KEY FK_17850D7BF25B0F5B");
        $this->addSql("ALTER TABLE ValidationDatas DROP FOREIGN KEY FK_70E84DDCE989605");
        $this->addSql("ALTER TABLE ValidationDatas DROP FOREIGN KEY FK_70E84DDC9D1C3019");
        $this->addSql("DROP TABLE Baskets");
        $this->addSql("DROP TABLE StoryWZ");
        $this->addSql("DROP TABLE ValidationSessions");
        $this->addSql("DROP TABLE ValidationDatas");
        $this->addSql("DROP TABLE BasketElements");
        $this->addSql("DROP TABLE ValidationParticipants");
    }
}
