<?php

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration as BaseMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151209101524 extends BaseMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ApiLogs DROP FOREIGN KEY FK_91E90F309B6B5FBA');
        $this->addSql('ALTER TABLE ApiLogs ADD CONSTRAINT FK_91E90F309B6B5FBA FOREIGN KEY (account_id) REFERENCES ApiAccounts (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ApiLogs DROP FOREIGN KEY FK_91E90F309B6B5FBA');
        $this->addSql('ALTER TABLE ApiLogs ADD CONSTRAINT FK_91E90F309B6B5FBA FOREIGN KEY (account_id) REFERENCES ApiAccounts (id)');
    }
}
