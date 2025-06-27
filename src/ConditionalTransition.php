<?php

namespace ProjectSaturnStudios\PocketFlow;

class ConditionalTransition
{
    public function __construct(public BaseNode $src, public string $action)
    {

    }

    public function _r_shift(BaseNode $tgt): BaseNode
    {
        return $this->src->next($tgt, $this->action);
    }
}
