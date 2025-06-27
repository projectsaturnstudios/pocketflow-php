<?php

namespace ProjectSaturnStudios\PocketFlow;

use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use function React\Promise\all;

class AsyncParallelBatchNode extends AsyncNode
{
    public function _exec(mixed $items): PromiseInterface
    {
        $items = $items ?: [];

        if (empty($items)) {
            return resolve([]);
        }

        // Create promises for all items
        $promises = [];
        foreach ($items as $item) {
            $promises[] = parent::_exec($item);
        }

        // Process all items in parallel using React\Promise\all()
        // This is the ReactPHP equivalent of Python's asyncio.gather()
        return all($promises);
    }
}
