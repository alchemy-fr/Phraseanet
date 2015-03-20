<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_390alpha20a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.20';

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
        $perRequest = 100;
        $offset = 0;
        do {
            $sql = sprintf('SELECT id, datas, type FROM notifications ORDER BY id ASC LIMIT %d, %d', $offset, $perRequest);
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $sql = 'UPDATE notifications SET datas = :json WHERE id = :id';
            $stmt = $appbox->get_connection()->prepare($sql);

            foreach ($rs as $row) {
                $json = $row['datas'];

                if (false !== ($sx = @simplexml_load_string($row['datas']))) {
                    $data = [];
                    foreach ($sx->children() as $name => $value) {
                        $data[$name] = (string) $value;
                    }
                    if ($row['type'] === 'notify_uploadquarantine') {
                        $data['reasons'] = [];
                        if (isset($sx->reasons)) {
                            foreach ($sx->reasons as $reason) {
                                $data['reasons'][] = (string) $reason->checkClassName;
                            }
                        }
                    }
                    if (in_array($row['type'], ['notify_autoregister', 'notify_register'])) {
                        $data['base_ids'] = [];
                        if (isset($sx->base_ids)) {
                            foreach ($sx->base_ids as $base_id) {
                                $data['base_ids'][] = (int) $base_id->base_id;
                            }
                        }
                    }
                    $json = json_encode($data);
                }

                $stmt->execute([':id' => $row['id'], ':json' => $json]);
            }
            $stmt->closeCursor();

            $offset += $perRequest;
        } while (count($rs) > 0);

        return true;
    }
}
