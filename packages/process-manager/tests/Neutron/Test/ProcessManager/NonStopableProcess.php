<?php

// required for signal handling
declare(ticks = 1);

pcntl_signal(SIGTERM, function () {});

$end = microtime(true) + (float) ($argv[1] ?? 3);

while (microtime(true) < $end) {
    usleep(10000);
}
