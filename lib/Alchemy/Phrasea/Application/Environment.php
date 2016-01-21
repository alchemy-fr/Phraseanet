<?php

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application;

class Environment
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $debug = false;

    public function __construct($name, $debug)
    {
        $this->name = (string) $name;
        $this->debug = ((bool) $debug) || ! in_array($name, [
            Application::ENV_PROD, Application::ENV_TEST
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }
}
