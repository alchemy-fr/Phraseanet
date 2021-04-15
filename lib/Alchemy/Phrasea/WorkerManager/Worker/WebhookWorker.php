<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Core\Version;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Entities\WebhookEventDelivery;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\Webhook\Processor\ProcessorInterface;
use Alchemy\Phrasea\WorkerManager\Event\WebhookDeliverFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Guzzle\Batch\BatchBuilder;
use Guzzle\Common\Event;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Message\Request;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Guzzle\Plugin\Backoff\CallbackBackoffStrategy;
use Guzzle\Plugin\Backoff\CurlBackoffStrategy;
use Guzzle\Plugin\Backoff\TruncatedBackoffStrategy;
use PhpAmqpLib\Wire\AMQPTable;

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

            $httpClient = new GuzzleClient();
            $version = new Version();
            $httpClient->setUserAgent(sprintf('Phraseanet/%s (%s)', $version->getNumber(), $version->getName()));

            $httpClient->getEventDispatcher()->addListener('request.error', function (Event $event) {
                // override guzzle default behavior of throwing exceptions
                // when 4xx & 5xx responses are encountered
                $event->stopPropagation();
            }, -254);

            // Set callback which logs success or failure
            $subscriber = new CallbackBackoffStrategy(function ($retries, Request $request, $response, $e) use ($app, $webhookEventId, $payload) {
                if ($response && (null !== $deliverId = parse_url($request->getUrl(), PHP_URL_FRAGMENT))) {
                    /** @var WebhookEventDelivery $delivery */
                    $delivery = $app['repo.webhook-delivery']->find($deliverId);

                    $logContext = [ 'host' => $request->getHost() ];

                    if ($response->isSuccessful()) {
                        $app['manipulator.webhook-delivery']->deliverySuccess($delivery);

                        $logType = 'info';
                        $logEntry = sprintf('Deliver success event "%d:%s" for app "%s"',
                            $delivery->getWebhookEvent()->getId(), $delivery->getWebhookEvent()->getName(),
                            $delivery->getThirdPartyApplication()->getName()
                        );

                    } else {
                        $app['manipulator.webhook-delivery']->deliveryFailure($delivery);

                        $logType = 'error';
                        $logEntry = sprintf('Deliver failure event "%d:%s" for app "%s"',
                            $delivery->getWebhookEvent()->getId(), $delivery->getWebhookEvent()->getName(),
                            $delivery->getThirdPartyApplication()->getName()
                        );

                        $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

                        $this->dispatch(WorkerEvents::WEBHOOK_DELIVER_FAILURE, new WebhookDeliverFailureEvent(
                            $webhookEventId,
                            $logEntry,
                            $count,
                            $deliverId
                        ));
                    }

                    $app['alchemy_worker.message.publisher']->pushLog($logEntry, $logType, $logContext);
                }
            }, true, new CurlBackoffStrategy());

            // set max retries
            $subscriber = new TruncatedBackoffStrategy(1, $subscriber);
            $subscriber = new BackoffPlugin($subscriber);

            $httpClient->addSubscriber($subscriber);


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

    private function deliverEvent(GuzzleClient $httpClient, array $thirdPartyApplications, WebhookEvent $webhookevent, $payload)
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

        // batch requests
        $batch = BatchBuilder::factory()
            ->transferRequests(10)
            ->build();

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

            // create http request with data as request body
            $batch->add($httpClient->createRequest('POST', $uniqueUrl, [
                'Content-Type' => 'application/vnd.phraseanet.event+json'
            ], json_encode($data)));
        }

        try {
            $batch->flush();
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
}
