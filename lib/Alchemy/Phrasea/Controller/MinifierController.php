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
        /**
         * Cache file locking. Set to false if filesystem is NFS. On at least one
         * NFS system flock-ing attempts stalled PHP for 30 seconds!
         */
        $min_cacheFileLocking = true;

        /**
         * Combining multiple CSS files can place @import declarations after rules, which
         * is invalid. Minify will attempt to detect when this happens and place a
         * warning comment at the top of the CSS output. To resolve this you can either
         * move the @imports within your CSS files, or enable this option, which will
         * move all @imports to the top of the output. Note that moving @imports could
         * affect CSS values (which is why this option is disabled by default).
         */
        $min_serveOptions['bubbleCssImports'] = false;

        $min_serveOptions['debug'] = false;
        $min_serveOptions['maxAge'] = 1800;
        if ($this->debug) {
            // may cause js errors
            $min_serveOptions['debug'] = false;
            // disallow minification instead
            $min_serveOptions['minApp']['noMinPattern'] = '#\.(?:js|css)$#i';
            $min_serveOptions['maxAge'] = 0;
        }

        /**
         * Set to true to disable the "f" GET parameter for specifying files.
         * Only the "g" parameter will be considered.
         */
        $min_serveOptions['minApp']['groupsOnly'] = false;

        /**
         * Maximum # of files that can be specified in the "f" GET parameter
         */
        $min_serveOptions['minApp']['maxFiles'] = 10;

        /**
         * If you minify CSS files stored in symlink-ed directories, the URI rewriting
         * algorithm can fail. To prevent this, provide an array of link paths to
         * target paths, where the link paths are within the document root.
         *
         * Because paths need to be normalized for this to work, use "//" to substitute
         * the doc root in the link paths (the array keys). E.g.:
         * <code>
         * array('//symlink' => '/real/target/path') // unix
         * array('//static' => 'D:\\staticStorage')  // Windows
         * </code>
         */
        $min_symlinks = [];

        /**
         * If you upload files from Windows to a non-Windows server, Windows may report
         * incorrect mtimes for the files. This may cause Minify to keep serving stale
         * cache files when source file changes are made too frequently (e.g. more than
         * once an hour).
         *
         * Immediately after modifying and uploading a file, use the touch command to
         * update the mtime on the server. If the mtime jumps ahead by a number of hours,
         * set this variable to that number. If the mtime moves back, this should not be
         * needed.
         *
         * In the Windows SFTP client WinSCP, there's an option that may fix this
         * issue without changing the variable below. Under login > environment,
         * select the option "Adjust remote timestamp with DST".
         * @link http://winscp.net/eng/docs/ui_login_environment#daylight_saving_time
         */
        $min_uploaderHoursBehind = 0;

        // return an array instead of echoing output
        $min_serveOptions['quiet'] = true;

        \Minify::$uploaderHoursBehind = $min_uploaderHoursBehind;
        \Minify::setCache(isset($min_cachePath) ? $min_cachePath : '', $min_cacheFileLocking);

        // required to work well :(
        $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../../../../www/';
        \Minify::$isDocRootSet = true;

        $min_serveOptions['minifierOptions']['text/css']['symlinks'] = $min_symlinks;
        // auto-add targets to allowDirs
        foreach ($min_symlinks as $uri => $target) {
            $min_serveOptions['minApp']['allowDirs'][] = $target;
        }

        if (null !== $request->query->get('g')) {
            // well need groups config
            $min_serveOptions['minApp']['groups'] = require __DIR__ . '/../../../conf.d/minifyGroupsConfig.php';
        }

        if (null === $request->query->get('f') && null === $request->query->get('g')) {
            throw new HttpException(400, 'Please provide an argument');
        }

        $ret = \Minify::serve(new \Minify_Controller_MinApp(), $min_serveOptions);

        if (!$ret['success']) {
            throw new HttpException(500, 'Unable to generate data');
        }

        $response = new Response($ret['content'], $ret['statusCode']);
        $response->setMaxAge($min_serveOptions['maxAge']);

        foreach ($ret['headers'] as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
