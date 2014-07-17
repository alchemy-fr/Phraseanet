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

    public function __construct(EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null, TranslatorInterface $translator, GuzzleClient $httpClient = null)
    {
        parent::__construct($dispatcher, $logger, $translator);

        $this->httpClient = $httpClient ?: new GuzzleClient();
        $this->httpClient->setUserAgent(sprintf('Phraseanet/%s (%s)', Version::getNumber(), Version::getName()));
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

        $this->httpClient->getEventDispatcher()->addListener('request.error', function (Event $event) {
            // override guzzle default behavior of throwing exceptions
            // when 4xx & 5xx responses are encountered
            $event->stopPropagation();
        }, -254);

        $this->httpClient->addSubscriber(new BackoffPlugin(
            // set max retries
            new TruncatedBackoffStrategy(WebhookEventDelivery::MAX_DELIVERY_TRIES,
                // set callback which logs success or failure
                new CallbackBackoffStrategy(function ($retries, $request, $response, $e) use ($app, $that) {
                    $retry = true;
                    if ($response && (null !== $deliverId = parse_url($request->getUrl(), PHP_URL_FRAGMENT))) {
                        $delivery = $app['repo.webhook-delivery']->find($deliverId);
                        if ($response->isSuccessful()) {
                            $app['manipulator.webhook-delivery']->deliverySuccess($delivery);

                            $that->log('info', sprintf('Deliver success event "%d:%s" for app  "%s"', $delivery->getWebhookEvent()->getId(), $delivery->getWebhookEvent()->getName(),  $delivery->getThirdPartyApplication()->getName()));
                            $retry = false;
                        } else {
                            $app['manipulator.webhook-delivery']->deliveryFailure($delivery);

                            $that->log('error', sprintf('Deliver failure event "%d:%s" for app  "%s"', $delivery->getWebhookEvent()->getId(), $delivery->getWebhookEvent()->getName(),  $delivery->getThirdPartyApplication()->getName()));
                        }

                        return $retry;
                    }},
                    true,
                    new CurlBackoffStrategy()
                )
            )
        ));

        foreach ($app['repo.webhook-event']->findUnprocessedEvents() as $event) {
            // set event as processed
            $app['manipulator.webhook-event']->processed($event);

            $this->log('info', sprintf('Processing event "%s" with id %d', $event->getName(), $event->getId()));

            // send requests
            $this->deliverEvent($app, $thirdPartyApplications, $event);
        }
    }

    private function deliverEvent(Application $app, array $thirdPartyApplications, WebhookEvent $event)
    {
        if (count($thirdPartyApplications) === 0) {
            $this->log('info', sprintf('No applications defined to listen for webhook events'));

            return;
        }

        // format event data
        $eventFactory = new EventProcessorFactory($app);
        $eventProcessor = $eventFactory->get($event);
        $data = $eventProcessor->process();

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
