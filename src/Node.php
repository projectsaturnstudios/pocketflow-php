<?php

namespace ProjectSaturnStudios\PocketFlow;

class Node extends BaseNode
{
    protected int $cur_retry = 0;

    public function __construct(public int $max_retries = 1, public int $wait = 0)
    {
        parent::__construct();
    }

    public function exec_fallback(mixed $prep_res, \Exception $e): mixed
    {
        throw $e;
    }

    public function _exec(mixed $prep_res): mixed
    {
        for($this->cur_retry = 0; $this->cur_retry < $this->max_retries; $this->cur_retry++) {
            try {
                return parent::_exec($prep_res);
            } catch (\Exception $e) {
                if($this->cur_retry === $this->max_retries - 1) {
                    return $this->exec_fallback($prep_res, $e);
                }
                if($this->wait > 0) {
                    sleep($this->wait);
                }
            }
        }
    }
}
