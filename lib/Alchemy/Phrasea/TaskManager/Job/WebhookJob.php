<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Core\Version;
use Alchemy\Phrasea\TaskManager\Editor\DefaultEditor;
use Alchemy\Phrasea\Webhook\EventProcessorFactory;
use Guzzle\Http\Client as GuzzleClient;
use Psr\Log\LoggerInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class WebhookJob extends AbstractJob
{
    private $httpClient;

    public function __construct(
        TranslatorInterface $translator,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null,
        GuzzleClient $httpClient = null
    ) {
        parent::__construct($translator, $dispatcher, $logger);

        $this->httpClient = $httpClient ?: new GuzzleClient();
        $version = new Version();
        $this->httpClient->setUserAgent(sprintf('Phraseanet/%s (%s)', $version->getNumber(), $version->getName()));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans("API Webhook");
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return 'Webhook';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->translator->trans("Notify third party application when an event occurs in Phraseanet");
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new DefaultEditor($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();
        $thirdPartyApplications = $app['repo.api-applications']->findWithDefinedWebhookCallback();

        /** @var EventProcessorFactory $eventFactory */
        $eventFactory = $app['webhook.processor_factory'];

        foreach ($app['repo.webhook-event']->getUnprocessedEventIterator() as $row) {
            $event = $row[0];
            // set event as processed
            $app['manipulator.webhook-event']->processed($event);

            $this->log('info', sprintf('Processing event "%s" with id %d', $event->getName(), $event->getId()));

            // send requests
            $this->deliverEvent($eventFactory, $app, $thirdPartyApplications, $event);
        }
    }
}
