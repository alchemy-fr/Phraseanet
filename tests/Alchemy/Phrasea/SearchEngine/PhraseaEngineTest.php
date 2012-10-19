<?php

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine;
use Symfony\Component\Process\Process;

require_once __DIR__ . '/SearchEngineAbstractTest.php';

class PhraseaEngineTest extends SearchEngineAbstractTest
{
    public function initialize()
    {
        self::$searchEngine = PhraseaEngine::create(self::$DI['app']);
    }

    protected function updateIndex()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $cmd = '/usr/local/bin/phraseanet_indexer '
            . ' -h=' . $appbox->get_host() . ' -P=' . $appbox->get_port()
            . ' -b=' . $appbox->get_dbname() . ' -u=' . $appbox->get_user()
            . ' -p=' . $appbox->get_passwd()
            . ' --default-character-set=utf8 -n -o -d=64 --quit';
        $process = new Process($cmd);
        $process->run();
    }

    public function testAutocomplete()
    {
        return;
    }

    public function testQueryStoryId()
    {
        $this->markTestSkipped('Phrasea does not support `storyid=` request');
    }
}

