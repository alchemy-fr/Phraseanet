<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Core\Version;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\User;
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
    use ApplicationBoxAware;

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
                ],
                'verify' => $this->app['conf']->get(['workers', 'webhook', 'verify_ssl'], true),
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
                try {
                    $this->deliverEvent($httpClient, $thirdPartyApplications, $webhookevent, $payload);
                } catch (\Exception $e) {
                    if ($workerRunningJob != null) {
                        $workerRunningJob->setInfo('error ' . $e->getMessage());
                        $em->persist($workerRunningJob);
                    }
                }

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

    /**
     * Deliver event to the webhook_url
     * make as public function because used also for unit test
     *
     * @param Client $httpClient
     * @param array $thirdPartyApplications
     * @param WebhookEvent $webhookevent
     * @param $payload
     * @return array|void
     */
    public function deliverEvent(Client $httpClient, array $thirdPartyApplications, WebhookEvent $webhookevent, $payload)
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

        // add common information data before generating each type of data
        if (!isset($payload['delivery_id'])) {
            $webhookData = $webhookevent->getData();

            $webhookData['event_time'] = $webhookevent->getCreated();
            $webhookData['url'] = $this->app['conf']->get(['servername'], '');
            $webhookData['instance_name'] = $this->app['conf']->get(['registry', 'general', 'title'], '');
            // a webhook version is also added when processing data

            $webhookevent->setData($webhookData);
        }

        /** @var ProcessorInterface $eventProcessor */
        $eventProcessor = $this->app['webhook.processor_factory']->get($webhookevent);
        $data = $eventProcessor->process($webhookevent);

        $record = null;
        if (isset($data['data']['databox_id']) && isset($data['data']['record_id'])) {
            $record = $this->findDataboxById($data['data']['databox_id'])->get_record($data['data']['record_id']);
        }

        $requests = [];
        /** @var ApiApplication $thirdPartyApplication */
        foreach ($thirdPartyApplications as $thirdPartyApplication) {

            // skip if webhook is not active
            if (!$thirdPartyApplication->isWebhookActive()) {
                continue;
            }

            $creator = $thirdPartyApplication->getCreator();

            if ($creator == null) {
                continue;
            }

            // check if the third-application listen this event
            // if listenedEvents is empty, third-application can received all webhookevent
            if (!empty($thirdPartyApplication->getListenedEvents()) && !in_array($webhookevent->getName(), $thirdPartyApplication->getListenedEvents())) {
                continue;
            }

            /** @var \ACL $creatorACL */
            $creatorACL = $this->app['acl']->get($creator);
            $creatorGrantedBaseIds = array_keys($creatorACL->get_granted_base());

            $concernedBaseIds = array_intersect($webhookevent->getCollectionBaseIds(), $creatorGrantedBaseIds);

            if (!$this->isCreatorHasRight($creator, $concernedBaseIds, $webhookevent)) {
                continue; // not send, skip
            }

            // continue iteration if api creator has no access to the record
            // it 's include the bas access and the record status bit access
            if ($record !== null && !$creatorACL->has_access_to_record($record)) {
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
            $logEntry = sprintf('Webhook delivery failed "%d:%s" for app "%s": %s',
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

                // deactivate webhook for the app if it's always failed for different event
                if ($app['manipulator.webhook-delivery']->isWebhookDeactivate($delivery->getThirdPartyApplication())) {
                    $message = sprintf('Webhook for app "%s" is deactivated, cannot deliver data in the url "%s" from different events (more than 5 times)',
                        $delivery->getThirdPartyApplication()->getName(),
                        $delivery->getThirdPartyApplication()->getWebhookUrl()
                    );

                    $app['alchemy_worker.message.publisher']->pushLog($message, 'info');
                }
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

        return $requests;
    }

    private function isCreatorHasRight(User $creator, array $baseIds, WebhookEvent $webhookEvent)
    {
        /** @var \ACL $creatorACL */
        $creatorACL = $this->app['acl']->get($creator);

        $checked = false;

        foreach ($baseIds as $baseId) {
            foreach (WebhookEvent::$eventsAccessRight[$webhookEvent->getName()] as $right) {
                // if it's a sub array, only one right is required from the sub array
                if (is_array($right)) {
                    $childChecked = false;
                    foreach ($right as $r) {
                        if (strpos($r, 'bas_') === 0) {
                            // for the sbas right
                            $sbasId = \collection::getByBaseId($this->app, $baseId)->get_databox()->get_sbas_id();
                            if ($creatorACL->has_right_on_sbas($sbasId, $r)) {
                                $childChecked = true;
                            }
                        } else {
                            if (($right === \ACL::ACCESS && $creatorACL->has_access_to_base($baseId)) || $creatorACL->has_right_on_base($baseId, $r)) {
                                $childChecked = true;
                            }
                        }
                    }
                    if (!$childChecked) {
                        return false;
                    }
                } else {
                    if (strpos($right, 'bas_') === 0) {
                        // for the sbas right
                        $sbasId = \collection::getByBaseId($this->app, $baseId)->get_databox()->get_sbas_id();
                        if (!$creatorACL->has_right_on_sbas($sbasId, $right)) {
                            return false;
                        }
                    } else {
                        if ($right === \ACL::ACCESS && !$creatorACL->has_access_to_base($baseId)) {
                            return false;
                        } elseif ($right !== \ACL::ACCESS && !$creatorACL->has_right_on_base($baseId, $right)) {
                            return false;
                        }
                    }
                }
                $checked = true;
            }
        }

        $specificRightOnType = [
            WebhookEvent::USER_TYPE,
            WebhookEvent::FEED_ENTRY_TYPE
        ];

        if ($webhookEvent->getType() === WebhookEvent::FEED_ENTRY_TYPE) {
            $data = $webhookEvent->getData();
            if (isset($data['entry_id'])) {
                /** @var FeedEntry $feedEntry */
                $feedEntry = $this->app['repo.feed-entries']->find($data['entry_id']);
                if ($feedEntry->getFeed()->isPublisher($creator)) {
                    $checked = true;
                }
            }
        }

        // for user created and phantom user deleted
        if (empty($baseIds) && $webhookEvent->getType() === WebhookEvent::USER_TYPE && empty($webhookEvent->getCollectionBaseIds()) ) {
            // check if creatorUser has right canadmin in at least one collection
            if ($creatorACL->has_right(\ACL::CANADMIN)) {
                $checked = true;
            }
        } elseif (empty($baseIds) && empty($webhookEvent->getCollectionBaseIds()) && !in_array($webhookEvent->getType(), $specificRightOnType)) {
            // in this case, for others type, there is not yet a specific rule defined
            // so give the right true
            $checked = true;
        }

        return $checked;
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
