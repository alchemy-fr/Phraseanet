<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_381alpha3a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.1-alpha.3';

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
        $propSql = $propArgs = [];
        $n = 0;

        foreach ($app['settings']->getUsersSettings() as $prop => $value) {
            if ('start_page_query' === $prop) {
                continue;
            }
            $propSql[] = '(prop = :prop_'.$n.' AND value = :value_'.$n.')';
            $propArgs[':prop_'.$n] = $prop;
            $propArgs[':value_'.$n] = $value;
            $n++;
        }

        $sql = "DELETE FROM usr_settings
                WHERE 1 AND (".implode(' OR ', $propSql)." OR value IS NULL OR (value = 1 AND prop LIKE 'notification_%'))";

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($propArgs);
        $stmt->closeCursor();

        return true;
    }
}
