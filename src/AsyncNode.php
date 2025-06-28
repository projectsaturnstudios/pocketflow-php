<?php

namespace ProjectSaturnStudios\PocketFlow;

use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use function React\Promise\reject;

class AsyncNode extends Node
{
    public function prep_async(mixed $shared): PromiseInterface
    {
        return resolve(null);
    }

    public function exec_async(mixed $prep_res): PromiseInterface
    {
        return resolve(null);
    }

    public function exec_fallback_async(mixed $prep_res, \Exception $e): PromiseInterface
    {
        return reject($e);
    }

    public function post_async(mixed $shared, mixed $prep_res, mixed $exec_res): PromiseInterface
    {
        return resolve(null);
    }

    public function _exec(mixed $prep_res): PromiseInterface
    {
        $attempts = 0;
        $maxRetries = $this->max_retries;
        $wait = $this->wait;

        $tryExec = function() use (&$tryExec, $prep_res, &$attempts, $maxRetries, $wait): PromiseInterface {
            return $this->exec_async($prep_res)
                ->catch(function(\Exception $e) use (&$tryExec, $prep_res, &$attempts, $maxRetries, $wait): PromiseInterface {
                    $attempts++;
                    if ($attempts >= $maxRetries) {
                        return $this->exec_fallback_async($prep_res, $e);
                    }

                    if ($wait > 0) {
                        // Create a delayed promise (simplified - in real usage you'd use ReactPHP's timer)
                        $deferred = new Deferred();
                        $this->delay($wait)->then(function() use ($deferred, $tryExec) {
                            $tryExec()->then([$deferred, 'resolve'], [$deferred, 'reject']);
                        });
                        return $deferred->promise();
                    }

                    return $tryExec();
                });
        };

        return $tryExec();
    }

    public function run_async(mixed $shared): PromiseInterface
    {
        if (!empty($this->successors)) {
            trigger_error("Node won't run successors. Use AsyncFlow.", E_USER_WARNING);
        }
        return $this->_run_async($shared);
    }

    public function _run_async(mixed $shared): PromiseInterface
    {
        return $this->prep_async($shared)
            ->then(function($p) use ($shared) {
                return $this->_exec($p)->then(function($e) use ($shared, $p) {
                    return $this->post_async($shared, $p, $e);
                });
            });
    }

    public function _run(mixed &$shared): mixed
    {
        throw new \RuntimeException("Use run_async() for AsyncNode.");
    }

    // Simple delay helper (in real usage, use ReactPHP's timer)
    private function delay(int $seconds): PromiseInterface
    {
        $deferred = new Deferred();
        // This is a simplified delay - in production you'd use React\EventLoop\Loop::get()->addTimer()
        $deferred->resolve(null);
        return $deferred->promise();
    }
}
