<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class SphinxSearchEngineTest extends SearchEngineAbstractTest
{
    private static $skipped = false;
    private static $config;
    private static $searchd;

    /**
     * @covers Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine
     */
    public function bootTestCase()
    {
        $binaryFinder = new ExecutableFinder();

        if (null !== self::$indexerBinary = $binaryFinder->find('indexer') || null !== self::$searchdBinary = $binaryFinder->find('searchd')) {
            self::$skipped = true;
        }

        $app = self::$DI['app'];
        $appbox = $app['phraseanet.appbox'];

        $app['conf']->set(['main', 'search-engine', 'options'], [
            'host'    => '127.0.0.1',
            'port'    => 9312,
            'rt_host' => '127.0.0.1',
            'rt_port' => 9306,
        ]);

        self::$searchEngine = SphinxSearchEngine::create($app, $app['conf']->get(['main', 'search-engine', 'options']));

        self::$config = tempnam(sys_get_temp_dir(), 'tmp_sphinx.conf');
        $configuration = self::$searchEngine->getConfigurationPanel()->getConfiguration();
        $configuration['date_fields'] = [];

        foreach ($appbox->get_databoxes() as $databox) {
            foreach ($databox->get_meta_structure() as $databox_field) {
                if ($databox_field->get_type() != \databox_field::TYPE_DATE) {
                    continue;
                }
                $configuration['date_fields'][] = $databox_field->get_name();
            }
        }

        $configuration['date_fields'] = array_unique($configuration['date_fields']);

        self::$searchEngine->getConfigurationPanel()->saveConfiguration($configuration);

        $configFile = self::$searchEngine->getConfigurationPanel()->generateSphinxConf($appbox->get_databoxes(), $configuration);

        file_put_contents(self::$config, $configFile);

        $process = new Process($indexer . ' --all -c ' . self::$config);
        $process->run();

        self::$searchd = new Process($searchd . ' -c ' . self::$config);
        self::$searchd->run();

        self::$searchEngine = SphinxSearchEngine::create($app, $app['conf']->get(['main', 'search-engine', 'options']));
        self::$searchEngineClass = 'Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine';
    }

    public function setUp()
    {
        parent::setUp();

        if (self::$skipped) {
            $this->markTestSkipped('SphinxSearch is not present on system');
        }
    }

    public function tearDown()
    {
        if (!self::$skipped) {
            self::$searchEngine->removeRecord(self::$DI['record_2']);
        }

        parent::tearDown();
    }

    public function initialize()
    {
        if (!self::$searchEngine) {
            self::$DI['app']['conf']->set(['main', 'search-engine', 'options'], [
                'host'    => '127.0.0.1',
                'port'    => 9312,
                'rt_host' => '127.0.0.1',
                'rt_port' => 9306,
            ]);

            self::$searchEngine = SphinxSearchEngine::create(self::$DI['app'], self::$DI['app']['conf']->get(['main', 'search-engine', 'options']));

            self::$config = tempnam(sys_get_temp_dir(), 'tmp_sphinx.conf');

            $configuration = self::$searchEngine->getConfigurationPanel()->getConfiguration();
            $configuration['date_fields'] = [];

            foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
                foreach ($databox->get_meta_structure() as $databox_field) {
                    if ($databox_field->get_type() != \databox_field::TYPE_DATE) {
                        continue;
                    }
                    $configuration['date_fields'][] = $databox_field->get_name();
                }
            }

            $configuration['date_fields'] = array_unique($configuration['date_fields']);

            self::$searchEngine->getConfigurationPanel()->saveConfiguration($configuration);

            $configFile = self::$searchEngine->getConfigurationPanel()->generateSphinxConf(self::$DI['app']['phraseanet.appbox']->get_databoxes(), $configuration);

            file_put_contents(self::$config, $configFile);

            $binaryFinder = new ExecutableFinder();

            $process = new Process(self::$indexerBinary . ' --all -c ' . self::$config);
            $process->run();

            self::$searchdProcess = new Process(self::$searchdBinary . ' -c ' . self::$config);
            self::$searchd->run();

            self::$searchEngine = SphinxSearchEngine::create(self::$DI['app'], self::$DI['app']['configuration']['main']['search-engine']['options']);
        }
    }

    public static function tearDownAfterClass()
    {
        if (!self::$skipped) {
            self::$searchdProcess = new Process(self::$searchdBinary . ' --stop -c ' . self::$config);
            self::$searchdProcess->run();

            unlink(self::$config);
        }

        self::$skipped = self::$config = self::$searchd = null;
        parent::tearDownAfterClass();
    }

    /**
     * @covers Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine::autocomplete
     */
    public function testAutocomplete()
    {
        $record = self::$DI['record_2'];

        $toupdate = [];

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = [
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => 'jeanne, jeannine, jeannette, jean-pierre et jean claude'
            ];
            break;
        }

        $record->set_metadatas($toupdate);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $binaryFinder = new ExecutableFinder();
        $indexer = $binaryFinder->find('indexer');

        $appbox = self::$DI['app']['phraseanet.appbox'];
        self::$searchEngine->buildSuggestions($appbox->get_databoxes(), self::$config, 0);

        $process = new Process($indexer . ' --all --rotate -c ' . self::$config);
        $process->run();
        usleep(500000);

        $suggestions = self::$searchEngine->autocomplete('jean', $this->options);
        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $suggestions);

        $this->assertGreaterThan(2, count($suggestions));

        foreach ($suggestions as $suggestion) {
            $this->assertInstanceof('\\Alchemy\\Phrasea\\SearchEngine\\SearchEngineSuggestion', $suggestion);
        }
    }

    protected function updateIndex(array $stemms = [])
    {
        return $this;
    }
}
