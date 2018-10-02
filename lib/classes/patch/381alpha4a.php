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

class patch_381alpha4a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.1-alpha.4';

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
        $sql = "SELECT usr_id, prop, value FROM usr_settings
                WHERE prop = 'editing_top_box'
                  OR prop = 'editing_right_box'
                  OR prop = 'editing_left_box'";

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = 'UPDATE usr_settings SET value = :value
                WHERE usr_id = :usr_id
                    AND prop = :prop';
        $stmt = $appbox->get_connection()->prepare($sql);

        foreach ($rows as $row) {
            $value = $row['value'];

            if ('px' === substr($value, -2)) {
                $value = 35;
            } elseif ('%' === substr($value, -1)) {
                $value = substr($value, 0, -1);
            }

            $stmt->execute([':value' => $value, ':usr_id' => $row['usr_id'], ':prop' => $row['prop']]);
        }

        $stmt->closeCursor();

        return true;
    }
}
