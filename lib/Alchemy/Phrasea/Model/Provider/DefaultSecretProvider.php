<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Model\Provider;

use Alchemy\Phrasea\Model\Entities\Secret;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\SecretRepository;
use RandomLib\Generator;

class DefaultSecretProvider implements SecretProvider
{
    /** @var SecretRepository */
    private $repository;
    /** @var Generator */
    private $generator;

    public function __construct(SecretRepository $repository, Generator $generator)
    {
        $this->repository = $repository;
        $this->generator = $generator;
    }

    public function getSecretForUser(User $user)
    {
        $secret = $this->repository->findOneBy(['creator' => $user], ['created' => 'DESC']);
        if ($secret) {
            return $secret;
        }

        $token = $this->generator->generateString(64, Generator::CHAR_ALNUM | Generator::CHAR_SYMBOLS);

        $secret = new Secret($user, $token);
        $this->repository->save($secret);

        return $secret;
    }
}
