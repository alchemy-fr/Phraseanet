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
class DeleteUsrIdFieldMigration extends AbstractMigration
{
    public function doUpSql(Schema $schema)
    {
        $this->addSql("DROP INDEX unique_owner ON UsrListOwners");
        $this->addSql("ALTER TABLE UsrListOwners DROP usr_id");
        $this->addSql("CREATE UNIQUE INDEX unique_owner ON UsrListOwners (user_id, id)");
        $this->addSql("ALTER TABLE FtpCredential DROP usrId");
        $this->addSql("DROP INDEX usr_id ON Sessions");
        $this->addSql("ALTER TABLE Sessions DROP usr_id");
        $this->addSql("ALTER TABLE Baskets DROP usr_id");
        $this->addSql("DROP INDEX user_story ON StoryWZ");
        $this->addSql("ALTER TABLE StoryWZ DROP usr_id");
        $this->addSql("CREATE UNIQUE INDEX user_story ON StoryWZ (user_id, sbas_id, record_id)");
        $this->addSql("ALTER TABLE UserNotificationSettings DROP usr_id");
        $this->addSql("ALTER TABLE UserSettings DROP usr_id");
        $this->addSql("ALTER TABLE Orders DROP usr_id");
        $this->addSql("ALTER TABLE UserQueries DROP usr_id");
        $this->addSql("ALTER TABLE LazaretSessions DROP usr_id");
        $this->addSql("ALTER TABLE ValidationParticipants DROP usr_id");
        $this->addSql("ALTER TABLE FeedPublishers DROP usr_id");
        $this->addSql("ALTER TABLE FtpExports DROP usr_id");
        $this->addSql("DROP INDEX unique_provider_per_user ON UsrAuthProviders");
        $this->addSql("ALTER TABLE UsrAuthProviders DROP usr_id");
        $this->addSql("CREATE UNIQUE INDEX unique_provider_per_user ON UsrAuthProviders (user_id, provider)");
        $this->addSql("ALTER TABLE FeedTokens DROP usr_id");
        $this->addSql("DROP INDEX unique_usr_per_list ON UsrListsContent");
        $this->addSql("ALTER TABLE UsrListsContent DROP usr_id");
        $this->addSql("CREATE UNIQUE INDEX unique_usr_per_list ON UsrListsContent (user_id, list_id)");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("ALTER TABLE Baskets ADD usr_id INT NOT NULL");
        $this->addSql("ALTER TABLE FeedPublishers ADD usr_id INT NOT NULL");
        $this->addSql("ALTER TABLE FeedTokens ADD usr_id INT NOT NULL");
        $this->addSql("ALTER TABLE FtpCredential ADD usrId INT NOT NULL");
        $this->addSql("ALTER TABLE FtpExports ADD usr_id INT NOT NULL");
        $this->addSql("ALTER TABLE LazaretSessions ADD usr_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE Orders ADD usr_id INT NOT NULL");
        $this->addSql("ALTER TABLE Sessions ADD usr_id INT NOT NULL");
        $this->addSql("CREATE INDEX usr_id ON Sessions (usr_id)");
        $this->addSql("DROP INDEX user_story ON StoryWZ");
        $this->addSql("ALTER TABLE StoryWZ ADD usr_id INT NOT NULL");
        $this->addSql("CREATE UNIQUE INDEX user_story ON StoryWZ (usr_id, sbas_id, record_id)");
        $this->addSql("ALTER TABLE UserNotificationSettings ADD usr_id INT NOT NULL");
        $this->addSql("ALTER TABLE UserQueries ADD usr_id INT NOT NULL");
        $this->addSql("ALTER TABLE UserSettings ADD usr_id INT NOT NULL");
        $this->addSql("DROP INDEX unique_provider_per_user ON UsrAuthProviders");
        $this->addSql("ALTER TABLE UsrAuthProviders ADD usr_id INT NOT NULL");
        $this->addSql("CREATE UNIQUE INDEX unique_provider_per_user ON UsrAuthProviders (usr_id, provider)");
        $this->addSql("DROP INDEX unique_owner ON UsrListOwners");
        $this->addSql("ALTER TABLE UsrListOwners ADD usr_id INT NOT NULL");
        $this->addSql("CREATE UNIQUE INDEX unique_owner ON UsrListOwners (usr_id, id)");
        $this->addSql("DROP INDEX unique_usr_per_list ON UsrListsContent");
        $this->addSql("ALTER TABLE UsrListsContent ADD usr_id INT NOT NULL");
        $this->addSql("CREATE UNIQUE INDEX unique_usr_per_list ON UsrListsContent (usr_id, list_id)");
        $this->addSql("ALTER TABLE ValidationParticipants ADD usr_id INT NOT NULL");
    }
}
