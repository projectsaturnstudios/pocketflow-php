<?php

namespace ProjectSaturnStudios\PocketFlow;

use React\Promise\PromiseInterface;
use function React\Promise\resolve;

class AsyncFlow extends Flow
{
    // Inherit AsyncNode's async methods
    public function prep_async(mixed $shared): PromiseInterface
    {
        return resolve(null);
    }

    public function post_async(mixed $shared, mixed $prep_res, mixed $exec_res): PromiseInterface
    {
        return resolve($exec_res);
    }

    protected function _orch_async(mixed $shared, ?array $params = null): PromiseInterface
    {
        $curr = clone $this->start_node;
        $p = $params ?? array_merge([], $this->params);

        $processNode = function($curr, $p) use ($shared, &$processNode): PromiseInterface {
            if (!$curr) {
                return resolve(null);
            }

            $curr->set_params($p);

            // Check if node is async or sync and call appropriate method
            if ($curr instanceof AsyncNode) {
                $runPromise = $curr->_run_async($shared);
            } else {
                // Wrap synchronous _run in a resolved promise
                try {
                    $result = $curr->_run($shared);
                    $runPromise = resolve($result);
                } catch (\Exception $e) {
                    $runPromise = resolve(null); // Or could reject, depending on desired behavior
                }
            }

            return $runPromise->then(function($last_action) use ($curr, $shared, $p, $processNode) {
                $next = $this->get_next_node($curr, $last_action);
                if ($next) {
                    $next = clone $next;
                    return $processNode($next, $p);
                }
                return $last_action;
            });
        };

        return $processNode($curr, $p);
    }

    public function _run_async(mixed $shared): PromiseInterface
    {
        return $this->prep_async($shared)
            ->then(function($p) use ($shared) {
                return $this->_orch_async($shared);
            })
            ->then(function($o) use ($shared) {
                return $this->post_async($shared, null, $o);
            });
    }

    public function run_async(mixed $shared): PromiseInterface
    {
        return $this->_run_async($shared);
    }

    // Override synchronous _run to throw error
    public function _run(mixed &$shared): mixed
    {
        throw new \RuntimeException("Use run_async() for AsyncFlow.");
    }
}
