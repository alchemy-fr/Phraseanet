<?php

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161013115559 extends AbstractMigration
{

    public function isAlreadyApplied()
    {
        return $this->tableExists('Orders');
    }

    /**
     * @param Schema $schema
     */
    public function doUpSql(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("CREATE TABLE IF NOT EXISTS WebhookEventPayloads (id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', delivery_id INT DEFAULT NULL, request LONGTEXT NOT NULL, response LONGTEXT NOT NULL, status INT NOT NULL, headers LONGTEXT NOT NULL, UNIQUE INDEX UNIQ_B949629612136921 (delivery_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $this->addSql("ALTER TABLE WebhookEventPayloads ADD CONSTRAINT FK_B949629612136921 FOREIGN KEY (delivery_id) REFERENCES WebhookEventDeliveries (id);");
    }

    /**
     * @param Schema $schema
     */
    public function doDownSql(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE WebhookEventPayloads');
    }
}
