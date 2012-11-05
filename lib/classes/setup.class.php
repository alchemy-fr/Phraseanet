<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * This file MUST NOT contains any default PHP function as
 * mb_*, curl_*, bind_text_domain, _
 *
 * This file is intended to be loaded on setup test
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class setup
{
    protected static $PHP_EXT = array(
        "dom"
        , "exif"
        , "fileinfo"
        , "ftp"
        , "curl"
        , "gd"
        , "gettext"
        , "hash"
        , "json"
        , "iconv"
        , "libxml"
        , "mbstring"
        , "mysql"
        , 'pcntl'
        , "PDO"
        , "phrasea2"
        , 'posix'
        , "SimpleXML"
        , "sockets"
        , "xml"
        , "zip"
        , "zlib"
        , "intl"
        , "twig"
        , "gmagick"
        , "imagick"
    );
    protected static $PHP_CONF = array(
        'output_buffering'                => '4096'  //INI_ALL
        , 'memory_limit'                    => '2048M'  //INI_ALL
        , 'error_reporting'                 => '6143'  //INI_ALL
        , 'default_charset'                 => 'UTF-8'  //INI_ALL
        , 'session.use_cookies'             => 'on'   //INI_ALL
        , 'session.use_only_cookies'        => 'on'   //INI_ALL
        , 'session.auto_start'              => 'off'   //INI_ALL
        , 'session.hash_function'           => 'on'   //INI_ALL
        , 'session.hash_bits_per_character' => '6'  //INI_ALL
        , 'allow_url_fopen'                 => 'on'  //INI_ALL
        , 'display_errors'                  => 'off'  //INI_ALL
        , 'display_startup_errors'          => 'off'  //INI_ALL
        , 'log_errors'                      => 'off'  //INI_ALL
        , 'session.cache_limiter'           => ''  //INI_ALL
    );
    protected static $PHP_REQ = array(
        'safe_mode'            => 'off'
        , 'file_uploads'         => 'on'
        , 'magic_quotes_runtime' => 'off'  //INI_ALL
        , 'magic_quotes_gpc'     => 'off'  //INI_PER_DIR -- just for check
    );

    public static function create_global_values(Application $app, $datas = array())
    {
        require(__DIR__ . "/../../lib/conf.d/_GV_template.inc");

        $debug = $log_errors = false;
        $vars = array();

        $error = false;
        $extra_conf = '';

        foreach ($GV as $section) {
            foreach ($section['vars'] as $variable) {
                if (isset($datas[$variable['name']]) === false) {
                    if (isset($variable['default'])) {
                        if ($variable['type'] === 'boolean') {
                            if ($variable['default'] === true)
                                $datas[$variable['name']] = '1';
                            else
                                $datas[$variable['name']] = '0';
                        } else {
                            $datas[$variable['name']] = $variable['default'];
                        }
                    }
                }

                $type = $variable['type'];
                switch ($variable['type']) {
                    case \registry::TYPE_STRING:
                    case \registry::TYPE_BINARY:
                    case \registry::TYPE_TEXT:
                    case \registry::TYPE_TIMEZONE:
                        $datas[$variable['name']] = (string) trim($datas[$variable['name']]);
                        break;
                    case \registry::TYPE_ENUM:
                        if (!isset($variable['available'])) {
                            $variable['error'] = 'avalaibility';
                        } elseif (!is_array($variable['available'])) {
                            $variable['error'] = 'avalaibility';
                        } elseif (!in_array($datas[$variable['name']], $variable['available'])) {
                            $variable['error'] = 'avalaibility';
                        }
                        break;
                    case \registry::TYPE_ENUM_MULTI:
                        if (!isset($datas[$variable['name']]))
                            $datas[$variable['name']] = null;
                        $datas[$variable['name']] = ($datas[$variable['name']]);
                        break;
                    case \registry::TYPE_BOOLEAN:
                        $datas[$variable['name']] = strtolower($datas[$variable['name']]) === 'true' ? '1' : '0';
                        break;
                    case \registry::TYPE_INTEGER:
                        $datas[$variable['name']] = (int) trim($datas[$variable['name']]);
                        break;
                    default:
                        $error = true;
                        break;
                }

                if (isset($variable['required']) && $variable['required'] === true && trim($datas[$variable['name']]) === '')
                    $variable['error'] = 'required';

                if (isset($variable['end_slash'])) {
                    if ($variable['end_slash'] === true) {
                        $datas[$variable['name']] = trim($datas[$variable['name']]) !== '' ? p4string::addEndSlash($datas[$variable['name']]) : '';
                    }
                    if ($variable['end_slash'] === false) {
                        $datas[$variable['name']] = trim($datas[$variable['name']]) !== '' ? p4string::delEndSlash($datas[$variable['name']]) : '';
                    }
                }

                if ($variable['name'] === 'GV_debug' && $datas[$variable['name']] === '1')
                    $debug = true;
                if ($variable['name'] === 'GV_log_errors' && $datas[$variable['name']] === '1')
                    $log_errors = true;

                if ($variable['type'] !== 'integer' && $variable['type'] !== 'boolean')
                    $datas[$variable['name']] = $datas[$variable['name']];

                $vars[$variable['name']] = array('value' => $datas[$variable['name']], 'type'  => $type);
            }
        }

        if ($error === false) {
            foreach ($vars as $key => $values) {
                if ($key == 'GV_sit' && null !== $app['phraseanet.registry']->get('GV_sit')) {
                    continue;
                }
                $app['phraseanet.registry']->set($key, $values['value'], $values['type']);
            }

            return true;
        }

        return false;
    }

    public static function check_binaries(registryInterface $registry)
    {
        $finder = new \Symfony\Component\Process\ExecutableFinder();

        $binaries = array(
            'PHP CLI'                 => $registry->get('php_binary', $finder->find('php')),
            'ImageMagick (convert)'   => $registry->get('convert_binary', $finder->find('convert')),
            'PDF 2 SWF'               => $registry->get('pdf2swf_binary', $finder->find('pdf2swf')),
            'Unoconv'                 => $registry->get('unoconv_binary', $finder->find('unoconv')),
            'SWFextract'              => $registry->get('swf_extract_binary', $finder->find('swfextract')),
            'SWFrender'               => $registry->get('swf_render_binary', $finder->find('swfrender')),
            'MP4Box'                  => $registry->get('mp4box_binary', $finder->find('MP4Box')),
            'xpdf (pdf2text)'         => $registry->get('pdftotext_binary', $finder->find('pdftotext')),
            'ImageMagick (composite)' => $registry->get('composite_binary', $finder->find('composite')),
            'FFmpeg'                  => $registry->get('ffmpeg_binary', $finder->find('ffmpeg')),
            'FFprobe'                 => $registry->get('ffprobe_binary', $finder->find('ffprobe')),
            'phraseanet_indexer'      => $finder->find('phraseanet_indexer'),
        );

        $constraints = array();

        foreach ($binaries as $name => $binary) {
            if (trim($binary) == '' || (!is_file($binary))) {
                $constraints[] = new Setup_Constraint(
                        $name
                        , false
                        , sprintf('%s missing', $name)
                        , false
                );
            } else {
                if (!is_executable($binary)) {
                    $constraints[] = new Setup_Constraint(
                            $name
                            , false
                            , sprintf('%s not executeable', $name)
                            , true
                    );
                } else {
                    $constraints[] = new Setup_Constraint(
                            $name
                            , true
                            , sprintf('%s OK', $name)
                            , true
                    );
                }
            }
        }

        return new Setup_ConstraintsIterator($constraints);
    }

    public static function discover_binaries()
    {
        $finder = new \Symfony\Component\Process\ExecutableFinder();

        return array(
            'php' => array(
                'name'               => 'PHP CLI',
                'binary'             => $finder->find('php')
            ),
            'phraseanet_indexer' => array(
                'name'    => 'Indexeur Phrasea',
                'binary'  => $finder->find('phraseanet_indexer')
            ),
            'convert' => array(
                'name'      => 'ImageMagick (convert)',
                'binary'    => $finder->find('convert')
            ),
            'composite' => array(
                'name'    => 'ImageMagick (composite)',
                'binary'  => $finder->find('composite')
            ),
            'pdf2swf' => array(
                'name'    => 'PDF 2 SWF',
                'binary'  => $finder->find('pdf2swf')
            ),
            'unoconv' => array(
                'name'       => 'Unoconv',
                'binary'     => $finder->find('unoconv')
            ),
            'swfextract' => array(
                'name'      => 'SWFextract',
                'binary'    => $finder->find('swfextract')
            ),
            'swfrender' => array(
                'name'   => 'SWFrender',
                'binary' => $finder->find('swfrender')
            ),
            'MP4Box' => array(
                'name'   => 'MP4Box',
                'binary' => $finder->find('MP4Box')
            ),
            'xpdf'   => array(
                'name'   => 'XPDF',
                'binary' => $finder->find('xpdf')
            ),
            'ffmpeg' => array(
                'name'   => 'FFmpeg',
                'binary' => $finder->find('ffmpeg')
            ),
        );
    }

    public static function check_phrasea()
    {
        $constraints = array();
        if (function_exists('phrasea_info')) {
            foreach (phrasea_info() as $name => $value) {
                switch ($name) {
                    default:
                        $result = true;
                        $message = $name . ' = ' . $value;
                        break;
                    case 'temp_writable':
                        $result = $value == '1';
                        if ($result)
                            $message = 'Directory is writeable';
                        else
                            $message = 'Directory MUST be writable';
                        break;
                    case 'version':
                        $result = version_compare($value, '1.18.0.3', '>=');
                        if ($result)
                            $message = sprintf('Phrasea version %s is ok', $value);
                        else
                            $message = sprintf('Phrasea version %s is NOT ok', $value);
                        break;
                }
                $blocker = $name == 'temp_writable' ? ($value ? '' : 'blocker') : '';
                $constraints[] = new Setup_Constraint($name, $result, $message, true);
            }
        }

        return new Setup_ConstraintsIterator($constraints);
    }

    public static function check_writability(registryInterface $registry)
    {
        $root = p4string::addEndSlash(realpath(__DIR__ . '/../../'));

        $pathes = array(
            $root . 'config',
            $root . 'config/stamp',
            $root . 'config/status',
            $root . 'config/minilogos',
            $root . 'config/templates',
            $root . 'config/topics',
            $root . 'config/wm',
            $root . 'logs',
            $root . 'tmp',
            $root . 'www/custom',
            $root . 'tmp/locks',
            $root . 'tmp/cache_twig',
            $root . 'tmp/cache_minify',
            $root . 'tmp/lazaret',
            $root . 'tmp/desc_tmp',
            $root . 'tmp/download',
            $root . 'tmp/batches');

        if ($registry->is_set('GV_base_datapath_noweb')) {
            $pathes[] = $registry->get('GV_base_datapath_noweb');
        }

        $constraints = array();

        foreach ($pathes as $p) {
            if (!is_writable($p)) {
                $message = sprintf('%s not writeable', $p);
            } else {
                $message = sprintf('%s OK', $p);
            }

            $constraints[] = new Setup_Constraint(
                    'Writeability test', is_writable($p), $message, true
            );
        }
        $php_constraints = new Setup_ConstraintsIterator($constraints);

        return $php_constraints;
    }

    /**
     *
     */
    public static function check_php_version()
    {
        $version_ok = version_compare(PHP_VERSION, '5.3.3', '>');
        if (!$version_ok) {
            $message = sprintf(
                'Wrong PHP version : % ; PHP >= 5.3.3 required'
                , PHP_VERSION
            );
        } else {
            $message = sprintf('PHP version OK : %s', PHP_VERSION);
        }
        $constraints = array(
            new Setup_Constraint('PHP Version', $version_ok, $message)
        );

        return new Setup_ConstraintsIterator($constraints);
    }

    public static function check_php_extension()
    {
        $constraints = array();
        foreach (self::$PHP_EXT as $ext) {

            if (in_array($ext, array('pcntl', 'posix')) && defined('PHP_WINDOWS_VERSION_BUILD')) {
                continue;
            }

            if (extension_loaded($ext) !== true) {
                $blocker = true;
                if (in_array($ext, array('ftp', 'twig', 'gmagick', 'imagick', 'pcntl'))) {
                    $blocker = false;
                }

                $constraints[] = new Setup_Constraint(sprintf('Extension %s', $ext), false, sprintf('%s missing', $ext), $blocker);
            } else {
                $constraints[] = new Setup_Constraint(sprintf('Extension %s', $ext), true, sprintf('%s loaded', $ext));
            }
        }

        return new Setup_ConstraintsIterator($constraints);
    }

    public static function check_cache_server()
    {
        $availables_caches = array('memcache', 'redis');

        $constraints = array();
        foreach ($availables_caches as $ext) {
            if (extension_loaded($ext) === true) {
                $constraints[] = new Setup_Constraint(sprintf('Extension %s', $ext), true, sprintf('%s loaded', $ext), false);
            } else
                $constraints[] = new Setup_Constraint(sprintf('Extension %s', $ext), false, sprintf('%s not loaded', $ext), false);
        }

        return new Setup_ConstraintsIterator($constraints);
    }

    public static function check_cache_opcode()
    {
        $availables = array('XCache', 'apc', 'eAccelerator', 'phpa', 'WinCache');
        $constraints = array();

        $found = 0;
        foreach ($availables as $ext) {
            if (extension_loaded($ext) === true) {
                $constraints[] = new Setup_Constraint($ext, true, sprintf('%s loaded', $ext), false);
                $found++;
            }
        }

        if ($found > 1)
            $constraints[] = new Setup_Constraint('Multiple opcode caches', false, _('Many opcode cache load is forbidden'), true);
        if ($found === 0)
            $constraints[] = new Setup_Constraint('No opcode cache', false, _('No opcode cache were detected. Phraseanet strongly recommends the use of XCache or APC.'), false);

        return new Setup_ConstraintsIterator($constraints);
    }

    public static function check_php_configuration()
    {
        $nonblockers = array('log_errors', 'display_startup_errors', 'display_errors', 'output_buffering', 'error_reporting');

        $constraints = array();
        foreach (self::$PHP_REQ as $conf => $value) {
            if (($tmp = self::test_php_conf($conf, $value)) !== $value) {
                $constraints[] = new Setup_Constraint($conf, false, sprintf(_('setup::Configuration mauvaise : pour la variable %1$s, configuration donnee : %2$s ; attendue : %3$s'), $conf, $tmp, $value), true);
            } else {
                $constraints[] = new Setup_Constraint($conf, true, sprintf('%s = `%s` => OK', $conf, $value), true);
            }
        }

        if (!ini_get('date.timezone')) {
            $constraints[] = new Setup_Constraint('date.timezone', false, _('You must configure date.timezone'), true);
        }

        foreach (self::$PHP_CONF as $conf => $value) {
            if ($conf == 'memory_limit') {
                $memoryFound = self::test_php_conf($conf, $value);
                switch (strtolower(substr($memoryFound, -1))) {
                    case 'g':
                        $memoryFound *= 1024;
                    case 'm':
                        $memoryFound *= 1024;
                    case 'k':
                        $memoryFound *= 1024;
                }

                $memoryRequired = $value;
                switch (strtolower(substr($memoryRequired, -1))) {
                    case 'g':
                        $memoryRequired *= 1024;
                    case 'm':
                        $memoryRequired *= 1024;
                    case 'k':
                        $memoryRequired *= 1024;
                }

                if ($memoryFound >= $memoryRequired) {
                    $constraints[] = new Setup_Constraint($conf, true, sprintf('%s = `%s` => OK', $conf, $value), !in_array($conf, $nonblockers));
                } else {
                    $constraints[] = new Setup_Constraint($conf, false, sprintf('Bad configuration for %1$s, found `%2$s`, required `%3$s`', $conf, $tmp, $value), !in_array($conf, $nonblockers));
                }
            } elseif (($tmp = self::test_php_conf($conf, $value)) !== $value) {
                $constraints[] = new Setup_Constraint($conf, false, sprintf('Bad configuration for %1$s, found `%2$s`, required `%3$s`', $conf, $tmp, $value), !in_array($conf, $nonblockers));
            } else {
                $constraints[] = new Setup_Constraint($conf, true, sprintf('%s = `%s` => OK', $conf, $value), !in_array($conf, $nonblockers));
            }
        }

        return new Setup_ConstraintsIterator($constraints);
    }

    /**
     *
     * @return Setup_ConstraintsIterator
     */
    public static function check_system_locales(Application $app)
    {
        $constraints = array();

        if (!extension_loaded('gettext')) {
            return new Setup_ConstraintsIterator($constraints);
        }

        foreach (User_Adapter::$locales as $code => $language_name) {
            phrasea::use_i18n($code, 'test');

            if (_('test::test') == 'test') {
                $constraints[] = new Setup_Constraint($language_name, true, sprintf('Locale %s (%s) ok', $language_name, $code), false);
            } else {
                $constraints[] = new Setup_Constraint($language_name, false, sprintf('Locale %s (%s) not installed', $language_name, $code), false);
            }
        }
        phrasea::use_i18n($app['locale']);

        return new Setup_ConstraintsIterator($constraints);
    }

    private static function test_php_conf($conf, $value)
    {
        $is_flag = false;
        $flags = array('on', 'off', '1', '0');
        if (in_array(strtolower($value), $flags))
            $is_flag = true;
        $current = ini_get($conf);
        if ($is_flag)
            $current = strtolower($current);

        if (($current === '' || $current === 'off' || $current === '0') && $is_flag) {
            if ($value === 'off' || $value === '0' || $value === '') {
                return 'off';
            }
        }
        if (($current === '1' || $current === 'on') && $is_flag) {
            if ($value === 'on' || $value === '1') {
                return 'on';
            }
        }

        return $current;
    }
}
