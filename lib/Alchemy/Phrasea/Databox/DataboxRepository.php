<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DataboxRepository implements DataboxRepositoryInterface
{
    /** @var Application */
    private $app;
    /** @var \appbox */
    private $appbox;

    public function __construct(Application $app, \appbox $appbox)
    {
        $this->app = $app;
        $this->appbox = $appbox;
    }

    /**
     * @param int $id
     * @return \databox|null
     */
    public function find($id)
    {
        try {
            $databox = new \databox($this->app, (int)$id);
        } catch (NotFoundHttpException $exception) {
            $databox = null;
        }

        return $databox;
    }

    /**
     * @return \databox[]
     */
    public function findAll()
    {
        try {
            $rows = $this->appbox->get_data_from_cache(\appbox::CACHE_LIST_BASES);
            if (!is_array($rows)) {
                throw new \UnexpectedValueException('Expects rows to be an array');
            }
        } catch(\Exception $e) {
            $connection = $this->appbox->get_connection();

            $query = 'SELECT sbas_id, ord, viewname, label_en, label_fr, label_de, label_nl FROM sbas';
            $statement = $connection->prepare($query);
            $statement->execute();
            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->closeCursor();

            $this->appbox->set_data_to_cache($rows, \appbox::CACHE_LIST_BASES);
        }

        $databoxes = array();

        foreach ($rows as $row) {
            $databox = new \databox($this->app, (int)$row['sbas_id'], $row);

            $databoxes[$databox->get_sbas_id()] = $databox;
        }

        return $databoxes;
    }
}
