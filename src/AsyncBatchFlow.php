<?php

namespace ProjectSaturnStudios\PocketFlow;

use React\Promise\PromiseInterface;
use function React\Promise\resolve;

class AsyncBatchFlow extends AsyncFlow
{
    public function _run_async(mixed $shared): PromiseInterface
    {
        return $this->prep_async($shared)
            ->then(function($pr) use ($shared) {
                $pr = $pr ?: [];

                if (empty($pr)) {
                    return $this->post_async($shared, $pr, null);
                }

                // Process each batch parameter set sequentially
                $promise = resolve(null);

                foreach ($pr as $bp) {
                    $promise = $promise->then(function() use ($shared, $bp) {
                        $merged_params = array_merge($this->params, $bp);
                        return $this->_orch_async($shared, $merged_params);
                    });
                }

                return $promise->then(function() use ($shared, $pr) {
                    return $this->post_async($shared, $pr, null);
                });
            });
    }
}
