<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Log;

use Alchemy\Phrasea\TaskManager\Log\ManagerLogFile;

class ManagerLogFileTest extends LogFileTestCase
{
    protected function getLogFile($root)
    {
        return new ManagerLogFile($root);
    }
}
