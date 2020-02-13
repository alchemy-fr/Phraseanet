<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Alchemy\Phrasea\Application;


class patch_4011 implements patchInterface
{
    /** @var string */
    private $release = '4.0.11';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

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
    public function getDoctrineMigrations()
    {
        return [];
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
        foreach(['type', 'created', 'updated', 'expiration'] as $t) {
            $sql = "ALTER TABLE `Tokens` ADD INDEX `".$t."` (`".$t."`);";
            try {
                $stmt = $appbox->get_connection()->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            }
            catch (\Exception $e) {
                // the index already exists ?
            }
        }

        return true;
    }
}
