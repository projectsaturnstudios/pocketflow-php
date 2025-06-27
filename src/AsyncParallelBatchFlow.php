<?php

namespace ProjectSaturnStudios\PocketFlow;

use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use function React\Promise\all;

class AsyncParallelBatchFlow extends AsyncFlow
{
    public function _run_async(mixed $shared): PromiseInterface
    {
        return $this->prep_async($shared)
            ->then(function($pr) use ($shared) {
                $pr = $pr ?: [];

                if (empty($pr)) {
                    return $this->post_async($shared, $pr, null);
                }

                // Create promises for all flow orchestrations to run in parallel
                $promises = [];
                foreach ($pr as $bp) {
                    $merged_params = array_merge($this->params, $bp);
                    $promises[] = $this->_orch_async($shared, $merged_params);
                }

                // Run all flows in parallel using React\Promise\all()
                // This is the ReactPHP equivalent of Python's asyncio.gather()
                return all($promises)->then(function() use ($shared, $pr) {
                    return $this->post_async($shared, $pr, null);
                });
            });
    }
}
