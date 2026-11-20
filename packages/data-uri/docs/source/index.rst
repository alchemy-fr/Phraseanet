PHP-dataURI  documentation
==========================

Introduction
------------

PHP-DataURI is a library which handles URI scheme and provide a way to include
data in-line in web pages as if they were external ressources.

This feature is mainly used with HTML5 file API.

This is supposed 100% `RFC 2397 <http://www.ietf.org/rfc/rfc2397.txt>`_

Installation
------------

We rely on `composer <http://getcomposer.org/>`_ to use this library. If you do
no still use composer for your project, you can start with this ``composer.json``
at the root of your project:

.. code-block:: json

    {
        "require": {
            "data-uri/data-uri": "dev-master"
        }
    }

Install composer :

.. code-block:: bash

    # Install composer
    curl -s http://getcomposer.org/installer | php
    # Upgrade your install
    php composer.phar install

You now just have to autoload the library to use it :

.. code-block:: php

    <?php
    require 'vendor/autoload.php';

This is a very short intro to composer.
If you ever experience an issue or want to know more about composer,
you will find help on their  website
`http://getcomposer.org/ <http://getcomposer.org/>`_.

PHP dataURI
-----------

The PHP-dataURI library is very simple and consists of three main classes :

* One represents a data URI scheme as a PHP object
* One parses the data URI string
* One dumps the data URI PHP object

Parser
^^^^^^

.. code-block:: php

    <?php
    use DataURI;

    $dataString = "data:text/plain;charset=utf-8,%23%24%25";

    // Parse one data URI scheme and return a Data object
    $dataObject = DataURI\Parser::parse($dataString);

    echo $dataObject->getMimeType();
    // Output text/plain
    echo $dataObject->getData();
    // Output #$%
    var_dump($dataObject->getParameters());
    // Output an array of parameters array('charset' => 'utf-8')

Dumper
^^^^^^

.. code-block:: php

    <?php
    use DataURI;

    // Instance a Data object
    $dataObject = new DataURI\Data("#$%");
    // Add some parameters
    $dataObject->addParameters('charset' => 'utf-8');

    echo DaraURI\Dumper::dump($dataObject);
    // Output data:text/plain;charset=utf-8,%23%24%25

Dump URI from file
^^^^^^^^^^^^^^^^^^

.. code-block:: php

    <?php
    use DataURI;

    $dataObject = DataURI\Data::buildFromFile("/path/to/my/image.png");
    echo DaraURI\Dumper::dump($dataObject);
    // Output data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...+S/EAAAAASUVORK5CYII=

Handling Exceptions
-------------------

PHP-dataURI throws 4 different types of exception :

- ``\DataURI\Exception\FileNotFoundException`` is thrown when an invalid
  pathfile is supplied
- ``\DataURI\Exception\InvalidDataException`` is thrown when raw data could not
  be decoded
- ``\DataURI\Exception\TooLongDataException`` is thrown when provided data is too
  long according to the RFC 2397
- ``\DataURI\Exception\InvalidArgumentException`` is thrown when provided data URI
  scheme could not be parsed extends SPL InvalidArgumentException

All these Exception implements ``DataURI\Exception\Exception`` so you can catch
any of these exceptions by catching this exception interface.

Report a bug
------------

If you experience an issue, please report it in our
`issue tracker <https://github.com/alchemy-fr/PHP-dataURI/issues>`_. Before
reporting an issue, please be sure that it is not already reported by browsing
open issues.

Contribute
----------

You find a bug and resolved it ? You added a feature and want to share ? You
found a typo in this doc and fixed it ? Feel free to send a
`Pull Request <http://help.github.com/send-pull-requests/>`_ on GitHub, we will
be glad to merge your code.

Run tests
---------

PHP-dataURI relies on `PHPUnit <http://www.phpunit.de/manual/current/en/>`_ for
unit tests. To run tests on your system, ensure you have PHPUnit installed,
and, at the root of PHP-dataURI, execute it :

.. code-block:: bash

    phpunit

About
-----

PHP-dataURI has been written by Nicolas Le Goff @ `Alchemy <http://alchemy.fr/>`_
for `Phraseanet <https://github.com/alchemy-fr/Phraseanet>`_, our DAM software.
Try it, it's awesome !

License
-------

PHP-dataURI is licensed under the `MIT License <http://opensource.org/licenses/MIT>`_
