<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailInfoNewPublication extends AbstractMailWithLink
{
    private $author;
    private $title;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        if (!$this->title) {
            throw new LogicException('You must set an title before calling getMessage');
        }

        return sprintf(_('Nouvelle publication : %s'), $this->title);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->author) {
            throw new LogicException('You must set an author before calling getMessage');
        }
        if (!$this->title) {
            throw new LogicException('You must set an title before calling getMessage');
        }

        return sprintf('%s vient de publier %s', $this->author, $this->title);
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return sprintf(_('View on %s'), $this->getPhraseanetTitle());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
