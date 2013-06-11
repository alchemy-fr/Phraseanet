<?php

use Alchemy\Phrasea\Application;

class patch_3807Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->revert();
    }

    public function tearDown()
    {
        $this->revert();
    }

    private function revert()
    {
        foreach (array('binaries.yml.bkp', 'config.yml.bkp', 'connexions.yml.bkp', 'services.yml.bkp') as $backupFile) {
            if (is_file(__DIR__ . '/fixtures-3807/config/' . $backupFile)) {
                rename(__DIR__ . '/fixtures-3807/config/' . $backupFile, __DIR__ . '/fixtures-3807/config/' . substr($backupFile, 0, -4));
            }
        }
    }

    public function testApply()
    {
        $app = new Application();
        $app['phraseanet.configuration'] = $this->getMock('Alchemy\Phrasea\Core\Configuration\ConfigurationInterface');
        $app['root.path'] = __DIR__ . '/fixtures-3807';

        $app['phraseanet.configuration']->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($this->getCurrent()));

        $app['phraseanet.configuration']->expects($this->once())
            ->method('setConfig')
            ->with($this->getModified());

        $appbox = $this->getMockBuilder('appbox')
            ->disableOriginalConstructor()
            ->getMock();

        $patch = new patch_3807();
        $patch->apply($appbox, $app);

    }

    private function getModified()
    {
        $modified = $this->getCurrent();

        $modified['main']['key'] = '1234567890';
        $modified['main']['servername'] = 'http://sub.domain.tld/';
        $modified['main']['maintenance'] = true;
        $modified['binaries']['test_binary'] = '/path/to/test/binary/file';
        $modified['main']['database'] = array_replace($modified['main']['database'], array(
            'host' => 'sql-host',
            'port' => '13306',
            'user' => 'username',
            'password' => 's3cr3t',
            'dbname' => 'phrasea_db',
        ));
        $modified['main']['cache'] = array(
            'type' => 'MemcacheCache',
            'options' => array(
                'host' => 'memcache-host',
                'port' => 21211,
            )
        );
        $modified['main']['opcodecache'] = array(
            'type' => 'ApcCache',
            'options' => array(),
        );
        $modified['border-manager']['enabled'] = false;

        return $modified;
    }

    private function getCurrent()
    {
        return array(
            'main' => array(
                'servername' => 'http://local.phrasea/',
                'maintenance' => false,
                'database' => array(
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'user' => 'root',
                    'password' => '',
                    'dbname' => 'ab_test',
                    'driver' => 'pdo_mysql',
                    'charset' => 'UTF8',
                ),
                'database-test' => array(
                    'driver' => 'pdo_sqlite',
                    'path' => '/tmp/db.sqlite',
                    'charset' => 'UTF8',
                ),
                'api-timers' => true,
                'cache' => array(
                    'type' => 'ArrayCache',
                    'options' => array(
                    ),
                ),
                'opcodecache' => array(
                    'type' => 'ArrayCache',
                    'options' => array(
                    ),
                ),
                'search-engine' => array(
                    'type' => 'Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine',
                    'options' => array(
                    ),
                ),
                'task-manager' => array(
                    'options' => '',
                ),
                'key' => null,
            ),
            'binaries' => array(
                'legacy_binay' => '/path/to/legacy/binary',
            ),
            'border-manager' => array(
                'enabled' => true,
                'checkers' => array(
                    array(
                        'type' => 'Checker\Sha256',
                        'enabled' => true,
                    ),
                    array(
                        'type' => 'Checker\UUID',
                        'enabled' => true,
                    ),
                    array(
                        'type' => 'Checker\Colorspace',
                        'enabled' => false,
                        'options' => array(
                            'colorspaces' => array(
                                'cmyk',
                                'grayscale',
                                'rgb',
                            ),
                        ),
                    ),
                    array(
                        'type' => 'Checker\Dimension',
                        'enabled' => false,
                        'options' => array(
                            'width' => 80,
                            'height' => 160,
                        ),
                    ),
                    array(
                        'type' => 'Checker\Extension',
                        'enabled' => false,
                        'options' => array(
                            'extensions' => array(
                                'jpg',
                                'jpeg',
                                'bmp',
                                'tif',
                                'gif',
                                'png',
                                'pdf',
                                'doc',
                                'odt',
                                'mpg',
                                'mpeg',
                                'mov',
                                'avi',
                                'xls',
                                'flv',
                                'mp3',
                                'mp2',
                            ),
                        ),
                    ),
                    array(
                        'type' => 'Checker\Filename',
                        'enabled' => false,
                        'options' => array(
                            'sensitive' => true,
                        ),
                    ),
                    array(
                        'type' => 'Checker\MediaType',
                        'enabled' => false,
                        'options' => array(
                            'mediatypes' => array(
                                'Audio',
                                'Document',
                                'Flash',
                                'Image',
                                'Video',
                            ),
                        ),
                    ),
                ),
            ),
            'authentication' => array(
                'auto-create' => array(
                    'enabled' => false,
                    'templates' => array(
                    ),
                ),
                'captcha' => array(
                    'enabled' => true,
                    'trials-before-failure' => 9,
                ),
                'providers' => array(
                    'facebook' => array(
                        'enabled' => false,
                        'options' => array(
                            'app-id' => '',
                            'secret' => '',
                        ),
                    ),
                    'twitter' => array(
                        'enabled' => false,
                        'options' => array(
                            'consumer-key' => '',
                            'consumer-secret' => '',
                        ),
                    ),
                    'google-plus' => array(
                        'enabled' => false,
                        'options' => array(
                            'client-id' => '',
                            'client-secret' => '',
                        ),
                    ),
                    'github' => array(
                        'enabled' => false,
                        'options' => array(
                            'client-id' => '',
                            'client-secret' => '',
                        ),
                    ),
                    'viadeo' => array(
                        'enabled' => false,
                        'options' => array(
                            'client-id' => '',
                            'client-secret' => '',
                        ),
                    ),
                    'linkedin' => array(
                        'enabled' => false,
                        'options' => array(
                            'client-id' => '',
                            'client-secret' => '',
                        ),
                    ),
                ),
            ),
            'registration-fields' => array(
                array(
                    'name' => 'company',
                    'required' => true,
                ),
                array(
                    'name' => 'firstname',
                    'required' => true,
                ),
                array(
                    'name' => 'geonameid',
                    'required' => true,
                ),
            ),
        );
    }
}