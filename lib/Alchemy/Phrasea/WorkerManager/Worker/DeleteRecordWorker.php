<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;

class DeleteRecordWorker implements WorkerInterface
{
    use ApplicationBoxAware;

    public function process(array $payload)
    {
        $record = $this->findDataboxById($payload['databoxId'])->get_record($payload['recordId']);

        $record->delete();
    }
}
