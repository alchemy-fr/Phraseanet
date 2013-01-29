<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Notification\Mail\MailTest as Mail;
use Alchemy\Phrasea\Notification\Receiver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class MailTest extends Command
{
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->addArgument('email', InputArgument::REQUIRED, 'An email where to send the test email');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->container['notification.deliverer']->deliver(
            Mail::create($this->container, new Receiver(null, $input->getArgument('email')))
        );
    }
}
