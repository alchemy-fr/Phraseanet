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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Model\Entities\ApiAccount;
use Alchemy\Phrasea\Model\Entities\ApiOauthCode;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
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

    public function create(ApiAccount $account, $redirectUri, \DateTime $expire = null, $scope = null)
    {
        $code = new ApiOauthCode();

        $code->setCode($this->getNewCode());
        $code->setRedirectUri($redirectUri);
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

    private function getNewCode()
    {
        do {
            $code = $this->randomGenerator->generateString(16, TokenManipulator::LETTERS_AND_NUMBERS);
        } while (null !== $this->repository->find($code));

        return $code;
    }
}
