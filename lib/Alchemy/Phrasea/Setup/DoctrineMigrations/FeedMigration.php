<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Query\ResultSetMapping;

class FeedMigration extends AbstractMigration
{
    public function doUpSql(Schema $schema)
    {
        $rsm = (new ResultSetMapping())->addScalarResult('Name', 'Name');
        $backup = false;

        foreach ($this->getEntityManager()->createNativeQuery('SHOW TABLE STATUS', $rsm)->getResult() as $row) {
            if ('feeds' === $row['Name']) {
                $backup = true;
                break;
            }
        }

        if ($backup) {
            $this->getEntityManager()->executeQuery('RENAME TABLE `feeds` TO `feeds_backup`');
        }

        $this->addSql("CREATE TABLE IF NOT EXISTS Feeds (id INT AUTO_INCREMENT NOT NULL, public TINYINT(1) NOT NULL, icon_url TINYINT(1) NOT NULL, base_id INT DEFAULT NULL, title VARCHAR(128) NOT NULL, subtitle VARCHAR(1024) DEFAULT NULL, created_on DATETIME NOT NULL, updated_on DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS FeedPublishers (id INT AUTO_INCREMENT NOT NULL, feed_id INT DEFAULT NULL, usr_id INT NOT NULL, owner TINYINT(1) NOT NULL, created_on DATETIME NOT NULL, INDEX IDX_31AFAB251A5BC03 (feed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS FeedEntries (id INT AUTO_INCREMENT NOT NULL, publisher_id INT DEFAULT NULL, feed_id INT DEFAULT NULL, title VARCHAR(128) NOT NULL, subtitle VARCHAR(128) NOT NULL, author_name VARCHAR(128) NOT NULL, author_email VARCHAR(128) NOT NULL, created_on DATETIME NOT NULL, updated_on DATETIME NOT NULL, INDEX IDX_5FC892F940C86FCE (publisher_id), INDEX IDX_5FC892F951A5BC03 (feed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS FeedItems (id INT AUTO_INCREMENT NOT NULL, entry_id INT DEFAULT NULL, record_id INT NOT NULL, sbas_id INT NOT NULL, ord INT NOT NULL, created_on DATETIME NOT NULL, updated_on DATETIME NOT NULL, INDEX IDX_7F9CDFA6BA364942 (entry_id), UNIQUE INDEX lookup_unique_idx (entry_id, sbas_id, record_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS FeedTokens (id INT AUTO_INCREMENT NOT NULL, feed_id INT DEFAULT NULL, usr_id INT NOT NULL, value VARCHAR(12) DEFAULT NULL, INDEX IDX_9D1CA84851A5BC03 (feed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS AggregateTokens (id INT AUTO_INCREMENT NOT NULL, usr_id INT NOT NULL, value VARCHAR(12) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE FeedPublishers ADD CONSTRAINT FK_31AFAB251A5BC03 FOREIGN KEY (feed_id) REFERENCES Feeds (id)");
        $this->addSql("ALTER TABLE FeedEntries ADD CONSTRAINT FK_5FC892F940C86FCE FOREIGN KEY (publisher_id) REFERENCES FeedPublishers (id)");
        $this->addSql("ALTER TABLE FeedEntries ADD CONSTRAINT FK_5FC892F951A5BC03 FOREIGN KEY (feed_id) REFERENCES Feeds (id)");
        $this->addSql("ALTER TABLE FeedItems ADD CONSTRAINT FK_7F9CDFA6BA364942 FOREIGN KEY (entry_id) REFERENCES FeedEntries (id)");
        $this->addSql("ALTER TABLE FeedTokens ADD CONSTRAINT FK_9D1CA84851A5BC03 FOREIGN KEY (feed_id) REFERENCES Feeds (id)");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("ALTER TABLE FeedPublishers DROP FOREIGN KEY FK_31AFAB251A5BC03");
        $this->addSql("ALTER TABLE FeedEntries DROP FOREIGN KEY FK_5FC892F951A5BC03");
        $this->addSql("ALTER TABLE FeedTokens DROP FOREIGN KEY FK_9D1CA84851A5BC03");
        $this->addSql("ALTER TABLE FeedEntries DROP FOREIGN KEY FK_5FC892F940C86FCE");
        $this->addSql("ALTER TABLE FeedItems DROP FOREIGN KEY FK_7F9CDFA6BA364942");
        $this->addSql("DROP TABLE Feeds");
        $this->addSql("DROP TABLE FeedPublishers");
        $this->addSql("DROP TABLE FeedEntries");
        $this->addSql("DROP TABLE FeedItems");
        $this->addSql("DROP TABLE FeedTokens");
        $this->addSql("DROP TABLE AggregateTokens");
    }
}
