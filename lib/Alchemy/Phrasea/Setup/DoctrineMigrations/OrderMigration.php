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

class OrderMigration extends AbstractMigration
{
    public function doUpSql(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS Orders (id INT AUTO_INCREMENT NOT NULL, basket_id INT DEFAULT NULL, usr_id INT NOT NULL, order_usage VARCHAR(2048) NOT NULL, todo INT DEFAULT NULL, deadline DATETIME NOT NULL, created_on DATETIME NOT NULL, UNIQUE INDEX UNIQ_E283F8D81BE1FB52 (basket_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS OrderElements (id INT AUTO_INCREMENT NOT NULL, order_id INT DEFAULT NULL, base_id INT NOT NULL, record_id INT NOT NULL, order_master_id INT DEFAULT NULL, deny TINYINT(1) DEFAULT NULL, INDEX IDX_8C7066C88D9F6D38 (order_id), UNIQUE INDEX unique_ordercle (base_id, record_id, order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE Orders ADD CONSTRAINT FK_E283F8D81BE1FB52 FOREIGN KEY (basket_id) REFERENCES Baskets (id)");
        $this->addSql("ALTER TABLE OrderElements ADD CONSTRAINT FK_8C7066C88D9F6D38 FOREIGN KEY (order_id) REFERENCES Orders (id)");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("ALTER TABLE OrderElements DROP FOREIGN KEY FK_8C7066C88D9F6D38");
        $this->addSql("DROP TABLE Orders");
        $this->addSql("DROP TABLE OrderElements");
    }
}
