<?php

/*
 * This file is part of phrasea-4.1.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Webhook;

use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Entities\WebhookEventDelivery;
use Alchemy\Phrasea\Model\Entities\WebhookEventPayload;
use Alchemy\Phrasea\Model\Manipulator\WebhookEventDeliveryManipulator;
use Alchemy\Phrasea\Model\Manipulator\WebhookEventManipulator;
use Alchemy\Phrasea\Model\Repositories\ApiApplicationRepository;
use Alchemy\Phrasea\Model\Repositories\WebhookEventDeliveryRepository;
use Alchemy\Phrasea\Model\Repositories\WebhookEventPayloadRepository;
use Alchemy\Phrasea\Model\Repositories\WebhookEventRepository;
use Guzzle\Common\Event;
use Guzzle\Http\Client;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Guzzle\Plugin\Backoff\CallbackBackoffStrategy;
use Guzzle\Plugin\Backoff\CurlBackoffStrategy;
use Guzzle\Plugin\Backoff\TruncatedBackoffStrategy;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class WebhookInvoker invokes remote endpoints with webhook event data
 * @package Alchemy\Phrasea\Webhook
 */
class WebhookInvoker implements LoggerAwareInterface
{

    /**
     * @var ApiApplicationRepository
     */
    private $applicationRepository;

    /**
     * @var WebhookEventDeliveryManipulator
     */
    private $eventDeliveryManipulator;

    /**
     * @var WebhookEventDeliveryRepository
     */
    private $eventDeliveryRepository;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventProcessorFactory
     */
    private $processorFactory;

    /**
     * @var WebhookEventRepository
     */
    private $eventRepository;

    /**
     * @var WebhookEventManipulator
     */
    private $eventManipulator;

    /**
     * @var WebhookEventPayloadRepository
     */
    private $eventPayloadRepository;

