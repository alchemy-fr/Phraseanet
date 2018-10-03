<?php

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration as BaseMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151209101524 extends BaseMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ApiLogs DROP FOREIGN KEY FK_91E90F309B6B5FBA');
        $this->addSql('ALTER TABLE ApiLogs ADD CONSTRAINT FK_91E90F309B6B5FBA FOREIGN KEY (account_id) REFERENCES ApiAccounts (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ApiLogs DROP FOREIGN KEY FK_91E90F309B6B5FBA');
        $this->addSql('ALTER TABLE ApiLogs ADD CONSTRAINT FK_91E90F309B6B5FBA FOREIGN KEY (account_id) REFERENCES ApiAccounts (id)');
    }
}
