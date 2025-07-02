<?php

use ProjectSaturnStudios\PocketFlow\Flow;
use ProjectSaturnStudios\PocketFlow\Node;
use ProjectSaturnStudios\PocketFlow\AsyncNode;

if(!function_exists('flow'))
{
    function flow(Node|AsyncNode $node, mixed $shared):  mixed
    {
        $flow = new Flow($node);
        $flow->_run($shared);
        return $shared;
    }
}
