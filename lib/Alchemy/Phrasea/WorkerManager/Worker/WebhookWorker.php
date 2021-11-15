<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Core\Version;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Entities\WebhookEventDelivery;
use Alchemy\Phrasea\Model\Entities\WebhookEventPayload;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\Utilities\NetworkProxiesConfiguration;
use Alchemy\Phrasea\Webhook\Processor\ProcessorInterface;
use Alchemy\Phrasea\WorkerManager\Event\WebhookDeliverFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Http\Message\RequestInterface;


class WebhookWorker implements WorkerInterface
{
    use DispatcherAware;

    private $app;

    /** @var MessagePublisher  $messagePublisher */
    private $messagePublisher;

    /** @var  WorkerRunningJobRepository $repoWorkerJob */
    private $repoWorkerJob;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->messagePublisher = $app['alchemy_worker.message.publisher'];
    }

    /**
     * @param array $payload
     * @return mixed|void
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function process(array $payload)
    {
        if (isset($payload['id'])) {
            $this->repoWorkerJob = $this->getWorkerRunningJobRepository();
            $em = $this->repoWorkerJob->getEntityManager();
            $em->beginTransaction();
            $date = new \DateTime();

            $message = [
                'message_type'  => MessagePublisher::WEBHOOK_TYPE,
                'payload'       => $payload
            ];

            try {
                $workerRunningJob = new WorkerRunningJob();
                $workerRunningJob
                    ->setWork(MessagePublisher::WEBHOOK_TYPE)
                    ->setWorkOn('WebhookEventId: '. $payload['id'])
                    ->setPayload($message)
                    ->setPublished($date->setTimestamp($payload['published']))
                    ->setStatus(WorkerRunningJob::RUNNING)
                ;

                $em->persist($workerRunningJob);

                $em->flush();

                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }

            $webhookEventId = $payload['id'];
            $app = $this->app;

            $version = new Version();

            $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);

            $clientOptions = [
                'timeout' => $this->app['conf']->get(['workers', 'webhook', 'timeout'], 30), // default time out 30 sec if not set
                'connect_timeout' => 50, // should be less than default rabbit timeout 60 to avoid to block Q
                'headers' => [
                    'User-Agent' => sprintf('Phraseanet/%s (%s)', $version->getNumber(), $version->getName())
                ]
            ];

            // use proxy if http-proxy defined in configuration.yml
            // otherwise no
            $httpClient = $proxyConfig->getClientWithOptions($clientOptions);

            $thirdPartyApplications = $this->app['repo.api-applications']->findWithDefinedWebhookCallback();

            /** @var WebhookEvent|null $webhookevent */
            $webhookevent = $this->app['repo.webhook-event']->find($webhookEventId);

            if ($webhookevent !== null) {
                $app['manipulator.webhook-event']->processed($webhookevent);

                $this->messagePublisher->pushLog(sprintf('Processing event "%s" with id %d', $webhookevent->getName(), $webhookevent->getId()));
                // send requests
                $this->deliverEvent($httpClient, $thirdPartyApplications, $webhookevent, $payload);
            }

            if ($workerRunningJob != null) {
                $workerRunningJob
                    ->setStatus(WorkerRunningJob::FINISHED)
                    ->setFinished(new \DateTime('now'))
                ;

                $em->persist($workerRunningJob);

                $em->flush();
            }
        }
    }

    private function deliverEvent(Client $httpClient, array $thirdPartyApplications, WebhookEvent $webhookevent, $payload)
    {
        if (count($thirdPartyApplications) === 0) {
            $workerMessage = 'No applications defined to listen for webhook events';
            $this->messagePublisher->pushLog($workerMessage);

            // count = 0  mean do not retry because no api application defined
            $this->dispatch(WorkerEvents::WEBHOOK_DELIVER_FAILURE, new WebhookDeliverFailureEvent(
                $webhookevent->getId(),
                $workerMessage,
                0)
            );

            return;
        }

        // format event data
        if (!isset($payload['delivery_id'])) {
            $webhookData = $webhookevent->getData();
            $webhookData['time'] = $webhookevent->getCreated();
            $webhookevent->setData($webhookData);
        }

        /** @var ProcessorInterface $eventProcessor */
        $eventProcessor = $this->app['webhook.processor_factory']->get($webhookevent);
        $data = $eventProcessor->process($webhookevent);

        $requests = [];
        /** @var ApiApplication $thirdPartyApplication */
        foreach ($thirdPartyApplications as $thirdPartyApplication) {
            $creator = $thirdPartyApplication->getCreator();

            if ($creator == null) {
                continue;
            }

            $creatorGrantedBaseIds = array_keys($this->app['acl']->get($creator)->get_granted_base());

            $concernedBaseIds = array_intersect($webhookevent->getCollectionBaseIds(), $creatorGrantedBaseIds);

            if (count($webhookevent->getCollectionBaseIds()) != 0 && count($concernedBaseIds) == 0) {
                continue;
            }

            if (isset($payload['delivery_id']) && $payload['delivery_id'] != null) {
                /** @var WebhookEventDelivery $delivery */
                $delivery = $this->app['repo.webhook-delivery']->find($payload['delivery_id']);

                //  only the app url to retry
                if ($delivery->getThirdPartyApplication()->getId() != $thirdPartyApplication->getId()) {
                    continue;
                }
            } else {
                $delivery = $this->app['manipulator.webhook-delivery']->create($thirdPartyApplication, $webhookevent);
            }

            // append delivery id as url anchor
            $uniqueUrl = $this->getUrl($thirdPartyApplication, $delivery);

            // make delivery Id as index
            $requests[$delivery->getId()] = new Request(
                'POST',
                $uniqueUrl,
                ['Content-Type' => 'application/vnd.phraseanet.event+json'],
                json_encode($data)
            );
        }

        $app =  $this->app;
        $webhookEventId = $webhookevent->getId();

        $successCallbackFunction = function (Response $response, $index) use ($app, $payload, $requests) {
            /** @var WebhookEventDelivery $delivery */
            $delivery = $app['repo.webhook-delivery']->find($index);

            $app['manipulator.webhook-delivery']->deliverySuccess($delivery);

            $logType = 'info';
            $logEntry = sprintf('Deliver success event "%d:%s" for app "%s"',
                $delivery->getWebhookEvent()->getId(), $delivery->getWebhookEvent()->getName(),
                $delivery->getThirdPartyApplication()->getName()
            );

            $app['alchemy_worker.message.publisher']->pushLog($logEntry, $logType);

            /** @var Request $req */
            $req = $requests[$index];
            $requestBody = $req instanceof RequestInterface ? $req->getBody() : '';
            $responseBody = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();
            $headers = $this->extractResponseHeaders($response);

            // save the success webhook information in the WebhookEventPayloads table
            $deliveryPayload = new WebhookEventPayload(
                $delivery,
                $requestBody,
                $responseBody,
                $statusCode,
                $headers
            );

            $app['webhook.delivery_payload_repository']->save($deliveryPayload);
        };

        $rejectedCallbackFunction = function (RequestException $reason, $index) use ($app, $webhookEventId, $payload, $requests) {
            /** @var WebhookEventDelivery $delivery */
            $delivery = $app['repo.webhook-delivery']->find($index);
            $app['manipulator.webhook-delivery']->deliveryFailure($delivery);

            $logType = 'error';
            $logEntry = sprintf('Deliver failure event "%d:%s" for app "%s": %s',
                $delivery->getWebhookEvent()->getId(), $delivery->getWebhookEvent()->getName(),
                $delivery->getThirdPartyApplication()->getName(),
                $reason->getMessage()
            );

            $app['alchemy_worker.message.publisher']->pushLog($logEntry, $logType);

            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

            // return in the retry_Q
            $this->dispatch(WorkerEvents::WEBHOOK_DELIVER_FAILURE, new WebhookDeliverFailureEvent(
                $webhookEventId,
                $logEntry,
                $count,
                $index  // the delivery_id
            ));

            // if it's failed after some retry, save the failure information in the WebhookEventPayloads table
            if ($count > $app['alchemy_worker.amqp.connection']->getSetting(MessagePublisher::WEBHOOK_TYPE, AMQPConnection::MAX_RETRY)) {
                /** @var Request $req */
                $req = $requests[$index];
                $requestBody = $req instanceof RequestInterface ? $req->getBody() : '';
                $responseBody = $reason->getMessage();
                $statusCode = -1;
                $headers = '';

                if ($reason->hasResponse()) {
                    $responseBody = $reason->getResponse()->getBody()->getContents();
                    $statusCode = $reason->getResponse()->getStatusCode();
                }

                $deliveryPayload = new WebhookEventPayload(
                    $delivery,
                    $requestBody,
                    $responseBody,
                    $statusCode,
                    $headers
                );

                $app['webhook.delivery_payload_repository']->save($deliveryPayload);
            }
        };

        $pool = new Pool(
            $httpClient,
            $requests,
            [
                'concurrency'   => 10, // sended per 10 request
                'fulfilled'     => $successCallbackFunction,
                'rejected'      => $rejectedCallbackFunction
            ]
        );

        try {
            $pool->promise()->wait();
        } catch (\Exception $e) {
            $this->messagePublisher->pushLog($e->getMessage());
            $this->messagePublisher->publishFailedMessage(
                $payload,
                new AMQPTable(['worker-message' => $e->getMessage()]),
                MessagePublisher::WEBHOOK_TYPE
            );
        }
    }

    private function getUrl(ApiApplication $application, WebhookEventDelivery $delivery)
    {
        return sprintf('%s#%s', $application->getWebhookUrl(), $delivery->getId());
    }

    /**
     * @return WorkerRunningJobRepository
     */
    private function getWorkerRunningJobRepository()
    {
        return $this->app['repo.worker-running-job'];
    }

    private function extractResponseHeaders(Response $response)
    {
        $headerCollection = $response->getHeaders();
        $headers = '';

        foreach ($headerCollection as $name => $values) {
            $headers .= sprintf('%s: %s', $name, implode(',', $values)) . PHP_EOL;
        }

        return trim($headers);
    }
}
