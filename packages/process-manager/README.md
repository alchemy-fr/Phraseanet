# PHP Process Manager

This is based on SYmfony Process and the work done in [symfony/symfony#8753](https://github.com/symfony/symfony/pull/8753).

[![Build Status](https://travis-ci.org/romainneutron/ProcessManager.png?branch=master)](https://travis-ci.org/romainneutron/ProcessManager)

## Usage examples

Run 4 jobs in parallel

```php
$manager = new Neutron\ProcessManager\ProcessManager();
$manager
    ->add(new Process(['...']))
    ->add(new Process(['...']))
    ->add(new Process(['...']))
    ->add(new Process(['...']))
    ->run();
```

Run 4 jobs in queue

```php
$manager = new Neutron\ProcessManager\ProcessManager();
$manager
    ->setMaxParallelProcesses(1)
    ->add(new Process(['...']))
    ->add(new Process(['...']))
    ->add(new Process(['...']))
    ->add(new Process(['...']))
    ->run();
```

## License

This is released under the MIT License.
