<?php

namespace Alchemy\Phrasea;

use Alchemy\Phrasea\PhraseanetServiceProvider;
use Silex\Application as SilexApplication;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Application extends SilexApplication
{

    public function __construct()
    {
        parent::__construct();

        $this->register(new PhraseanetServiceProvider());
        $this->register(new ValidatorServiceProvider());

        $this['debug'] = $this['phraseanet.core']->getEnv() !== 'prod';

        $this->before(function(Request $request) {
            $request->setRequestFormat(
                $request->getFormat(
                    array_shift(
                        $request->getAcceptableContentTypes()
                    )
                )
            );
        });

//        $this->register(new \Silex\Provider\HttpCacheServiceProvider());
//        $this->register(new \Silex\Provider\MonologServiceProvider());
//        $this->register(new \Silex\Provider\SecurityServiceProvider());
//        $this->register(new \Silex\Provider\SessionServiceProvider());
//        $this->register(new \Silex\Provider\SwiftmailerServiceProvider());
//        $this->register(new \Silex\Provider\TwigServiceProvider());
//        $this->register(new \Silex\Provider\UrlGeneratorServiceProvider());


    }

    public function run(Request $request = null)
    {
        $app = $this;

        $this->error(function($e) use ($app) {
            if ($app['debug']) {
                return new Response($e->getMessage(), 500);
            } else {
                return new Response(_('An error occured'), 500);
            }
        });
        parent::run($request);
    }
}

