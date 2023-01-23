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
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Entities\WebhookEventDelivery;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\TaskManager\Editor\DefaultEditor;
use Alchemy\Phrasea\Webhook\EventProcessorFactory;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Batch\BatchBuilder;
use Guzzle\Http\Message\Request;
use Silex\Application;
use Guzzle\Common\Event;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Guzzle\Plugin\Backoff\TruncatedBackoffStrategy;
use Guzzle\Plugin\Backoff\CallbackBackoffStrategy;
use Guzzle\Plugin\Backoff\CurlBackoffStrategy;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class WebhookJob extends AbstractJob
{
    private $httpClient;

    private $firstRun = true;

    public function __construct(
        TranslatorInterface $translator,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null,
        GuzzleClient $httpClient = null
    )
    {
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
        $that = $this;

        if ($this->firstRun) {
            $this->httpClient->getEventDispatcher()->addListener('request.error', function (Event $event) {
                // override guzzle default behavior of throwing exceptions
                // when 4xx & 5xx responses are encountered
                $event->stopPropagation();
            }, -254);

            // Set callback which logs success or failure
            $subscriber = new CallbackBackoffStrategy(function ($retries, Request $request, $response, $e) use ($app, $that) {
                $retry = true;
                if ($response && (null !== $deliverId = parse_url($request->getUrl(), PHP_URL_FRAGMENT))) {
                    $delivery = $app['repo.webhook-delivery']->find($deliverId);

                    $logContext = [ 'host' => $request->getHost() ];

                    if ($response->isSuccessful()) {
                        $app['manipulator.webhook-delivery']->deliverySuccess($delivery);

                        $logType = 'info';
                        $logEntry = sprintf('Deliver success event "%d:%s" for app "%s"',
                            $delivery->getWebhookEvent()->getId(), $delivery->getWebhookEvent()->getName(),
                            $delivery->getThirdPartyApplication()->getName()
                        );

                        $retry = false;
                    } else {
                        $app['manipulator.webhook-delivery']->deliveryFailure($delivery);

                        $logType = 'error';
                        $logEntry = sprintf('Deliver failure event "%d:%s" for app "%s"',
                            $delivery->getWebhookEvent()->getId(), $delivery->getWebhookEvent()->getName(),
                            $delivery->getThirdPartyApplication()->getName()
                        );
                    }

                    $that->log($logType, $logEntry, $logContext);

                    return $retry;
                }
            }, true, new CurlBackoffStrategy());

            // set max retries
            $subscriber = new TruncatedBackoffStrategy(WebhookEventDelivery::MAX_DELIVERY_TRIES, $subscriber);
            $subscriber = new BackoffPlugin($subscriber);

            $this->httpClient->addSubscriber($subscriber);

            $this->firstRun = false;
        }

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

    private function deliverEvent(EventProcessorFactory $eventFactory, Application $app, array $thirdPartyApplications, WebhookEvent $event)
    {
        if (count($thirdPartyApplications) === 0) {
            $this->log('info', sprintf('No applications defined to listen for webhook events'));

            return;
        }

        // format event data
        $eventProcessor = $eventFactory->get($event);
        $data = $eventProcessor->process($event);

        // batch requests
        $batch = BatchBuilder::factory()
            ->transferRequests(10)
            ->build();

        foreach ($thirdPartyApplications as $thirdPartyApplication) {
            $delivery = $app['manipulator.webhook-delivery']->create($thirdPartyApplication, $event);

            // append delivery id as url anchor
            $uniqueUrl = $this->getUrl($thirdPartyApplication, $delivery);

            // create http request with data as request body
            $batch->add($this->httpClient->createRequest('POST', $uniqueUrl, [
                'Content-Type' => 'application/vnd.phraseanet.event+json'
            ], json_encode($data)));
        }

        $batch->flush();
    }

    private function getUrl(ApiApplication $application, WebhookEventDelivery $delivery)
    {
        return sprintf('%s#%s', $application->getWebhookUrl(), $delivery->getId());
    }
}