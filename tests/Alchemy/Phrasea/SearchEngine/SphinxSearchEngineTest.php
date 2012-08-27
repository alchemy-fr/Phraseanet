<?php

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine;

require_once __DIR__ . '/SearchEngineAbstractTest.php';

class SphinxSearchEngineTest extends SearchEngineAbstractTest
{
    protected static $searchd;
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $appbox = \appbox::get_instance(self::$core);
        self::$searchEngine = new SphinxSearchEngine('127.0.0.1', 9306, '127.0.0.1', 9308);

        $config = tempnam(sys_get_temp_dir(), 'tmp_sphinx.conf');
        $configFile = self::$searchEngine->configurationPanel()->generateSphinxConf($appbox->get_databoxes(), self::$searchEngine->configurationPanel()->getConfiguration());
        $configFile = str_replace('sql_pass              =', 'sql_pass              = toor', $configFile);

        file_put_contents($config, $configFile);

        $binaryFinder = new \Symfony\Component\Process\ExecutableFinder();
        $indexer = $binaryFinder->find('indexer');

        $searchd = $binaryFinder->find('searchd');

        $process = new \Symfony\Component\Process\Process($indexer.' --all -c '.$config);
        $process->run();
        self::$searchd = new \Symfony\Component\Process\Process($searchd.' --nodetach -c '.$config);
        self::$searchd->start();

        sleep(2);
        self::$searchEngine = new SphinxSearchEngine('127.0.0.1', 9306, '127.0.0.1', 9308);
    }

    public static function tearDownAfterClass()
    {
        self::$searchd->stop();
        parent::tearDownAfterClass();
    }


    public function setUp()
    {
        parent::setUp();
    }
}

