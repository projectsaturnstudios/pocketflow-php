<?php

namespace ProjectSaturnStudios\PocketFlow;

class BatchNode extends Node
{
    public function _exec(mixed $items): array
    {
        $items = $items ?: [];
        $results = [];

        foreach ($items as $item) {
            $results[] = parent::_exec($item);
        }

        return $results;
    }
}
