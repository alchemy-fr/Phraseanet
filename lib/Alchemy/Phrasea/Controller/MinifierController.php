<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MinifierController
{
    /** @var string */
    private $cachePath;
    /** @var bool */
    private $debug;

    /**
     * @param string $cachePath
     * @param bool   $debug
     */
    public function __construct($cachePath, $debug = false)
    {
        $this->cachePath = $cachePath;
        $this->debug = (bool) $debug;
    }

    public function minifyAction(Request $request)
    {
        $serveOptions = [
            'bubbleCssImports' => false,
            'debug' => false,
            'maxAge' => 1800,
            'quiet' => true,
            'minApp' => [
                'groupsOnly' => false,
            ],
        ];
        $sourceOptions = [];

        if ($this->debug) {
            // disallow minification instead of using debug mode, which may cause js errors
            $sourceOptions['noMinPattern'] = '#\.(?:js|css)$#i';
            $serveOptions['maxAge'] = 0;
        }

        if (null !== $request->query->get('g')) {
            $serveOptions['minApp']['groups'] = require __DIR__ . '/../../../conf.d/minifyGroupsConfig.php';
        }

        if (null === $request->query->get('f') && null === $request->query->get('g')) {
            throw new HttpException(400, 'Please provide an argument');
        }

        $env = new \Minify_Env([
            'server' => array_replace($request->server->all(), ['DOCUMENT_ROOT' => realpath(__DIR__ . '/../../../../www/')]),
            'get' => $request->query->all(),
            'post' => [],
            'cookie' => [],
        ]);
        $cache = new \Minify_Cache_File($this->cachePath ?: sys_get_temp_dir(), true);
        $controller = new \Minify_Controller_MinApp($env, new \Minify_Source_Factory($env, $sourceOptions, $cache));

        $ret = (new \Minify($cache))->serve($controller, $serveOptions);

        if (!$ret['success']) {
            throw new HttpException(500, 'Unable to generate data');
        }

        $response = new Response($ret['content'], $ret['statusCode']);
        $response->setMaxAge($serveOptions['maxAge']);

        foreach ($ret['headers'] as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
