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

class RegistrationMigration extends AbstractMigration
{
    public function doUpSql(Schema $schema)
    {
        $this->addSql("DROP TABLE IF EXISTS Registrations");
        $this->addSql("CREATE TABLE Registrations (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, base_id INT NOT NULL, pending TINYINT(1) NOT NULL, rejected TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_7A997C5FA76ED395 (user_id), UNIQUE INDEX unique_registration (user_id, base_id, pending), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
    }

    public function doDownSql(Schema $schema)
    {
        $this->addSql("DROP TABLE Registrations");
    }
}
