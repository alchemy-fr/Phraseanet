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
use Alchemy\Phrasea\Feed\Aggregate;
use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\Feed\Link\FeedLink;
use Alchemy\Phrasea\Model\Entities\AggregateToken;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Generator\UrlGenerator;

class AggregateLinkGenerator implements LinkGeneratorInterface
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
    public function generate(FeedInterface $aggregate, \User_Adapter $user, $format, $page = null, $renew = false)
    {
        if (!$this->supports($aggregate)) {
            throw new InvalidArgumentException('AggregateLinkGenerator only support aggregate feeds.');
        }

        switch ($format) {
            case self::FORMAT_ATOM:
                $params = [
                    'token'  => $this->getAggregateToken($user, $renew)->getValue(),
                    'format' => 'atom'
                ];
                if (null !== $page) {
                    $params['page'] = $page;
                }

                return new FeedLink(
                    $this->generator->generate('feed_user_aggregated', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $aggregate->getTitle(), 'Atom'),
                    'application/atom+xml'
                );
            case self::FORMAT_RSS:
                $params = [
                    'token'  => $this->getAggregateToken($user, $renew)->getValue(),
                    'format' => 'rss'
                ];
                if (null !== $page) {
                    $params['page'] = $page;
                }

                return new FeedLink(
                    $this->generator->generate('feed_user_aggregated', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $aggregate->getTitle(), 'RSS'),
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
        return $feed instanceof Aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public function generatePublic(FeedInterface $aggregate, $format, $page = null)
    {
        if (!$this->supports($aggregate)) {
            throw new InvalidArgumentException('AggregateLinkGenerator only support aggregate feeds.');
        }

        switch ($format) {
            case self::FORMAT_ATOM:
                $params = ['format' => 'atom'];
                if (null !== $page) {
                    $params['page'] = $page;
                }

                return new FeedLink(
                    $this->generator->generate('feed_public_aggregated', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $aggregate->getTitle(), 'Atom'),
                    'application/atom+xml'
                );
            case self::FORMAT_RSS:
                $params = ['format' => 'rss'];
                if (null !== $page) {
                    $params['page'] = $page;
                }

                return new FeedLink(
                    $this->generator->generate('feed_public_aggregated', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $aggregate->getTitle(), 'RSS'),
                    'application/rss+xml'
                );
            default:
                throw new InvalidArgumentException(sprintf('Format %s is not recognized.', $format));
        }
    }

    private function getAggregateToken(\User_Adapter $user, $renew = false)
    {
        $token = $this->em
            ->getRepository('Alchemy\Phrasea\Model\Entities\AggregateToken')
            ->findOneBy(['usrId' => $user->get_id()]);

        if (null === $token || true === $renew) {
            if (null === $token) {
                $token = new AggregateToken();
                $token->setUsrId($user->get_id());
            }

            $token->setValue($this->random->generatePassword(12, \random::LETTERS_AND_NUMBERS));
            $this->em->persist($token);
            $this->em->flush();
        }

        return $token;
    }
}
