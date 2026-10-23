<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\NotificationDelivererServiceProvider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\NotificationDelivererServiceProvider
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class NotificationDelivererServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\NotificationDelivererServiceProvider', 'notification.deliverer', 'Alchemy\\Phrasea\\Notification\\Deliverer'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideConfigurationData')]
    public function testWithoutSmtpSettings($instance, $values)
    {
        self::$DI['app']->register(new NotificationDelivererServiceProvider());

        self::$DI['app']['conf'] = $this->createStub('Alchemy\Phrasea\Core\Configuration\PropertyAccess');
        self::$DI['app']['conf']
            ->method('get')
            ->will($this->returnCallback(function ($key) use ($values) {
                if (!is_scalar($key)) {
                    $key = serialize($key);
                }
                if (!isset($values[$key])) {
                    throw new \InvalidArgumentException(sprintf('Invalid key %s', $key));
                }

                return $values[$key];
            }));

        $this->assertInstanceOf($instance, self::$DI['app']['swiftmailer.transport']);

        if (!self::$DI['app']['swiftmailer.transport'] instanceof \Swift_Transport_SendmailTransport) {
            $this->assertEquals($values['expected-host'], self::$DI['app']['swiftmailer.transport']->getHost());
            $this->assertEquals($values['expected-port'], self::$DI['app']['swiftmailer.transport']->getPort());
            $this->assertEquals($values['expected-encryption'], self::$DI['app']['swiftmailer.transport']->getEncryption());
            $this->assertEquals($values['expected-username'], self::$DI['app']['swiftmailer.transport']->getUsername());
            $this->assertEquals($values['expected-password'], self::$DI['app']['swiftmailer.transport']->getPassword());
            $this->assertEquals($values['expected-authmode'], self::$DI['app']['swiftmailer.transport']->getAuthMode());
        }
    }

    public static function provideConfigurationData()
    {
        return [
            ['\Swift_Transport_EsmtpTransport', [
                serialize(['registry', 'email', 'smtp-enabled']) => true,
                serialize(['registry', 'email', 'smtp-auth-enabled']) => true,
                serialize(['registry', 'email', 'smtp-host']) => 'special.host.mail',
                serialize(['registry', 'email', 'smtp-port']) => 3306,
                serialize(['registry', 'email', 'smtp-user']) => 'superman',
                serialize(['registry', 'email', 'smtp-password']) => 'b4tm4n',
                serialize(['registry', 'email', 'smtp-secure-mode']) => 'ssl',
                'expected-host' => 'special.host.mail',
                'expected-port' => 3306,
                'expected-encryption' => 'ssl',
                'expected-username' => 'superman',
                'expected-password' => 'b4tm4n',
                'expected-authmode' => null,
            ]],
            ['\Swift_Transport_SendmailTransport', [
                serialize(['registry', 'email', 'smtp-enabled']) => false,
                serialize(['registry', 'email', 'smtp-auth-enabled']) => true,
                serialize(['registry', 'email', 'smtp-host']) => 'special.host.mail',
                serialize(['registry', 'email', 'smtp-port']) => 3306,
                serialize(['registry', 'email', 'smtp-user']) => 'superman',
                serialize(['registry', 'email', 'smtp-password']) => 'b4tm4n',
                serialize(['registry', 'email', 'smtp-secure-mode']) => 'tls',
                'expected-host' => 'special.host.mail',
                'expected-port' => 3306,
                'expected-encryption' => 'tls',
                'expected-username' => 'superman',
                'expected-password' => 'b4tm4n',
                'expected-authmode' => null,
            ]],
            ['\Swift_Transport_EsmtpTransport', [
                serialize(['registry', 'email', 'smtp-enabled']) => true,
                serialize(['registry', 'email', 'smtp-auth-enabled']) => false,
                serialize(['registry', 'email', 'smtp-host']) => 'special.host.mail',
                serialize(['registry', 'email', 'smtp-port']) => 3306,
                serialize(['registry', 'email', 'smtp-user']) => 'superman',
                serialize(['registry', 'email', 'smtp-password']) => 'b4tm4n',
                serialize(['registry', 'email', 'smtp-secure-mode']) => 'ssl',
                'expected-host' => 'special.host.mail',
                'expected-port' => 3306,
                'expected-encryption' => 'ssl',
                'expected-username' => null,
                'expected-password' => null,
                'expected-authmode' => null,
            ]],
            ['\Swift_Transport_SendmailTransport', [
                serialize(['registry', 'email', 'smtp-enabled']) => false,
                serialize(['registry', 'email', 'smtp-auth-enabled']) => false,
                'expected-host' => null,
                'expected-port' => null,
                'expected-encryption' => null,
                'expected-username' => null,
                'expected-password' => null,
                'expected-authmode' => null,
            ]],
        ];
    }
}
