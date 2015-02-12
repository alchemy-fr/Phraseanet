<?php

class PhraseanetPHPUnitListener implements PHPUnit_Framework_TestListener
{
    private static $logEcho = true;
    private static $logSQL = false;
    private static $data = [];
    private static $conn;
    private static $booted = false;

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        static::$data[self::generateName($test)]['status'] = 'error';
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        static::$data[self::generateName($test)]['status'] = 'fail';
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        static::$data[self::generateName($test)]['status'] = 'incomplete';
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        static::$data[self::generateName($test)]['status'] = 'skipped';
    }

    public static function getCsv()
    {
        return static::$data;
    }

    public static function getDurationByTest()
    {
        return static::$durationByTest;
    }

    public function startTest(PHPUnit_Framework_Test $test)
    {
        static::$data[self::generateName($test)] = [
            'duration' => microtime(true),
            'test'     => get_class($test),
            'name'     => $test->getName(),
            'status'   => 'ok'
        ];
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        $name = self::generateName($test);
        static::$data[$name]['duration'] = microtime(true) - static::$data[$name]['duration'];

        if (self::$logSQL) {
            self::$conn->insert('tests', static::$data[self::generateName($test)]);
        }
        if (self::$logEcho) {
            echo "$name (".round(static::$data[$name]['duration'], 2)."s)\n";
        }
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if (!class_exists($suite->getName())) {
            return;
        }

        if (!self::$booted && self::$logSQL) {
            self::$booted = true;
            $app = new \Alchemy\Phrasea\Application(\Alchemy\Phrasea\Application::ENV_TEST);
            self::$conn = $app['dbal.provider']($app['db.info']($app['db.appbox.info']));
            unset($app);
            self::$conn->connect();
            $schema = self::$conn->getSchemaManager();

            $tableTest = new \Doctrine\DBAL\Schema\Table("tests");
            /* Add some columns to the table */
            $tableTest->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
            $tableTest->addColumn("test", "string", array("length" => 256));
            $tableTest->addColumn("name", "string", array("length" => 256));
            $tableTest->addColumn("status", "string", array("length" => 16));
            $tableTest->addColumn("duration", "float");
            /* Add a primary key */
            $tableTest->setPrimaryKey(array("id"));
            $schema->dropAndCreateTable($tableTest);
        }
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if (!class_exists($suite->getName())) {
            return;
        }
     }

    private static function generateName(PHPUnit_Framework_Test $test)
    {
        $reflect = new \ReflectionClass($test);

        return $reflect->getShortName() . '::' . $test->getName();
    }
}
