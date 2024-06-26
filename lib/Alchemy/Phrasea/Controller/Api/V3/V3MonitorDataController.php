<?php

namespace Alchemy\Phrasea\Controller\Api\V3;

use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Api\InstanceIdAware;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\Exception;
use Alchemy\Phrasea\Utilities\Stopwatch;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class V3MonitorDataController extends Controller
{
    use JsonBodyAware;
    use DispatcherAware;
    use InstanceIdAware;

    /**
     * monitor infos for app
     *
     * @param  Request $request
     *
     * @return Response
     */
    private function unitToMultiplier(string $unit)
    {
        static $map = ['o', 'ko', 'mo', 'go'];
        if(($i = array_search(strtolower($unit), $map)) === false) {
            return false;
        }
        return 1 << ($i * 10);
    }

    public function indexAction(Request $request)
    {
        $stopwatch = new Stopwatch("controller");
        $ret = [
            'databoxes' => []
        ];
        $matches = [];
        if(preg_match("/^(\\d+)\\s*(ko|mo|go)?$/i", $request->get('blocksize', '1'), $matches) !== 1) {
            throw new Exception("bad 'blocksize' parameter");
        }
        $matches[] = 'o';   // if no unit, force
        $blocksize = (int)($matches[1]) * $this->unitToMultiplier($matches[2]);

        $sql = "SELECT COALESCE(r.`coll_id`, '?') AS `coll_id`,
                    COALESCE(c.`asciiname`, CONCAT('_',r.`coll_id`), '?') AS `asciiname`, s.`name`,
                    SUM(1) AS n, SUM(s.`size`) AS `size`, SUM(CEIL(s.`size` / " . $blocksize . ") * " . $blocksize . ") AS `disksize`
                    FROM `subdef` AS s LEFT JOIN `record` AS r ON r.`record_id`=s.`record_id` 
                    LEFT JOIN `coll` AS c ON r.`coll_id`=c.`coll_id`
                GROUP BY r.`coll_id`, s.`name`;";

        foreach($this->app->getDataboxes() as $databox) {
            $collections = [];
            $subdefs = [];
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if(!array_key_exists($row['coll_id'], $collections)) {
                    $collections[$row['coll_id']] = [
                        'coll_id' => $row['coll_id'],
                        'name' => $row['asciiname'],
                        'subdefs' => []
                    ];
                }
                $collections[$row['coll_id']]['subdefs'][$row['name']] = [
                    'count' => $row['n'],
                    'size' => $row['size'],
                    'disksize' => $row['disksize']
                ];
                if(!array_key_exists($row['name'], $subdefs)) {
                    $subdefs[$row['name']] = [
                        'count' => 0,
                        'size' => 0,
                        'disksize' => 0
                    ];
                }
                $subdefs[$row['name']]['count'] += $row['n'];
                $subdefs[$row['name']]['size'] += $row['size'];
                $subdefs[$row['name']]['disksize'] += $row['disksize'];
            }
            $ret['databoxes'][$databox->get_sbas_id()] = [
                'sbas_id' => $databox->get_sbas_id(),
                'viewname' => $databox->get_viewname(),
                'collections' => $collections,
                'subdefs' => $subdefs
            ];
        }
        return Result::create($request, $ret)->createResponse([$stopwatch]);
    }

}
