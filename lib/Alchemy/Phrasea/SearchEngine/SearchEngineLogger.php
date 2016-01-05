<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\Application;

class SearchEngineLogger
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function log(\databox $databox, $query, $answers, array $coll_ids)
    {
        $conn = $databox->get_connection();

        $sql = "INSERT INTO log_search
           (id, log_id, date, search, results, coll_id )
           VALUES
           (null, :log_id, :date, :query, :nbresults, :colls)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            ':log_id'    => $this->app['phraseanet.logger']($databox)->get_id(),
            ':date'      => date("Y-m-d H:i:s"),
            ':query'     => $query,
            ':nbresults' => $answers,
            ':colls'     => implode(',', array_map(function ($coll_id) {
                                return (int) $coll_id;
                            }, $coll_ids)),
        ]);

        $stmt->closeCursor();

        return $this;
    }

}
