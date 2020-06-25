<?php

namespace Alchemy\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\Core\Event\Record\RecordAutoSubtitleEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SubtitlingSubscriber implements EventSubscriberInterface
{
    const GINGER_BASE_URL = "https://test.api.ginger.studio/recognition/speech";
    const GINGER_TOKEN    = "39c6011d-3bbe-4f39-95d0-a327d320ded4";
    const GINGER_TRANSCRIPT_FORMAT = "text/vtt";

    /**
     * @var callable
     */
    private $appboxLocator;

    private $logger;

    public function __construct(callable $appboxLocator, LoggerInterface $logger)
    {
        $this->appboxLocator    = $appboxLocator;
        $this->logger           = $logger;
    }

    public function onRecordAutoSubtitle(RecordAutoSubtitleEvent $event)
    {
        $record = $this->getApplicationBox()->get_databox($event->getRecord()->getDataboxId())->get_record($event->getRecord()->getRecordId());

        if ($record->has_preview() && ($previewLink = $record->get_preview()->get_permalink()) !== null && $event->getMetaStructId()) {
            switch ($event->getLanguageSource()) {
                case 'En':
                    $language = 'en-GB';
                    break;
                case 'De':
                    $language = 'de-DE';
                    break;
                case 'Fr':
                default:
                    $language = 'fr-FR';
                    break;
            }

            $permalinkUrl = $previewLink->get_url()->__toString();

            $gingerClient = new Client();

            try {
                $response = $gingerClient->post(self::GINGER_BASE_URL.'/media/', [
                    'headers' => [
                        'Authorization' => 'token '.self::GINGER_TOKEN
                    ],
                    'json' => [
                        'url'       => $permalinkUrl,
                        'language'  => $language
                    ]
                ]);
            } catch(\Exception $e) {
                $this->logger->error($e->getMessage());

                return 0;
            }

            if ($response->getStatusCode() !== 201) {
                $this->logger->error("response status : ". $response->getStatusCode());

                return 0;
            }

            $responseMediaBody = $response->getBody()->getContents();
            $responseMediaBody = json_decode($responseMediaBody,true);

            $checkStatus = true;
            do {
                // first wait 5 second before check subtitling status
                sleep(5);

                try {
                    $response = $gingerClient->get(self::GINGER_BASE_URL.'/task/'.$responseMediaBody['task_id'].'/', [
                        'headers' => [
                            'Authorization' => 'token '.self::GINGER_TOKEN
                        ]
                    ]);
                } catch (\Exception $e) {
                    $checkStatus = false;
                }

                if ($response->getStatusCode() !== 200) {
                    $checkStatus = false;
                    break;
                }

                $responseTaskBody = $response->getBody()->getContents();
                $responseTaskBody = json_decode($responseTaskBody,true);

            } while($responseTaskBody['status'] != 'SUCCESS');

            if (!$checkStatus) {
                $this->logger->error("can't check status");

                return 0;
            }

            try {
                $response = $gingerClient->get(self::GINGER_BASE_URL.'/media/'.$responseMediaBody['media']['uuid'].'/', [
                    'headers' => [
                        'Authorization' => 'token '.self::GINGER_TOKEN,
                        'ACCEPT'        => self::GINGER_TRANSCRIPT_FORMAT
                    ],
                    'query' => [
                        'language'  => $language
                    ]
                ]);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());

                return 0;
            }

            if ($response->getStatusCode() !== 201) {
                $this->logger->error("response status : ". $response->getStatusCode());

                return 0;
            }

            $transcriptContent = $response->getBody()->getContents();

            $metadatas[0] = [
                'meta_struct_id' => (int)$event->getMetaStructId(),
                'meta_id'        => '',
                'value'          => $transcriptContent
            ];

            try {
                $record->set_metadatas($metadatas);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());

                return 0;
            }

            $this->logger->error("Auto subtitle SUCCESS");
        }

        return 0;
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::RECORD_AUTO_SUBTITLE  => 'onRecordAutoSubtitle',
        ];
    }

    /**
     * @return \appbox
     */
    private function getApplicationBox()
    {
        $callable = $this->appboxLocator;

        return $callable();
    }
}
