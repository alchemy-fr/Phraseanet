<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\ApiAccount;
use Alchemy\Phrasea\Model\Entities\ApiOauthCode;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use RandomLib\Generator;

class ApiOauthCodeManipulator implements ManipulatorInterface
{
    private $om;
    private $repository;
    private $randomGenerator;

    public function __construct(ObjectManager $om, EntityRepository $repo, Generator $random)
    {
        $this->om = $om;
        $this->repository = $repo;
        $this->randomGenerator = $random;
    }

    public function create(ApiAccount $account, $redirectUri, $expire, $scope = null)
    {
        $code = new ApiOauthCode();

        $code->setCode($this->getNewCode());
        $this->doSetRedirectUri($code, $redirectUri);
        $code->setAccount($account);
        $code->setExpires($expire);
        $code->setScope($scope);

        $this->update($code);

        return $code;
    }

    public function delete(ApiOauthCode $code)
    {
        $this->om->remove($code);
        $this->om->flush();
    }

    public function update(ApiOauthCode $code)
    {
        $this->om->persist($code);
        $this->om->flush();
    }

    public function setCode(ApiOauthCode $code, $oauthCode)
    {
        $code->setCode($oauthCode);
        $this->update($code);
    }

    public function setRedirectUri(ApiOauthCode $code, $uri)
    {
        $this->doSetRedirectUri($code, $uri);
        $this->update($code);
    }

    private function doSetRedirectUri(ApiOauthCode $code, $uri)
    {
        if (false === filter_var($uri, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)
            && $uri !== ApiApplication::NATIVE_APP_REDIRECT_URI
        ) {
            throw new InvalidArgumentException(sprintf('Redirect Uri Url %s is not legal.', $uri));
        }

        $code->setRedirectUri($uri);
    }

    private function getNewCode()
    {
        do {
            $code = $this->randomGenerator->generateString(16, TokenManipulator::LETTERS_AND_NUMBERS);
        } while (null !== $this->repository->find($code));

        return $code;
    }
}
