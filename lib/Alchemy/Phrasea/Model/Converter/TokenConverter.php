<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TokenConverter implements ConverterInterface
{
    private $repository;

    public function __construct(TokenRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     *
     * @return Token
     */
    public function convert($value)
    {
        if (null === $token = $this->repository->findValidToken($value)) {
            throw new NotFoundHttpException('Token is not valid.');
        }

        return $token;
    }
}
