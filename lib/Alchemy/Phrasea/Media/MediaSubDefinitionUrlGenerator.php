<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media;

use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Provider\SecretProvider;
use Assert\Assertion;
use Firebase\JWT\JWT;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MediaSubDefinitionUrlGenerator
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var SecretProvider
     */
    private $secretProvider;

    /**
     * @var int
     */
    private $defaultTTL;

    public function __construct(UrlGeneratorInterface $urlGenerator, SecretProvider $secretProvider, $defaultTTL = 0)
    {
        Assertion::integer($defaultTTL);

        $this->urlGenerator = $urlGenerator;
        $this->secretProvider = $secretProvider;
        $this->defaultTTL = (int)$defaultTTL;
    }

    /**
     * @return int
     */
    public function getDefaultTTL()
    {
        return $this->defaultTTL;
    }

    /**
     * @param User $issuer
     * @param \media_subdef $subdef
     * @param int $url_ttl
     * @return string
     */
    public function generate(User $issuer, \media_subdef $subdef, $url_ttl = null)
    {
        $url_ttl = $url_ttl ?: $this->defaultTTL;

        $payload = [
            'iat'  => time(),
            'iss'  => $issuer->getId(),
            'sdef' => [$subdef->get_sbas_id(), $subdef->get_record_id(), $subdef->get_name()],
        ];

        if ($url_ttl > 0) {
            $payload['exp'] = $payload['iat'] + $url_ttl;
        }

        $secret = $this->secretProvider->getSecretForUser($issuer);

        return $this->urlGenerator->generate('media_accessor', [
            'token' => JWT::encode($payload, $secret->getToken(), 'HS256', $secret->getId()),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @param User $issuer
     * @param \media_subdef[] $subdefs
     * @param int $url_ttl
     * @return string[]
     */
    public function generateMany(User $issuer, $subdefs, $url_ttl = null)
    {
        Assertion::allIsInstanceOf($subdefs, \media_subdef::class);
        $url_ttl = $url_ttl ?: $this->defaultTTL;

        $payloads = [];

        $payload = [
            'iat' => time(),
            'iss' => $issuer->getId(),
            'sdef' => null,
        ];

        if ($url_ttl > 0) {
            $payload['exp'] = $payload['iat'] + $url_ttl;
        }

        foreach ($subdefs as $index => $subdef) {
            $payload['sdef'] = [$subdef->get_sbas_id(), $subdef->get_record_id(), $subdef->get_name()];

            $payloads[$index] = $payload;
        }

        $secret = $this->secretProvider->getSecretForUser($issuer);

        return array_map(function ($payload) use ($secret) {
            return $this->urlGenerator->generate('media_accessor', [
                'token' => JWT::encode($payload, $secret->getToken(), 'HS256', $secret->getId()),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }, $payloads);
    }
}
