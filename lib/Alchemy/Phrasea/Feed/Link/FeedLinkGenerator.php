<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\Link;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Doctrine\ORM\EntityManager;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedToken;
use RandomLib\Generator;
use Symfony\Component\Routing\Generator\UrlGenerator;

class FeedLinkGenerator implements LinkGeneratorInterface
{
    const FORMAT_ATOM = 'atom';
    const FORMAT_RSS  = 'rss';

    private $em;
    private $generator;
    private $random;

    /**
     * @param UrlGenerator  $generator
     * @param EntityManager $em
     * @param Generator     $random
     */
    public function __construct(UrlGenerator $generator, EntityManager $em, Generator $random)
    {
        $this->generator = $generator;
        $this->em = $em;
        $this->random = $random;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(FeedInterface $feed, User $user, $format, $page = null, $renew = false)
    {
        if (!$this->supports($feed)) {
            throw new InvalidArgumentException('FeedLinkGenerator only support aggregate feeds.');
        }

        switch ($format) {
            case self::FORMAT_ATOM:
                $params = [
                    'token'  => $this->getFeedToken($feed, $user, $renew)->getValue(),
                    'id'     => $feed->getId(),
                    'format' => 'atom'
                ];
                if (null !== $page) {
                    $params['page'] = $page;
                }

                return new FeedLink(
                    $this->generator->generate('feed_user', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $feed->getTitle(), 'Atom'),
                    'application/atom+xml'
                );
            case self::FORMAT_RSS:
                $params = [
                    'token'  => $this->getFeedToken($feed, $user, $renew)->getValue(),
                    'id'     => $feed->getId(),
                    'format' => 'rss'
                ];
                if (null !== $page) {
                    $params['page'] = $page;
                }

                return new FeedLink(
                    $this->generator->generate('feed_user', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $feed->getTitle(), 'RSS'),
                    'application/rss+xml'
                );
            default:
                throw new InvalidArgumentException(sprintf('Format %s is not recognized.', $format));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FeedInterface $feed)
    {
        return $feed instanceof Feed;
    }

    /**
     * {@inheritdoc}
     */
    public function generatePublic(FeedInterface $feed, $format, $page = null)
    {
        if (!$this->supports($feed)) {
            throw new InvalidArgumentException('FeedLinkGenerator only support aggregate feeds.');
        }

        switch ($format) {
            case self::FORMAT_ATOM:
                $params = [
                    'id'     => $feed->getId(),
                    'format' => 'atom'
                ];
                if (null !== $page) {
                    $params['page'] = $page;
                }

                return new FeedLink(
                    $this->generator->generate('feed_public', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $feed->getTitle(), 'Atom'),
                    'application/atom+xml'
                );
            case self::FORMAT_RSS:
                $params = [
                    'id'     => $feed->getId(),
                    'format' => 'rss'
                ];
                if (null !== $page) {
                    $params['page'] = $page;
                }

                return new FeedLink(
                    $this->generator->generate('feed_public', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $feed->getTitle(), 'RSS'),
                    'application/rss+xml'
                );
            default:
                throw new InvalidArgumentException(sprintf('Format %s is not recognized.', $format));
        }
    }

    private function getFeedToken(Feed $feed, User $user, $renew = false)
    {
        $token = $this->em
            ->getRepository('Phraseanet:FeedToken')
            ->findOneBy(['user' => $user, 'feed' => $feed]);

        if (null === $token || true === $renew) {
            if (null === $token) {
                $token = new FeedToken();
                $token->setFeed($feed);
                $token->setUser($user);
                $feed->addToken($token);

                $this->em->persist($feed);
            }

            $token->setValue($this->random->generateString(64, TokenManipulator::LETTERS_AND_NUMBERS));
            $this->em->persist($token);
            $this->em->flush();
        }

        return $token;
    }
}
