<?php

namespace ProjectSaturnStudios\PocketFlow;

use React\Promise\PromiseInterface;
use function React\Promise\resolve;

class AsyncBatchNode extends AsyncNode
{
    public function _exec(mixed $items): PromiseInterface
    {
        $items = $items ?: [];

        if (empty($items)) {
            return resolve([]);
        }

        // Process items sequentially
        $results = [];
        $promise = resolve(null);

        foreach ($items as $index => $item) {
            $promise = $promise->then(function() use ($item, &$results, $index) {
                return parent::_exec($item)->then(function($result) use (&$results, $index) {
                    $results[$index] = $result;
                    return $result;
                });
            });
        }

        return $promise->then(function() use (&$results) {
            return $results;
        });
    }
}
