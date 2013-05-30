<?php

namespace Alchemy\Phrasea\Feed;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Feed\Aggregate;
use Entities\AggregateToken;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Generator\UrlGenerator;

class AggregateLinkGenerator
{
    const FORMAT_ATOM = 'atom';
    const FORMAT_RSS  = 'rss';

    private $em;
    private $generator;
    private $random;

    public function __construct(UrlGenerator $generator, EntityManager $em, \random $random)
    {
        $this->generator = $generator;
        $this->em = $em;
        $this->random = $random;
    }

    public function generate(Aggregate $aggregate, \User_Adapter $user, $format, $page = null, $renew = false)
    {
        switch ($format) {
            case self::FORMAT_ATOM:
                return new FeedLink(
                    $this->generator->generate('feed_user_aggregated', array(
                        'token'  => $this->getAggregateToken($user, $renew)->getValue(),
                        'format' => 'atom',
                    ), UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $aggregate->getTitle(), 'Atom'),
                    'application/atom+xml'
                );
                break;
            case self::FORMAT_RSS:
                return new FeedLink(
                    $this->generator->generate('feed_user_aggregated', array(
                        'token'  => $this->getAggregateToken($user, $renew)->getValue(),
                        'format' => 'rss',
                    ), UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $aggregate->getTitle(), 'RSS'),
                    'application/rss+xml'
                );
                break;
            default:
                throw new InvalidArgumentException(sprintf('Format %s is not recognized.', $format));
        }
    }

    private function getAggregateToken(\User_Adapter $user, $renew = false)
    {
        $token = $this->em
            ->getRepository('Entities\AggregateToken')
            ->findByUser($user);

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
