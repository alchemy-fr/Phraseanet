<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\NotificationDelivererServiceProvider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\NotificationDelivererServiceProvider
 */
class NotificationDelivererServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\NotificationDelivererServiceProvider', 'notification.deliverer', 'Alchemy\\Phrasea\\Notification\\Deliverer'),
        );
    }

    /**
     * @dataProvider provideConfigurationData
     */
    public function testWithoutSmtpSettings($instance, $values)
    {
        self::$DI['app']->register(new NotificationDelivererServiceProvider());

        self::$DI['app']['phraseanet.registry'] = $this->getMock('registryInterface');
        self::$DI['app']['phraseanet.registry']->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($key) use ($values) {

                if (!isset($values[$key])) {
                    throw new \InvalidArgumentException(sprintf('Invalid key %s', $key));
                }

                return $values[$key];
            }));

        $this->assertInstanceOf($instance, self::$DI['app']['swiftmailer.transport']);

        if (!self::$DI['app']['swiftmailer.transport'] instanceof \Swift_Transport_MailTransport) {
            $this->assertEquals($values['expected-host'], self::$DI['app']['swiftmailer.transport']->getHost());
            $this->assertEquals($values['expected-port'], self::$DI['app']['swiftmailer.transport']->getPort());
            $this->assertEquals($values['expected-encryption'], self::$DI['app']['swiftmailer.transport']->getEncryption());
            $this->assertEquals($values['expected-username'], self::$DI['app']['swiftmailer.transport']->getUsername());
            $this->assertEquals($values['expected-password'], self::$DI['app']['swiftmailer.transport']->getPassword());
            $this->assertEquals($values['expected-authmode'], self::$DI['app']['swiftmailer.transport']->getAuthMode());
        }
    }

    public function provideConfigurationData()
    {
        return array(
            array('\Swift_Transport_EsmtpTransport', array(
                'GV_smtp' => true,
                'GV_smtp_auth' => true,
                'GV_smtp_host' => 'special.host.mail',
                'GV_smtp_port' => 3306,
                'GV_smtp_user' => 'superman',
                'GV_smtp_password' => 'b4tm4n',
                'GV_smtp_secure' => 'ssl',
                'expected-host' => 'special.host.mail',
                'expected-port' => 3306,
                'expected-encryption' => 'ssl',
                'expected-username' => 'superman',
                'expected-password' => 'b4tm4n',
                'expected-authmode' => null,
            )),
            array('\Swift_Transport_MailTransport', array(
                'GV_smtp' => false,
                'GV_smtp_auth' => true,
                'GV_smtp_host' => 'special.host.mail',
                'GV_smtp_port' => 3306,
                'GV_smtp_user' => 'superman',
                'GV_smtp_password' => 'b4tm4n',
                'GV_smtp_secure' => 'tls',
                'expected-host' => 'special.host.mail',
                'expected-port' => 3306,
                'expected-encryption' => 'tls',
                'expected-username' => 'superman',
                'expected-password' => 'b4tm4n',
                'expected-authmode' => null,
            )),
            array('\Swift_Transport_EsmtpTransport', array(
                'GV_smtp' => true,
                'GV_smtp_auth' => false,
                'GV_smtp_host' => 'special.host.mail',
                'GV_smtp_port' => 3306,
                'GV_smtp_user' => 'superman',
                'GV_smtp_password' => 'b4tm4n',
                'GV_smtp_secure' => 'ssl',
                'expected-host' => 'special.host.mail',
                'expected-port' => 3306,
                'expected-encryption' => 'ssl',
                'expected-username' => null,
                'expected-password' => null,
                'expected-authmode' => null,
            )),
            array('\Swift_Transport_MailTransport', array(
                'GV_smtp' => false,
                'GV_smtp_auth' => false,
                'expected-host' => null,
                'expected-port' => null,
                'expected-encryption' => null,
                'expected-username' => null,
                'expected-password' => null,
                'expected-authmode' => null,
            )),
        );
    }
}
