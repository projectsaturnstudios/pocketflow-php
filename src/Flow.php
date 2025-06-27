<?php

namespace ProjectSaturnStudios\PocketFlow;

class Flow extends BaseNode
{
    protected ?BaseNode $start_node = null;

    public function __construct(?BaseNode $start = null)
    {
        parent::__construct();
        $this->start_node = $start;
    }

    public function start(BaseNode $start): BaseNode
    {
        $this->start_node = $start;
        return $start;
    }

    public function get_next_node(BaseNode $curr, ?string $action): ?BaseNode
    {
        $next = $curr->successors[$action ?? 'default'] ?? null;

        if (!$next && !empty($curr->successors)) {
            $available_actions = array_keys($curr->successors);
            trigger_error(
                "Flow ends: '{$action}' not found in [" . implode(', ', $available_actions) . "]",
                E_USER_WARNING
            );
        }

        return $next;
    }

    protected function _orch(mixed &$shared, ?array $params = null): mixed
    {
        $curr = clone $this->start_node;
        $p = $params ?? array_merge([], $this->params);
        $last_action = null;

        while ($curr) {
            $curr->set_params($p);
            $last_action = $curr->_run($shared);
            $curr = $this->get_next_node($curr, $last_action);
            if ($curr) {
                $curr = clone $curr;
            }
        }

        return $last_action;
    }

    public function _run(mixed &$shared): mixed
    {
        $p = $this->prep($shared);
        $o = $this->_orch($shared);
        return $this->post($shared, $p, $o);
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        return $exec_res;
    }
}
