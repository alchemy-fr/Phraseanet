<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class patch_387alpha3a implements patchInterface
{
    /** @var string */
    private $release = '3.8.7-alpha.3';

    /** @var array */
    private $concern = array(base::APPLICATION_BOX);

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $sqls = [];
        $sqls[] = 'CREATE TABLE Secrets (id INT AUTO_INCREMENT NOT NULL, creator_id INT NOT NULL, created DATETIME NOT NULL, token VARCHAR(40) COLLATE utf8_bin NOT NULL, INDEX IDX_48F428861220EA6 (creator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB';
        $sqls[] = 'ALTER TABLE Secrets ADD CONSTRAINT FK_48F428861220EA6 FOREIGN KEY (creator_id) REFERENCES Users (id)';

        foreach ($sqls as $sql) {
            $appbox->get_connection()->exec($sql);
        }

        return true;
    }
}
