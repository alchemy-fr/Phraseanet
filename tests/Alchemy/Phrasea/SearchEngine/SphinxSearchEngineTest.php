<?php

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

require_once __DIR__ . '/SearchEngineAbstractTest.php';

class SphinxSearchEngineTest extends SearchEngineAbstractTest
{
    protected static $config;
    protected static $searchd;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $app = new Application('test');
        $appbox = $app['phraseanet.appbox'];
        self::$searchEngine = new SphinxSearchEngine($app, '127.0.0.1', 19306, '127.0.0.1', 19308);

        self::$config = tempnam(sys_get_temp_dir(), 'tmp_sphinx.conf');
        $configuration = self::$searchEngine->configurationPanel()->getConfiguration();
        $configuration['date_fields'] = array();
        
        foreach($appbox->get_databoxes() as $databox) {
            foreach ($databox->get_meta_structure() as $databox_field) {
                if ($databox_field->get_type() != \databox_field::TYPE_DATE) {
                    continue;
                }
                $configuration['date_fields'][] = $databox_field->get_name();
            }
        }
        
        $configuration['date_fields'] = array_unique($configuration['date_fields']);
        
        self::$searchEngine->configurationPanel()->saveConfiguration($configuration);
        
        $configFile = self::$searchEngine->configurationPanel()->generateSphinxConf($appbox->get_databoxes(), $configuration);

        file_put_contents(self::$config, $configFile);

        $binaryFinder = new ExecutableFinder();
        $indexer = $binaryFinder->find('indexer');

        $searchd = $binaryFinder->find('searchd');

        $process = new Process($indexer . ' --all -c ' . self::$config);
        $process->run();

        self::$searchd = new Process($searchd . ' -c ' . self::$config);
        self::$searchd->run();
        
        self::$searchEngine = new SphinxSearchEngine($app, '127.0.0.1', 19306, '127.0.0.1', 19308);
    }
    
    public function tearDown()
    {
        self::$searchEngine->removeRecord(self::$DI['record_24']);
        
        parent::tearDown();
    }

    public function initialize()
    {
    }

    public static function tearDownAfterClass()
    {
        $binaryFinder = new ExecutableFinder();
        $searchd = $binaryFinder->find('searchd');

        self::$searchd = new Process($searchd . ' --stop -c ' . self::$config);
        self::$searchd->run();

        unlink(self::$config);

        parent::tearDownAfterClass();
    }

    public function testAutocomplete()
    {
        $record = self::$DI['record_24'];

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => 'jeanne, jeannine, jeannette, jean-pierre et jean claude'
            );
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

        $suggestions = self::$searchEngine->autoComplete('jean');
        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $suggestions);

        $this->assertGreaterThan(2, count($suggestions));

        foreach ($suggestions as $suggestion) {
            $this->assertInstanceof('\\Alchemy\\Phrasea\\SearchEngine\\SearchEngineSuggestion', $suggestion);
        }
    }

    public function testAutocomplete()
    {
        $record = self::$DI['record_24'];

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => 'jeanne, jeannine, jeannette, jean-pierre et jean claude'
            );
            break;
        }

        $record->set_metadatas($toupdate);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $binaryFinder = new ExecutableFinder();
        $indexer = $binaryFinder->find('indexer');

        $process = new Process($indexer . ' --all --rotate -c ' . self::$config);
        $process->run();

        $appbox = self::$DI['app']['phraseanet.appbox'];
        self::$searchEngine->buildSuggestions($appbox->get_databoxes(), self::$config, 0);

        $process = new Process($indexer . ' --all --rotate -c ' . self::$config);
        $process->run();
        usleep(500000);

        $suggestions = self::$searchEngine->autoComplete('jean');
        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $suggestions);

        $this->assertGreaterThan(2, count($suggestions));

        foreach ($suggestions as $suggestion) {
            $this->assertInstanceof('\\Alchemy\\Phrasea\\SearchEngine\\SearchEngineSuggestion', $suggestion);
        }
    }
}

