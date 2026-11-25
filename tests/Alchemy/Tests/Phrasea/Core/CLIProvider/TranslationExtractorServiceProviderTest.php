<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

/**
 * @covers Alchemy\Phrasea\Core\CLIProvider\TranslationExtractorServiceProvider
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class TranslationExtractorServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\CLIProvider\TranslationExtractorServiceProvider',
                'translation-extractor.logger',
                'Monolog\Logger'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\TranslationExtractorServiceProvider',
                'translation-extractor.doc-parser',
                'Doctrine\Common\Annotations\DocParser'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\TranslationExtractorServiceProvider',
                'translation-extractor.file-extractor',
                'JMS\TranslationBundle\Translation\Extractor\FileExtractor'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\TranslationExtractorServiceProvider',
                'translation-extractor.extractor-manager',
                'JMS\TranslationBundle\Translation\ExtractorManager'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\TranslationExtractorServiceProvider',
                'translation-extractor.writer',
                'JMS\TranslationBundle\Translation\FileWriter'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\TranslationExtractorServiceProvider',
                'translation-extractor.loader-manager',
                'JMS\TranslationBundle\Translation\LoaderManager'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\TranslationExtractorServiceProvider',
                'translation-extractor.updater',
                'JMS\TranslationBundle\Translation\Updater'
            ],
        ];
    }
}