    /**
     * @param ApiApplicationRepository $applicationRepository
     * @param EventProcessorFactory $processorFactory
     * @param WebhookEventRepository $eventRepository
     * @param WebhookEventManipulator $eventManipulator
     * @param WebhookEventDeliveryRepository $eventDeliveryRepository
     * @param WebhookEventDeliveryManipulator $eventDeliveryManipulator
     * @param WebhookEventPayloadRepository $eventPayloadRepository
     * @param Client $client
     *
     * @todo Extract classes to reduce number of required dependencies
     */
    public function __construct(
        ApiApplicationRepository $applicationRepository,
        EventProcessorFactory $processorFactory,
        WebhookEventRepository $eventRepository,
        WebhookEventManipulator $eventManipulator,
        WebhookEventDeliveryRepository $eventDeliveryRepository,
        WebhookEventDeliveryManipulator $eventDeliveryManipulator,
        WebhookEventPayloadRepository $eventPayloadRepository,
        Client $client = null
    ) {
        $this->applicationRepository = $applicationRepository;
        $this->processorFactory = $processorFactory;
        $this->eventRepository = $eventRepository;
        $this->eventManipulator = $eventManipulator;
        $this->eventDeliveryManipulator = $eventDeliveryManipulator;
        $this->eventDeliveryRepository = $eventDeliveryRepository;
        $this->eventPayloadRepository = $eventPayloadRepository;

        $this->client = $client ?: new Client();
        $this->logger = new NullLogger();

        $this->configureClient();
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function invoke(WebhookEvent $event)
    {
        $this->doInvoke($event, $this->applicationRepository->findWithDefinedWebhookCallback());
    }

    public function invokeUnprocessedEvents()
    {
        $targetApplications = $this->applicationRepository->findWithDefinedWebhookCallback();

        foreach ($this->eventRepository->getUnprocessedEventIterator() as $row) {
            /** @var WebhookEvent $event */
            $event = $row[0];

            $this->doInvoke($event, $targetApplications);
        }
    }

    /**
     * @param WebhookEvent $event
     * @param ApiApplication[] $targets
     */
    private function doInvoke(WebhookEvent $event, array $targets)
    {
        $this->eventManipulator->processed($event);
        $this->logger->info(sprintf('Processing event "%s" with id %d', $event->getName(), $event->getId()));

        // send requests
        $this->doHttpDelivery($event, $targets);
    }

    private function configureClient()
    {
        $this->client->getEventDispatcher()->addListener('request.error', function (Event $event) {
            // Override guzzle default behavior of throwing exceptions
            // when 4xx & 5xx responses are encountered
            $event->stopPropagation();
        }, -254);

        // Set callback which logs success or failure
        $subscriber = new CallbackBackoffStrategy(function ($retries, Request $request, $response, $e) {
            $retry = true;
            if ($response && (null !== $deliverId = parse_url($request->getUrl(), PHP_URL_FRAGMENT))) {
                $delivery = $this->eventDeliveryRepository->find($deliverId);

                $logContext = ['host' => $request->getHost()];

                if ($response->isSuccessful()) {
                    $this->eventDeliveryManipulator->deliverySuccess($delivery);

                    $logType = 'info';
                    $logEntry = sprintf('Deliver success event "%d:%s" for app "%s"',
                        $delivery->getWebhookEvent()->getId(), $delivery->getWebhookEvent()->getName(),
                        $delivery->getThirdPartyApplication()->getName()
                    );

                    $retry = false;
                } else {
                    $this->eventDeliveryManipulator->deliveryFailure($delivery);

                    $logType = 'error';
                    $logEntry = sprintf('Webhook delivery failed "%d:%s" for app "%s"',
                        $delivery->getWebhookEvent()->getId(), $delivery->getWebhookEvent()->getName(),
                        $delivery->getThirdPartyApplication()->getName()
                    );
                }

                $this->logger->log($logType, $logEntry, $logContext);

                return $retry;
            }
        }, true, new CurlBackoffStrategy());

        // Set max retries
        $subscriber = new TruncatedBackoffStrategy(WebhookEventDelivery::MAX_DELIVERY_TRIES, $subscriber);
        $subscriber = new BackoffPlugin($subscriber);

        $this->client->addSubscriber($subscriber);
    }

    /**
     * @param WebhookEvent $event
     * @param ApiApplication[] $targets
     */
    private function doHttpDelivery(
        WebhookEvent $event,
        array $targets
    ) {
        if (count($targets) === 0) {
            $this->logger->info(sprintf('No applications defined to listen for webhook events'));

            return;
        }

        // Format event data
        $eventProcessor = $this->processorFactory->getProcessor($event);
        $data = $eventProcessor->process($event);

        foreach ($targets as $thirdPartyApplication) {
            $delivery = $this->eventDeliveryManipulator->create($thirdPartyApplication, $event);
            // Append delivery id as url anchor
            $uniqueUrl = $this->buildUrl($thirdPartyApplication, $delivery);

            // Create http request with data as request body
            $request = $this->client->createRequest('POST', $uniqueUrl, [
                'Content-Type' => 'application/vnd.phraseanet.event+json'
            ], json_encode($data));

            $requestBody = $request instanceof EntityEnclosingRequestInterface ? $request->getBody() : '';

            try {
                $response = $request->send();

                $responseBody = $response->getBody(true);
                $statusCode = $response->getStatusCode();
                $headers = $this->extractResponseHeaders($response);
            }
            catch (\Exception $exception) {
                $responseBody = $exception->getMessage();
                $statusCode = -1;
                $headers = '';
            }

            $deliveryPayload = new WebhookEventPayload(
                $delivery,
                $requestBody,
                $responseBody,
                $statusCode,
                $headers
            );

            $this->eventPayloadRepository->save($deliveryPayload);
        }
    }

    /**
     * @param ApiApplication $application
     * @param WebhookEventDelivery $delivery
     * @return string
     */
    private function buildUrl(ApiApplication $application, WebhookEventDelivery $delivery)
    {
        return sprintf('%s#%s', $application->getWebhookUrl(), $delivery->getId());
    }

    private function extractResponseHeaders(Response $response)
    {
        $headerCollection = $response->getHeaders()->toArray();
        $headers = '';

        foreach ($headerCollection as $name => $value) {
            $headers .= sprintf('%s: %s', $name, $value) . PHP_EOL;
        }

        return trim($headers);
    }
}
