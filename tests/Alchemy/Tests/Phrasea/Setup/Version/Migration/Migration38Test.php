<?php

namespace Alchemy\Tests\Phrasea\Setup\Version\Migration;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Version\Migration\Migration38;
use Alchemy\Tests\Phrasea\Setup\AbstractSetupTester;

class Migration38Test extends AbstractSetupTester
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
        foreach (['binaries.yml.bkp', 'config.yml.bkp', 'connexions.yml.bkp', 'services.yml.bkp'] as $backupFile) {
            if (is_file(__DIR__ . '/../Probe/fixtures-3807/config/' . $backupFile)) {
                rename(__DIR__ . '/../Probe/fixtures-3807/config/' . $backupFile, __DIR__ . '/../Probe/fixtures-3807/config/' . substr($backupFile, 0, -4));
            }
        }
    }

    public function testMigrate()
    {
        $app = new Application();
        $app['configuration.store'] = $this->getMock('Alchemy\Phrasea\Core\Configuration\ConfigurationInterface');
        $app['root.path'] = __DIR__ . '/../Probe/fixtures-3807';

        $app['configuration.store']->expects($this->once())
            ->method('initialize');

        $app['configuration.store']->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($this->getCurrent()));

        $app['configuration.store']->expects($this->once())
            ->method('setConfig')
            ->with($this->getModified());

        $migration = new Migration38($app);
        $migration->migrate();
    }

    private function getModified()
    {
        $modified = $this->getCurrent();

        $modified['main']['key'] = '1234567890';
        $modified['main']['servername'] = 'http://sub.domain.tld/';
        $modified['main']['maintenance'] = true;
        $modified['main']['binaries']['test_binary'] = '/path/to/test/binary/file';
        $modified['main']['database'] = array_replace($modified['main']['database'], [
            'host' => 'sql-host',
            'port' => '13306',
            'user' => 'username',
            'password' => 's3cr3t',
            'dbname' => 'phrasea_db',
        ]);
        $modified['main']['cache'] = [
            'type' => 'MemcacheCache',
            'options' => [
                'host' => 'memcache-host',
                'port' => 21211,
            ]
        ];
        $modified['main']['opcodecache'] = [
            'type' => 'ApcCache',
            'options' => [],
        ];
        $modified['border-manager']['enabled'] = false;

        return $modified;
    }

    private function getCurrent()
    {
        return [
            'main' => [
                'servername' => 'http://local.phrasea/',
                'maintenance' => false,
                'database' => [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'user' => 'root',
                    'password' => '',
                    'dbname' => 'ab_test',
                    'driver' => 'pdo_mysql',
                    'charset' => 'UTF8',
                ],
                'database-test' => [
                    'driver' => 'pdo_sqlite',
                    'path' => '/tmp/db.sqlite',
                    'charset' => 'UTF8',
                ],
                'api-timers' => true,
                'cache' => [
                    'type' => 'ArrayCache',
                    'options' => [
                    ],
                ],
                'opcodecache' => [
                    'type' => 'ArrayCache',
                    'options' => [
                    ],
                ],
                'search-engine' => [
                    'type' => 'Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine',
                    'options' => [
                    ],
                ],
                'task-manager' => [
                    'options' => '',
                ],
                'key' => null,
            ],
            'binaries' => [
                'legacy_binay' => '/path/to/legacy/binary',
            ],
            'border-manager' => [
                'enabled' => true,
                'checkers' => [
                    [
                        'type' => 'Checker\Sha256',
                        'enabled' => true,
                    ],
                    [
                        'type' => 'Checker\UUID',
                        'enabled' => true,
                    ],
                    [
                        'type' => 'Checker\Colorspace',
                        'enabled' => false,
                        'options' => [
                            'colorspaces' => [
                                'cmyk',
                                'grayscale',
                                'rgb',
                            ],
                        ],
                    ],
                    [
                        'type' => 'Checker\Dimension',
                        'enabled' => false,
                        'options' => [
                            'width' => 80,
                            'height' => 160,
                        ],
                    ],
                    [
                        'type' => 'Checker\Extension',
                        'enabled' => false,
                        'options' => [
                            'extensions' => [
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
                            ],
                        ],
                    ],
                    [
                        'type' => 'Checker\Filename',
                        'enabled' => false,
                        'options' => [
                            'sensitive' => true,
                        ],
                    ],
                    [
                        'type' => 'Checker\MediaType',
                        'enabled' => false,
                        'options' => [
                            'mediatypes' => [
                                'Audio',
                                'Document',
                                'Flash',
                                'Image',
                                'Video',
                            ],
                        ],
                    ],
                ],
            ],
            'authentication' => [
                'auto-create' => [
                    'templates' => [
                    ],
                ],
                'captcha' => [
                    'enabled' => true,
                    'trials-before-display' => 9,
                ],
                'providers' => [
                    'facebook' => [
                        'enabled' => false,
                        'options' => [
                            'app-id' => '',
                            'secret' => '',
                        ],
                    ],
                    'twitter' => [
                        'enabled' => false,
                        'options' => [
                            'consumer-key' => '',
                            'consumer-secret' => '',
                        ],
                    ],
                    'google-plus' => [
                        'enabled' => false,
                        'options' => [
                            'client-id' => '',
                            'client-secret' => '',
                        ],
                    ],
                    'github' => [
                        'enabled' => false,
                        'options' => [
                            'client-id' => '',
                            'client-secret' => '',
                        ],
                    ],
                    'viadeo' => [
                        'enabled' => false,
                        'options' => [
                            'client-id' => '',
                            'client-secret' => '',
                        ],
                    ],
                    'linkedin' => [
                        'enabled' => false,
                        'options' => [
                            'client-id' => '',
                            'client-secret' => '',
                        ],
                    ],
                ],
            ],
            'registration-fields' => [
                [
                    'name' => 'company',
                    'required' => true,
                ],
                [
                    'name' => 'firstname',
                    'required' => true,
                ],
                [
                    'name' => 'geonameid',
                    'required' => true,
                ],
            ],
        ];
    }
}
