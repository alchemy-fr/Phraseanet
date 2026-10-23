<?php

namespace Alchemy\Phrasea\Utilities;

use Alchemy\Phrasea\Application;
use Symfony\Component\Translation\Formatter\MessageFormatterInterface;
use Symfony\Component\Translation\Translator;

/**
 * Translator following the application locale, with a catalogue cache.
 */
class CachedTranslator extends Translator
{
    protected $app;
    protected $options = [
        'cache_dir' => null,
        'debug'     => false,
    ];

    public function __construct(Application $app, MessageFormatterInterface $formatter, array $options = [])
    {
        $this->app = $app;

        if ($diff = array_diff(array_keys($options), array_keys($this->options))) {
            throw new \InvalidArgumentException(sprintf('The Translator does not support the following options: \'%s\'.', implode('\', \'', $diff)));
        }

        $this->options = array_merge($this->options, $options);

        $cacheDir = $this->options['cache_dir'];
        if (is_callable($cacheDir)) {
            $cacheDir = call_user_func($cacheDir, $this->app);
        }

        parent::__construct($app['locale'], $formatter, $cacheDir, $this->options['debug']);
    }

    public function getLocale(): string
    {
        return $this->app['locale'];
    }

    public function setLocale(string $locale): void
    {
        $this->app['locale'] = $locale;

        parent::setLocale($locale);
    }
}
