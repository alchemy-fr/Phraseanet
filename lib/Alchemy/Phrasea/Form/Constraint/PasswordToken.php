<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;

class PasswordToken extends Constraint
{
    public $message = 'The token provided is not valid anymore';
    private $repository;

    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
        parent::__construct();
    }

    public function isValid($tokenValue)
    {
        if (null === $token = $this->repository->findValidToken($tokenValue)) {
            return false;
        }

        return TokenManipulator::TYPE_PASSWORD === $token->getType();
    }

    public static function create(Application $app)
    {
        return new static($app['repo.tokens']);
    }
}
