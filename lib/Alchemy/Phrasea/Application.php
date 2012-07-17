<?php

namespace Alchemy\Phrasea;

use Silex\Application as SilexApplication;

class Application extends SilexApplication
{
    public function __construct()
    {
        parent::__construct();

        $this->register(new \Alchemy\Phrasea\PhraseanetServiceProvider());

        $this['debug'] = $this['phraseanet.core']->getEnv() !== 'prod';
//        $this->register(new \Silex\Provider\HttpCacheServiceProvider());
//        $this->register(new \Silex\Provider\MonologServiceProvider());
//        $this->register(new \Silex\Provider\SecurityServiceProvider());
//        $this->register(new \Silex\Provider\SessionServiceProvider());
//        $this->register(new \Silex\Provider\SwiftmailerServiceProvider());
//        $this->register(new \Silex\Provider\TwigServiceProvider());
//        $this->register(new \Silex\Provider\UrlGeneratorServiceProvider());
    }
}

