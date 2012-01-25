<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class CoreTest extends PhraseanetPHPUnitAbstract
{

  public function testCoreVersion()
  {
    $this->assertInstanceOf('\Alchemy\Phrasea\Core\Version', self::$core->getVersion());
  }

  public function testCoreRegistry()
  {
    $this->assertInstanceOf('\registryInterface', self::$core->getRegistry());
  }

  public function testCoreEntityManager()
  {
    $this->assertInstanceOf('\Doctrine\ORM\EntityManager', self::$core->getEntityManager());
  }

  public function testCoreTemplateEngine()
  {
    $this->assertInstanceOf('\Twig_Environment', self::$core->getTwig());
  }

  public function testCoreSerializer()
  {
    $this->assertInstanceOf('\Symfony\Component\Serializer\Serializer', self::$core->getSerializer());
  }

  public function testCoreConfiguration()
  {
    $this->assertInstanceOf('\Alchemy\Phrasea\Core\Configuration', self::$core->getConfiguration());
  }

  public function testIsAuthenticathed()
  {
    $this->assertFalse(self::$core->isAuthenticated());
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $auth = new Session_Authentication_None(self::$user);
    $session->authenticate($auth);
    $this->assertTrue(self::$core->isAuthenticated());
    $session->logout();
  }

  public function testGetAuthenticathed()
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $auth = new Session_Authentication_None(self::$user);
    $session->authenticate($auth);
    $this->assertInstanceOf("\User_Adapter", self::$core->getAuthenticatedUser());
    $session->logout();
  }

  public function testGetAvailableLanguages()
  {
    $languages = \Alchemy\Phrasea\Core::getAvailableLanguages();
    $this->assertTrue(is_array($languages));
    $this->assertEquals(5, count($languages));
    $this->assertTrue(array_key_exists("ar_SA", $languages));
    $this->assertTrue(array_key_exists("de_DE", $languages));
    $this->assertTrue(array_key_exists("en_GB", $languages));
    $this->assertTrue(array_key_exists("es_ES", $languages));
    $this->assertTrue(array_key_exists("fr_FR", $languages));
  }

  public function testGetPhpConf()
  {
    \Alchemy\Phrasea\Core::initPHPConf();
//    $this->assertEquals("4096", ini_get('output_buffering'));
    $this->assertGreaterThanOrEqual(2048, (int) ini_get('memory_limit'));
    $this->assertEquals("6143", ini_get('error_reporting'));
    $this->assertEquals("UTF-8", ini_get('default_charset'));
    $this->assertEquals("1", ini_get('session.use_cookies'));
    $this->assertEquals("1", ini_get('session.use_only_cookies'));
    $this->assertEquals("0", ini_get('session.auto_start'));
    $this->assertEquals("1", ini_get('session.hash_function'));
    $this->assertEquals("6", ini_get('session.hash_bits_per_character'));
    $this->assertEquals("1", ini_get('allow_url_fopen'));
  }

  public function testGetEnv()
  {
    $core = new \Alchemy\Phrasea\Core("test");
    $this->assertEquals("test", $core->getEnv());
    $core = new \Alchemy\Phrasea\Core("prod");
    $this->assertEquals("prod", $core->getEnv());
  }

  public function testNotInstalled()
  {
    
    if (!extension_loaded('test_helpers'))
    {
      $this->markTestSkipped();
    }
    
    set_new_overload(array($this, 'newCallback'));

    $handler = new \Alchemy\Phrasea\Core\Configuration\Handler(
                    new \Alchemy\Phrasea\Core\Configuration\Application()
                    , new \Alchemy\Phrasea\Core\Configuration\Parser\Yaml()
    );
    $class = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration'
            , array('isInstalled')
            , array($handler)
            , 'ConfMock'
    );

    $class->expects($this->any())
            ->method('isInstalled')
            ->will($this->returnValue(false));

    $core = new \Alchemy\Phrasea\Core("test");

    $this->assertInstanceOf("\Setup_Registry", $core->getRegistry());

    unset_new_overload();
  }

  protected function newCallback($className)
  {
    switch ($className)
    {
      case 'Alchemy\Phrasea\Core\Configuration': return 'ConfMock';
      default: return $className;
    }
  }

}