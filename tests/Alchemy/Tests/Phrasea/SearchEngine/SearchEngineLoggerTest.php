<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SearchEngineLoggerTest extends \PhraseanetTestCase
{
    /**
     * @todo   Implement testLog().
     */
    public function testLog()
    {
        $databox = self::$DI['collection']->get_databox();
        $coll_ids = [self::$DI['collection']->get_coll_id()];
        $answers = 42;
        $query = self::$DI['app']['random.low']->generateString(8);

        $object = new SearchEngineLogger(self::$DI['app']);
        $object->log($databox, $query, $answers, $coll_ids);

        $conn = $databox->get_connection();

        $sql = 'SELECT date, search, results, coll_id
                FROM log_search
                ORDER BY id DESC
                LIMIT 1';
        $stmt = $conn->executeQuery($sql);
        $row = $stmt->fetchAssociative();

        $this->assertEquals($query, $row['search']);
        $this->assertEquals($answers, $row['results']);
        $this->assertEquals(self::$DI['collection']->get_coll_id(), $row['coll_id']);
    }
}
