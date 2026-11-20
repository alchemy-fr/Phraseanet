<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Controller;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\HttpFoundation\Response;

final class ControllerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ACLProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $aclProvider;
    /** @var Authenticator|\PHPUnit\Framework\MockObject\MockObject */
    private $authenticator;
    /** @var \Twig\Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;
    /** @var \appbox|\PHPUnit\Framework\MockObject\MockObject */
    private $appbox;
    /** @var Application|\PHPUnit\Framework\MockObject\MockObject */
    private $app;
    /** @var Controller */
    private $sut;

    protected function setUp(): void
    {
        $this->appbox = $this->createMock(\appbox::class);

        $this->twig = $this->createMock(\Twig\Environment::class);

        $this->aclProvider = $this->createMock(ACLProvider::class);

        $this->authenticator = $this->createMock(Authenticator::class);

        $this->app = $this->createMock(Application::class);

        $this->app
            ->method('offsetGet')
            ->willReturnMap([
                ['phraseanet.appbox', $this->appbox],
                ['twig', $this->twig],
                ['authentication', $this->authenticator],
                ['acl', $this->aclProvider],
            ]);

        $this->sut = new Controller($this->app);
    }

    public function testItCanFetchApplicationBox()
    {
        $this->assertInstanceOf(\appbox::class, $this->sut->getApplicationBox());
    }

    public function testItCanFetchDataboxById()
    {
        $databox = $this->createStub(\databox::class);
        $this->appbox->expects($this->once())
            ->method('get_databox')
            ->with(42)
            ->willReturn($databox);

        $this->assertSame($databox, $this->sut->findDataboxById(42));
    }

    public function testItCanRenderTwigTemplate()
    {
        $name = 'template_name';
        $context = ['foo' => 'bar'];
        $this->twig->expects($this->once())
            ->method('render')
            ->with($name, $context)
            ->willReturn('foo content');

        $this->assertSame('foo content', $this->sut->render($name, $context));
    }

    public function testItCanWrapATwigTemplateIntoAResponse()
    {
        $name = 'template_name';
        $context = ['foo' => 'bar'];
        $status = 400;
        $headers = [ 'baz' => 'bim'];
        $this->twig->expects($this->once())
            ->method('render')
            ->with($name, $context)
            ->willReturn('foo content');

        $response = $this->sut->renderResponse($name, $context, $status, $headers);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('foo content', $response->getContent());
        $this->assertTrue($response->headers->contains('baz', 'bim'));
    }

    public function testItCanRetrieveAclProvider()
    {
        $this->assertSame($this->aclProvider, $this->sut->getAclProvider());
    }

    public function testItCanRetrieveAuthenticator()
    {
        $this->assertSame($this->authenticator, $this->sut->getAuthenticator());
    }

    public function testItCanRetrieveAuthenticatedUser()
    {
        $user = new User();
        $this->authenticator->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertSame($user, $this->sut->getAuthenticatedUser());
    }

    public function testItCanCreateAclForAGivenUser()
    {
        $user = new User();
        $acl = $this->createStub(\ACL::class);

        $this->authenticator->expects($this->never())
            ->method('getUser');

        $this->aclProvider->expects($this->once())
            ->method('get')
            ->with($user)
            ->willReturn($acl);

        $this->assertSame($acl, $this->sut->getAclForUser($user));
    }

    public function testItCanCreateAclForCurrentlyLoggedUser()
    {
        $user = new User();
        $acl = $this->createStub(\ACL::class);

        $this->authenticator->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->aclProvider->expects($this->once())
            ->method('get')
            ->with($user)
            ->willReturn($acl);

        $this->assertSame($acl, $this->sut->getAclForUser());
    }
}
