<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Manipulator\ApiApplicationManipulator;
use Alchemy\Phrasea\Model\Entities\ApiApplication;

class ApiApplicationManipulatorTest extends \PhraseanetTestCase
{
    public function testCreateDesktopApplication()
    {
        $manipulator = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $nbApps = count(self::$DI['app']['repo.api-applications']->findAll());
        $application = $manipulator->create(
            'desktop-app',
            ApiApplication::DESKTOP_TYPE,
            'Desktop application description',
            'http://desktop-app-url.net'
        );
        $this->assertGreaterThan($nbApps, count(self::$DI['app']['repo.api-applications']->findAll()));
        $this->assertNotNull($application->getClientId());
        $this->assertNotNull($application->getClientSecret());
        $this->assertNotNull($application->getNonce());
        $this->assertEquals('desktop-app', $application->getName());
        $this->assertEquals(ApiApplication::DESKTOP_TYPE, $application->getType());
        $this->assertEquals('http://desktop-app-url.net', $application->getWebsite());
        $this->assertEquals(ApiApplication::NATIVE_APP_REDIRECT_URI, $application->getRedirectUri());
    }

    public function testCreateWebApplication()
    {
        $manipulator = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $nbApps = count(self::$DI['app']['repo.api-applications']->findAll());
        $application = $manipulator->create(
            'web-app',
            ApiApplication::WEB_TYPE,
            'Desktop application description',
            'http://web-app-url.net',
            self::$DI['user'],
            'http://web-app-url.net/callback'
        );

        $this->assertGreaterThan($nbApps, count(self::$DI['app']['repo.api-applications']->findAll()));
        $this->assertNotNull($application->getClientId());
        $this->assertNotNull($application->getClientSecret());
        $this->assertNotNull($application->getNonce());
        $this->assertEquals('web-app', $application->getName());
        $this->assertEquals(ApiApplication::WEB_TYPE, $application->getType());
        $this->assertEquals('http://web-app-url.net', $application->getWebsite());
        $this->assertEquals('http://web-app-url.net/callback', $application->getRedirectUri());
    }

    public function testDelete()
    {
        $manipulator = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $application = $manipulator->create(
            'desktop-app',
            ApiApplication::DESKTOP_TYPE,
            'Desktop application description',
            'http://desktop-app-url.net'
        );
        $applicationSave = clone $application;
        $countBefore = count(self::$DI['app']['repo.api-applications']->findAll());
        $account = self::$DI['app']['manipulator.api-account']->create($application, self::$DI['user']);
        $accountMem = clone $account;
        self::$DI['app']['manipulator.api-oauth-token']->create($account);
        $manipulator->delete($application);
        $this->assertGreaterThan(count(self::$DI['app']['repo.api-applications']->findAll()), $countBefore);
        $accounts = self::$DI['app']['repo.api-accounts']->findByUserAndApplication(self::$DI['user'], $applicationSave);
        $this->assertEquals(0, count($accounts));
        $tokens = self::$DI['app']['repo.api-oauth-tokens']->findOauthTokens($accountMem);
        $this->assertEquals(0, count($tokens));
    }

    public function testUpdate()
    {
        $manipulator = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $application = $manipulator->create(
            'desktop-app',
            ApiApplication::DESKTOP_TYPE,
            'Desktop application description',
            'http://desktop-app-url.net'
        );
        $application->setName('new-desktop-app');
        $manipulator->update($application);
        $application =  self::$DI['app']['repo.api-applications']->find($application->getId());
        $this->assertEquals('new-desktop-app', $application->getName());
    }

    public function testSetType()
    {
        $manipulator = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $application = $manipulator->create(
            'desktop-app',
            ApiApplication::DESKTOP_TYPE,
            'Desktop application description',
            'http://desktop-app-url.net'
        );
        try {
            $manipulator->setType($application, 'invalid-type');
            $this->fail('Invalid argument exception should be raised');
        } catch (InvalidArgumentException $e) {

        }
    }

    public function testSetRedirectUri()
    {
        $manipulator = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $application = $manipulator->create(
            'desktop-app',
            ApiApplication::DESKTOP_TYPE,
            'Desktop application description',
            'http://desktop-app-url.net'
        );

        $manipulator->setRedirectUri($application, 'invalid-url.com');
        $this->assertEquals(ApiApplication::NATIVE_APP_REDIRECT_URI, $application->getRedirectUri());

        $application = $manipulator->create(
            'web-app',
            ApiApplication::WEB_TYPE,
            'Desktop application description',
            'http://web-app-url.net',
            self::$DI['user'],
            'http://web-app-url.net/callback'
        );
        try {
            $manipulator->setWebsiteUrl($application, 'invalid-url.com');
            $this->fail('Invalid argument exception should be raised');
        } catch (InvalidArgumentException $e) {

        }
    }

    public function testSetWebsiteUrl()
    {
        $manipulator = new ApiApplicationManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-applications'], self::$DI['app']['random.medium']);
        $application = $manipulator->create(
            'desktop-app',
            ApiApplication::DESKTOP_TYPE,
            'Desktop application description',
            'http://desktop-app-url.net'
        );
        try {
            $manipulator->setWebsiteUrl($application, 'invalid-url.com');
            $this->fail('Invalid argument exception should be raised');
        } catch (InvalidArgumentException $e) {

        }
    }
}
