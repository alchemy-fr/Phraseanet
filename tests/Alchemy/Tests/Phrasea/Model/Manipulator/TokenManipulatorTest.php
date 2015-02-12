<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;

class TokenManipulatorTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideConstructorArguments
     */
    public function testCreate($user, $type, $expiration, $data)
    {
        $user = $user ? self::$DI['user'] : null;

        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $token = $manipulator->create($user, $type, $expiration, $data);

        $this->assertSame($user, $token->getUser());
        $this->assertSame($type, $token->getType());
        $this->assertSame($expiration, $token->getExpiration());
        $this->assertSame($data, $token->getData());

        $this->assertInternalType('string', $token->getValue());
        $this->assertEquals(32, strlen($token->getValue()));
    }

    public function provideConstructorArguments()
    {
        return [
            [true, TokenManipulator::TYPE_RSS, null, null],
            [false, TokenManipulator::TYPE_RSS, null, null],
            [false, TokenManipulator::TYPE_RSS, new \DateTime('-1 day'), 'data'],
        ];
    }

    public function testCreateBasketValidationToken()
    {
        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $token = $manipulator->createBasketValidationToken(self::$DI['basket_4'], self::$DI['user_1']);

        $this->assertSame(self::$DI['basket_4']->getId(), $token->getData());
        $this->assertSame(self::$DI['user_1'], $token->getUser());
        $this->assertSame(TokenManipulator::TYPE_VALIDATE, $token->getType());
        $this->assertDateNear('+10 days', $token->getExpiration());
    }

    public function testCreateBasketValidationTokenWithoutUser()
    {
        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $token = $manipulator->createBasketValidationToken(self::$DI['basket_4']);

        $this->assertSame(self::$DI['basket_4']->getId(), $token->getData());
        $this->assertSame(self::$DI['basket_4']->getValidation()->getInitiator(), $token->getUser());
        $this->assertSame(TokenManipulator::TYPE_VALIDATE, $token->getType());
        $this->assertDateNear('+10 days', $token->getExpiration());
    }

    public function testCreateBasketValidationTokenWithInvalidBasket()
    {
        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $this->setExpectedException('InvalidArgumentException', 'A validation token requires a validation basket.');
        $manipulator->createBasketValidationToken(self::$DI['basket_1']);
    }

    public function testCreateBasketAccessToken()
    {
        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $token = $manipulator->createBasketAccessToken(self::$DI['basket_4'], self::$DI['user']);

        $this->assertSame(self::$DI['basket_4']->getId(), $token->getData());
        $this->assertSame(self::$DI['user'], $token->getUser());
        $this->assertSame(TokenManipulator::TYPE_VIEW, $token->getType());
        $this->assertNull($token->getExpiration());
    }

    public function testCreateFeedEntryToken()
    {
        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $token = $manipulator->createFeedEntryToken(self::$DI['user'], self::$DI['feed_public_entry']);

        $this->assertSame(self::$DI['feed_public_entry']->getId(), $token->getData());
        $this->assertSame(self::$DI['user'], $token->getUser());
        $this->assertSame(TokenManipulator::TYPE_FEED_ENTRY, $token->getType());
        $this->assertNull($token->getExpiration());
    }

    public function testCreateDownloadToken()
    {
        $data = serialize(['some' => 'data']);
        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $token = $manipulator->createDownloadToken(self::$DI['user'], $data);

        $this->assertSame($data, $token->getData());
        $this->assertSame(self::$DI['user'], $token->getUser());
        $this->assertSame(TokenManipulator::TYPE_DOWNLOAD, $token->getType());
        $this->assertDateNear('+3 hours', $token->getExpiration());
    }

    public function testCreateEmailExportToken()
    {
        $data = serialize(['some' => 'data']);
        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $token = $manipulator->createEmailExportToken($data);

        $this->assertSame($data, $token->getData());
        $this->assertNull($token->getUser());
        $this->assertSame(TokenManipulator::TYPE_EMAIL, $token->getType());
        $this->assertDateNear('+1 day', $token->getExpiration());
    }

    public function testCreateResetEmailToken()
    {
        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $token = $manipulator->createResetEmailToken(self::$DI['user'], 'newemail@phraseanet.com');

        $this->assertSame('newemail@phraseanet.com', $token->getData());
        $this->assertSame(self::$DI['user'], $token->getUser());
        $this->assertSame(TokenManipulator::TYPE_EMAIL_RESET, $token->getType());
        $this->assertDateNear('+1 day', $token->getExpiration());
    }

    public function testCreateAccountUnlockToken()
    {
        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $token = $manipulator->createAccountUnlockToken(self::$DI['user']);

        $this->assertNull($token->getData());
        $this->assertSame(self::$DI['user'], $token->getUser());
        $this->assertSame(TokenManipulator::TYPE_ACCOUNT_UNLOCK, $token->getType());
        $this->assertDateNear('+3 days', $token->getExpiration());
    }

    public function testCreateResetPasswordToken()
    {
        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $token = $manipulator->createResetPasswordToken(self::$DI['user']);

        $this->assertNull($token->getData());
        $this->assertSame(self::$DI['user'], $token->getUser());
        $this->assertSame(TokenManipulator::TYPE_PASSWORD, $token->getType());
        $this->assertDateNear('+1 day', $token->getExpiration());
    }

    public function testUpdate()
    {
        $em = $this->createEntityManagerMock();
        $token = new Token();

        $em->expects($this->once())
            ->method('persist')
            ->with($token);
        $em->expects($this->once())
            ->method('flush');

        $manipulator = new TokenManipulator($em, self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $manipulator->update($token);
    }

    public function testDelete()
    {
        $em = $this->createEntityManagerMock();
        $token = new Token();

        $em->expects($this->once())
            ->method('remove')
            ->with($token);
        $em->expects($this->once())
            ->method('flush');

        $manipulator = new TokenManipulator($em, self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $manipulator->delete($token);
    }

    public function testRemoveExpiredToken()
    {
        $this->assertCount(4, self::$DI['app']['repo.tokens']->findAll());

        $manipulator = new TokenManipulator(self::$DI['app']['orm.em'], self::$DI['app']['random.low'], self::$DI['app']['repo.tokens']);
        $manipulator->removeExpiredTokens();

        $this->assertCount(3, self::$DI['app']['repo.tokens']->findAll());
    }
}
