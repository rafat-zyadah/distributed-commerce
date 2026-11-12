<?php

namespace App\Support\Replication;

/**
 * Answers "how far has the primary progressed" and "has the replica caught up to a position".
 * Positions are MySQL GTID sets, for example "3f1c...:1-1500".
 */
interface ReplicaPosition
{
    /** The GTID set executed on the primary right now. Every write committed so far is inside it. */
    public function primaryExecuted(): string;

    /** True when the replica has already applied every transaction in $gtidSet. Never waits. */
    public function replicaHasExecuted(string $gtidSet): bool;
}
