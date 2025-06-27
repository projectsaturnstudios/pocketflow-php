<?php

namespace ProjectSaturnStudios\PocketFlow;

class BatchFlow extends Flow
{
    public function _run(mixed $shared): mixed
    {
        $pr = $this->prep($shared) ?: [];

        foreach ($pr as $bp) {
            $merged_params = array_merge($this->params, $bp);
            $this->_orch($shared, $merged_params);
        }

        return $this->post($shared, $pr, null);
    }
}
