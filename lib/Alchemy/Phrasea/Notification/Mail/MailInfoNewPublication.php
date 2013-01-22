<?php

namespace Alchemy\Phrasea\Notification\Mail;

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

    public function subject()
    {
        return sprintf(_('Nouvelle publication : %s'), $this->title);
    }

    public function message()
    {
        return sprintf('%s vient de publier %s', $this->author, $this->title);
    }

    public function buttonText()
    {
        return sprintf(_('View on %s'), $this->app['phraseanet.registry']->get('GV_homeTitle'));
    }

    public function buttonURL()
    {
        return $this->url;
    }
}
