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
class UserFieldMigration extends AbstractMigration
{
    public function doUpSql(Schema $schema)
    {
        $this->addSql("ALTER TABLE UsrListOwners ADD CONSTRAINT FK_54E9FE23A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_54E9FE23A76ED395 ON UsrListOwners (user_id)");
        $this->addSql("ALTER TABLE Sessions ADD CONSTRAINT FK_6316FF45A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_6316FF45A76ED395 ON Sessions (user_id)");
        $this->addSql("ALTER TABLE Baskets ADD CONSTRAINT FK_13461873A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_13461873A76ED395 ON Baskets (user_id)");
        $this->addSql("ALTER TABLE StoryWZ ADD CONSTRAINT FK_E0D2CBAEA76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_E0D2CBAEA76ED395 ON StoryWZ (user_id)");
        $this->addSql("ALTER TABLE Orders ADD CONSTRAINT FK_E283F8D8A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_E283F8D8A76ED395 ON Orders (user_id)");
        $this->addSql("ALTER TABLE LazaretSessions ADD CONSTRAINT FK_40A81317A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_40A81317A76ED395 ON LazaretSessions (user_id)");
        $this->addSql("ALTER TABLE ValidationParticipants ADD CONSTRAINT FK_17850D7BA76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_17850D7BA76ED395 ON ValidationParticipants (user_id)");
        $this->addSql("ALTER TABLE FeedPublishers ADD CONSTRAINT FK_31AFAB2A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_31AFAB2A76ED395 ON FeedPublishers (user_id)");
        $this->addSql("ALTER TABLE FtpExports ADD CONSTRAINT FK_CFCEEE7AA76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_CFCEEE7AA76ED395 ON FtpExports (user_id)");
        $this->addSql("ALTER TABLE UsrAuthProviders ADD CONSTRAINT FK_947F003FA76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_947F003FA76ED395 ON UsrAuthProviders (user_id)");
        $this->addSql("ALTER TABLE FeedTokens ADD CONSTRAINT FK_9D1CA848A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_9D1CA848A76ED395 ON FeedTokens (user_id)");
        $this->addSql("ALTER TABLE UsrListsContent ADD CONSTRAINT FK_661B8B9A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)");
        $this->addSql("CREATE INDEX IDX_661B8B9A76ED395 ON UsrListsContent (user_id)");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("ALTER TABLE Baskets DROP FOREIGN KEY FK_13461873A76ED395");
        $this->addSql("DROP INDEX IDX_13461873A76ED395 ON Baskets");
        $this->addSql("ALTER TABLE FeedPublishers DROP FOREIGN KEY FK_31AFAB2A76ED395");
        $this->addSql("DROP INDEX IDX_31AFAB2A76ED395 ON FeedPublishers");
        $this->addSql("ALTER TABLE FeedTokens DROP FOREIGN KEY FK_9D1CA848A76ED395");
        $this->addSql("DROP INDEX IDX_9D1CA848A76ED395 ON FeedTokens");
        $this->addSql("ALTER TABLE FtpExports DROP FOREIGN KEY FK_CFCEEE7AA76ED395");
        $this->addSql("DROP INDEX IDX_CFCEEE7AA76ED395 ON FtpExports");
        $this->addSql("ALTER TABLE LazaretSessions DROP FOREIGN KEY FK_40A81317A76ED395");
        $this->addSql("DROP INDEX IDX_40A81317A76ED395 ON LazaretSessions");
        $this->addSql("ALTER TABLE Orders DROP FOREIGN KEY FK_E283F8D8A76ED395");
        $this->addSql("DROP INDEX IDX_E283F8D8A76ED395 ON Orders");
        $this->addSql("ALTER TABLE Sessions DROP FOREIGN KEY FK_6316FF45A76ED395");
        $this->addSql("DROP INDEX IDX_6316FF45A76ED395 ON Sessions");
        $this->addSql("ALTER TABLE StoryWZ DROP FOREIGN KEY FK_E0D2CBAEA76ED395");
        $this->addSql("DROP INDEX IDX_E0D2CBAEA76ED395 ON StoryWZ");
        $this->addSql("ALTER TABLE UsrAuthProviders DROP FOREIGN KEY FK_947F003FA76ED395");
        $this->addSql("DROP INDEX IDX_947F003FA76ED395 ON UsrAuthProviders");
        $this->addSql("ALTER TABLE UsrListOwners DROP FOREIGN KEY FK_54E9FE23A76ED395");
        $this->addSql("DROP INDEX IDX_54E9FE23A76ED395 ON UsrListOwners");
        $this->addSql("ALTER TABLE UsrListsContent DROP FOREIGN KEY FK_661B8B9A76ED395");
        $this->addSql("DROP INDEX IDX_661B8B9A76ED395 ON UsrListsContent");
        $this->addSql("ALTER TABLE ValidationParticipants DROP FOREIGN KEY FK_17850D7BA76ED395");
        $this->addSql("DROP INDEX IDX_17850D7BA76ED395 ON ValidationParticipants");
    }
}
