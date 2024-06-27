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

    private function unitToMultiplier(string $unit)
    {
        static $map = [''=>1, 'o'=>1, 'octet'=>1, 'octets'=>1, 'ko'=>1<<10, 'mo'=>1<<20, 'go'=>1<<30];
        try {
            return $map[strtolower($unit)];
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * monitor infos for app
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $stopwatch = new Stopwatch("controller");
        $matches = [];
        if(preg_match("/^(\\d+)\\s*([a-z]*)$/i", $request->get('blocksize', '1'), $matches) !== 1) {
            throw new Exception("bad 'blocksize' parameter");
        }
        $matches[] = '';   // if no unit, force
        if(($mutiplier = $this->unitToMultiplier($matches[2])) === false) {
            throw new Exception("bad 'blocksize' unit");
        }
        $blocksize = (int)($matches[1]) * $mutiplier;

        if( ($divider = $this->unitToMultiplier($unit = $request->get('unit', '')) ) === false) {
            throw new Exception("bad 'unit' parameter");
        }
        $sqlDivider = $divider === 1 ? '' : (' / ' . $divider);

        $ret = [
            'unit' => $divider === 1 ? $unit : ucfirst($unit), // octet => octet ; mo => Mo
            'databoxes' => []
        ];

        $sqlByColl = "SELECT COALESCE(r.`coll_id`, '?') AS `coll_id`,
                    COALESCE(c.`asciiname`, CONCAT('_',r.`coll_id`), '?') AS `asciiname`, s.`name`,
                    SUM(1) AS n, SUM(s.`size`) " . $sqlDivider . " AS `size`,
                    SUM(CEIL(s.`size` / " . $blocksize . ") * " . $blocksize . ") " . $sqlDivider . " AS `disksize`
                    FROM `subdef` AS s LEFT JOIN `record` AS r ON r.`record_id`=s.`record_id` 
                    LEFT JOIN `coll` AS c ON r.`coll_id`=c.`coll_id`
                GROUP BY r.`coll_id`, s.`name`;";

        $sqlByName = "SELECT s.`name`,
                    SUM(1) AS n, SUM(s.`size`) " . $sqlDivider . " AS `size`,
                    SUM(CEIL(s.`size` / " . $blocksize . ") * " . $blocksize . ") " . $sqlDivider . " AS `disksize`
                    FROM `subdef` AS s
                GROUP BY s.`name`;";

        foreach($this->app->getDataboxes() as $databox) {
            $collections = [];
            $stmt = $databox->get_connection()->prepare($sqlByColl);
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
                    'count' => (int)$row['n'],
                    'size' => floatval($row['size']),
                    'disksize' => floatval($row['disksize'])
                ];
            }
            $stmt->closeCursor();

            $subdefs = [];
            $stmt = $databox->get_connection()->prepare($sqlByName);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $subdefs[$row['name']]['count'] = (int)$row['n'];
                $subdefs[$row['name']]['size'] = floatval($row['size']);
                $subdefs[$row['name']]['disksize'] = floatval($row['disksize']);
            }
            $stmt->closeCursor();

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
