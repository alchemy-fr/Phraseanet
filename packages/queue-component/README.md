# queue-component

[![License][badge-license]][license]
[![Packagist][badge-packagist]][packagist]
[![Travis][badge-travis]][travis]
[![Coverage][badge-coverage]][coverage]
[![Scrutinizer][badge-quality]][quality]
[![Packagist][badge-downloads]][downloads]

alchemy/queue-component is a library providing a minimalist publish/subscribe abstraction over AMQP

## Installation

The only supported installation method is via [Composer](https://getcomposer.org). Run the following command to require 
the package in your project:

```
composer require alchemy/queue-component
```

## Quickstart guide

```php
// Note: the following array contains all available parameters and their default values
// Every configuration key is optional, and its default value used when not defined in parameters
$parameters = [
    'host' => 'localhost',
    'vhost' => '/',
    'port' => 5672,
    'user' => 'guest',
    'password' => 'guest',
    'exchange' => 'alchemy-exchange',
    'dead-letter-exchange' => 'alchemy-dead-exchange',
    'queue' => 'alchemy-queue'
];

$factory = Alchemy\Queue\Amqp\AmqpMessageQueueFactory::create($parameters);

// Publish a message
$factory->getNamedQueue('my-queue')->publish(new Message('message body', 'correlation-id'));

// Consume next message in queue
$handler = new Alchemy\Queue\NullMessageHandler();
$resolver = new Alchemy\Queue\MessageHandlerResolver($handler);
$factory->getNamedQueue('my-queue')->handle($resolver);
```

[badge-license]: https://img.shields.io/packagist/l/alchemy/queue-component.svg?style=flat-square
[badge-packagist]: https://img.shields.io/packagist/v/alchemy/queue-component.svg?style=flat-square
[badge-travis]: https://img.shields.io/travis/alchemy-fr/queue-component.svg?style=flat-square
[badge-coverage]: https://img.shields.io/scrutinizer/coverage/g/alchemy-fr/queue-component.svg?style=flat-square
[badge-quality]: https://img.shields.io/scrutinizer/g/alchemy-fr/queue-component.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/alchemy/queue-component.svg?style=flat-square

[license]: https://github.com/alchemy-fr/queue-component/LICENSE
[packagist]: https://packagist.org/packages/alchemy/queue-component
[travis]: https://travis-ci.org/alchemy-fr/queue-component
[coverage]: https://scrutinizer-ci.com/g/alchemy-fr/queue-component/?branch=master
[quality]: https://scrutinizer-ci.com/g/alchemy-fr/queue-component/
[downloads]: https://packagist.org/packages/alchemy/queue-component/stats
