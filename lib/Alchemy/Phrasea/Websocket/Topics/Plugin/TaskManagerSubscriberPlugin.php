<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Websocket\Topics\Plugin;

use Alchemy\Phrasea\Websocket\Topics\TopicsManager;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\ZMQ\Context;

class TaskManagerSubscriberPlugin implements PluginInterface
{
    private $logger;
    private $pull;

    public function __construct($options, LoopInterface $loop, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $context = new Context($loop);

        $this->pull = $context->getSocket(\ZMQ::SOCKET_SUB);
        $this->pull->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, "");
        $this->pull->connect(sprintf('%s://%s:%s', $options['protocol'], $options['host'], $options['port']));

        $this->pull->on('error', function ($e) use ($logger) {
            $logger->error('TaskManager Subscriber received an error.', ['exception' => $e]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function attach(TopicsManager $manager)
    {
        $this->pull->on('message', function ($msg) use ($manager) {
            $data = @json_decode($msg, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error(sprintf('[WS] Received invalid message %s : invalid json', $msg));

                return;
            }

            if (!isset($data['topic'])) {
                $this->logger->error(sprintf('[WS] Received invalid message %s : no topic', $msg));

                return;
            }

            $this->logger->debug(sprintf('[WS] Received message %s', $msg));

            $manager->broadcast($data['topic'], json_encode($msg));
        });
    }
}
