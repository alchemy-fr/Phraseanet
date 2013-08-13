<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\Link;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Feed\FeedInterface;
use Doctrine\ORM\EntityManager;
use Entities\Feed;
use Entities\FeedToken;
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
     * @param \random       $random
     */
    public function __construct(UrlGenerator $generator, EntityManager $em, \random $random)
    {
        $this->generator = $generator;
        $this->em = $em;
        $this->random = $random;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(FeedInterface $feed, \User_Adapter $user, $format, $page = null, $renew = false)
    {
        if (!$this->supports($feed)) {
            throw new InvalidArgumentException('FeedLinkGenerator only support aggregate feeds.');
        }

        switch ($format) {
            case self::FORMAT_ATOM:
                $params = array(
                    'token'  => $this->getFeedToken($feed, $user, $renew)->getValue(),
                    'id'     => $feed->getId(),
                    'format' => 'atom'
                );
                if (null !== $page) {
                    $params['page'] = $page;
                }

                return new FeedLink(
                    $this->generator->generate('feed_user', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $feed->getTitle(), 'Atom'),
                    'application/atom+xml'
                );
            case self::FORMAT_RSS:
                $params = array(
                    'token'  => $this->getFeedToken($feed, $user, $renew)->getValue(),
                    'id'     => $feed->getId(),
                    'format' => 'rss'
                );
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
                $params = array(
                    'id'     => $feed->getId(),
                    'format' => 'atom'
                );
                if (null !== $page) {
                    $params['page'] = $page;
                }

                return new FeedLink(
                    $this->generator->generate('feed_public', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $feed->getTitle(), 'Atom'),
                    'application/atom+xml'
                );
            case self::FORMAT_RSS:
                $params = array(
                    'id'     => $feed->getId(),
                    'format' => 'rss'
                );
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

    private function getFeedToken(Feed $feed, \User_Adapter $user, $renew = false)
    {
        $token = $this->em
            ->getRepository('Entities\FeedToken')
            ->findByFeedAndUser($feed, $user);

        if (null === $token || true === $renew) {
            if (null === $token) {
                $token = new FeedToken();
                $token->setFeed($feed);
                $token->setUsrId($user->get_id());
                $feed->addToken($token);

                $this->em->persist($feed);
            }

            $token->setValue($this->random->generatePassword(12, \random::LETTERS_AND_NUMBERS));
            $this->em->persist($token);
            $this->em->flush();
        }

        return $token;
    }
}
