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
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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
     * @var ClientInterface
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
     * @param ClientInterface|null $client
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
        ?ClientInterface $client = null
    ) {
        $this->applicationRepository = $applicationRepository;
        $this->processorFactory = $processorFactory;
        $this->eventRepository = $eventRepository;
        $this->eventManipulator = $eventManipulator;
        $this->eventDeliveryManipulator = $eventDeliveryManipulator;
        $this->eventDeliveryRepository = $eventDeliveryRepository;
        $this->eventPayloadRepository = $eventPayloadRepository;

        $this->client = $client ?: new Client(['handler' => HandlerStack::create()]);
        $this->logger = new NullLogger();

        $this->configureClient();
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
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
        $handler = $this->client->getConfig('handler');
        if (!$handler instanceof HandlerStack) {
            return;
        }

        // Logs success or failure of each delivery attempt and retries failed ones
        $decider = function ($retries, RequestInterface $request, ?ResponseInterface $response = null, $exception = null) {
            if ($retries >= WebhookEventDelivery::MAX_DELIVERY_TRIES) {
                return false;
            }

            $deliverId = $request->getUri()->getFragment();

            if (null === $response || '' === $deliverId) {
                return $exception instanceof ConnectException;
            }

            $delivery = $this->eventDeliveryRepository->find($deliverId);

            $logContext = ['host' => $request->getUri()->getHost()];

            if ($this->isSuccessful($response)) {
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

                $retry = true;
            }

            $this->logger->log($logType, $logEntry, $logContext);

            return $retry;
        };

        $handler->push(Middleware::retry($decider, function () {
            return 0;
        }), 'webhook_retry');
    }

    private function isSuccessful(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();

        return ($statusCode >= 200 && $statusCode < 300) || $statusCode === 304;
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

            $requestBody = json_encode($data);

            try {
                // 4xx and 5xx responses are recorded as delivery failures, not thrown
                $response = $this->client->request('POST', $uniqueUrl, [
                    RequestOptions::HEADERS => ['Content-Type' => 'application/vnd.phraseanet.event+json'],
                    RequestOptions::BODY => $requestBody,
                    RequestOptions::HTTP_ERRORS => false,
                ]);

                $responseBody = (string) $response->getBody();
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

    private function extractResponseHeaders(ResponseInterface $response)
    {
        $headers = '';

        foreach ($response->getHeaders() as $name => $values) {
            $headers .= sprintf('%s: %s', $name, implode(', ', $values)) . PHP_EOL;
        }

        return trim($headers);
    }
}
