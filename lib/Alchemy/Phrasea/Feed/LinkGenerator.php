<?php

namespace Alchemy\Phrasea\Feed;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Doctrine\ORM\EntityManager;
use Entities\Feed;
use Entities\FeedToken;
use Symfony\Component\Routing\Generator\UrlGenerator;

class LinkGenerator
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

    public function generate(Feed $feed, \User_Adapter $user, $format, $page = null, $renew = false)
    {
        switch ($format) {
            case self::FORMAT_ATOM:
                $params = array('token'  => $this->getFeedToken($feed, $user, $renew)->getValue(),
                                'id'     => $feed->getId(),
                                'format' => 'atom');
                if (null !== $page) {
                    $params['page'] = $page;
                }
                return new FeedLink(
                    $this->generator->generate('feed_user', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $feed->getTitle(), 'Atom'),
                    'application/atom+xml'
                );
                break;
            case self::FORMAT_RSS:
                $params = array('token'  => $this->getFeedToken($feed, $user, $renew)->getValue(),
                                'id'     => $feed->getId(),
                                'format' => 'rss');
                if (null !== $page) {
                    $params['page'] = $page;
                }
                return new FeedLink(
                    $this->generator->generate('feed_user', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $feed->getTitle(), 'RSS'),
                    'application/rss+xml'
                );
                break;
            default:
                throw new InvalidArgumentException(sprintf('Format %s is not recognized.', $format));
        }
    }

    public function generatePublic(Feed $feed, $format, $page = null)
    {
        switch ($format) {
            case self::FORMAT_ATOM:
                $params = array('id'     => $feed->getId(),
                                'format' => 'atom');
                if (null !== $page) {
                    $params['page'] = $page;
                }
                return new FeedLink(
                    $this->generator->generate('feed_public', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $feed->getTitle(), 'Atom'),
                    'application/atom+xml'
                );
                break;
            case self::FORMAT_RSS:
                $params = array('id'     => $feed->getId(),
                                'format' => 'rss');
                if (null !== $page) {
                    $params['page'] = $page;
                }
                return new FeedLink(
                    $this->generator->generate('feed_public', $params, UrlGenerator::ABSOLUTE_URL),
                    sprintf('%s - %s', $feed->getTitle(), 'RSS'),
                    'application/rss+xml'
                );
                break;
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
