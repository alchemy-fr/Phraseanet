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
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Silex\Application;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class WebhookJob extends AbstractJob
{
    private $httpClient;

    private $userAgent;

    private $firstRun = true;

    public function __construct(
        \Symfony\Contracts\Translation\TranslatorInterface $translator,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null,
        ?ClientInterface $httpClient = null
    )
    {
        parent::__construct($translator, $dispatcher, $logger);

        $this->httpClient = $httpClient ?: new GuzzleClient(['handler' => HandlerStack::create()]);
        $version = new Version();
        $this->userAgent = sprintf('Phraseanet/%s (%s)', $version->getNumber(), $version->getName());
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
            $handler = $this->httpClient->getConfig('handler');

            if ($handler instanceof HandlerStack) {
                // logs success or failure of each delivery attempt and retries failed ones
                $decider = function ($retries, RequestInterface $request, ?ResponseInterface $response = null, $exception = null) use ($app, $that) {
                    if ($retries >= WebhookEventDelivery::MAX_DELIVERY_TRIES) {
                        return false;
                    }

                    $deliverId = $request->getUri()->getFragment();

                    if (null === $response || '' === $deliverId) {
                        return $exception instanceof ConnectException;
                    }

                    $delivery = $app['repo.webhook-delivery']->find($deliverId);

                    $logContext = [ 'host' => $request->getUri()->getHost() ];

                    $statusCode = $response->getStatusCode();
                    if (($statusCode >= 200 && $statusCode < 300) || $statusCode === 304) {
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

                        $retry = true;
                    }

                    $that->log($logType, $logEntry, $logContext);

                    return $retry;
                };

                $handler->push(Middleware::retry($decider, function () {
                    return 0;
                }), 'webhook_retry');
            }

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

        $requests = [];

        foreach ($thirdPartyApplications as $thirdPartyApplication) {
            $delivery = $app['manipulator.webhook-delivery']->create($thirdPartyApplication, $event);

            // append delivery id as url anchor
            $uniqueUrl = $this->getUrl($thirdPartyApplication, $delivery);

            // create http request with data as request body
            $requests[] = new Request('POST', $uniqueUrl, [
                'Content-Type' => 'application/vnd.phraseanet.event+json',
                'User-Agent' => $this->userAgent,
            ], json_encode($data));
        }

        // send requests by batches of 10; 4xx and 5xx responses are handled as delivery failures, not thrown
        $pool = new Pool($this->httpClient, $requests, [
            'concurrency' => 10,
            'options' => [RequestOptions::HTTP_ERRORS => false],
            'rejected' => function ($reason) {
                $this->log('error', sprintf('Webhook delivery failed: %s', $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason));
            },
        ]);

        $pool->promise()->wait();
    }

    private function getUrl(ApiApplication $application, WebhookEventDelivery $delivery)
    {
        return sprintf('%s#%s', $application->getWebhookUrl(), $delivery->getId());
    }
}