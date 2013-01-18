<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;

class SearchEngineLoggerTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @covers Alchemy\Phrasea\SearchEngine\SearchEngineLogger::log
     * @todo   Implement testLog().
     */
    public function testLog()
    {
        $databox = self::$DI['collection']->get_databox();
        $coll_ids = array(self::$DI['collection']->get_coll_id());
        $answers = 42;
        $query = \random::generatePassword();

        $object = new SearchEngineLogger(self::$DI['app']);
        $object->log($databox, $query, $answers, $coll_ids);

        $conn = $databox->get_connection();

        $sql = 'SELECT date, search, results, coll_id
                FROM log_search
                ORDER BY id DESC
                LIMIT 1';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->assertEquals($query, $row['search']);
        $this->assertEquals($answers, $row['results']);
        $this->assertEquals(self::$DI['collection']->get_coll_id(), $row['coll_id']);
    }
}
