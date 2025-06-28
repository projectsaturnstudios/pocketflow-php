<?php

namespace ProjectSaturnStudios\PocketFlow;

abstract class BaseNode
{
    protected array $params = [];
    protected array $successors = [];

    public function __construct()
    {

    }

    public function set_params(array $params): void
    {
        $this->params = $params;
    }

    public function next(BaseNode $node, string $action = 'default'): BaseNode
    {
        $this->successors[$action] = $node;
        return $node;
    }

    public function prep(mixed &$shared): mixed
    {
        return null;
    }

    public function exec(mixed $prep_res): mixed
    {
        return null;
    }
    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        return null;
    }

    public function _exec(mixed $prep_res): mixed
    {
        return $this->exec($prep_res);
    }

    public function _run(mixed &$shared): mixed
    {
        $p = $this->prep($shared);
        $e = $this->exec($p);
        return $this->post($shared, $p, $e);
    }

    public function _r_shift(BaseNode $other): BaseNode
    {
        return $this->next($other);
    }

    public function _sub(mixed $action): ConditionalTransition
    {
        if(is_string($action)) return new ConditionalTransition($this, $action);
        throw new \DomainException("Action must be a string");
    }

}
