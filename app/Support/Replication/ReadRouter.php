<?php

namespace App\Support\Replication;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Decides, for each read, whether the replica may answer it.
 *
 * The rule: a read goes to the replica only if the replica has already applied every
 * transaction the primary has committed at that moment. Every write the app has
 * acknowledged to a client was committed before the acknowledgement, so it is part of
 * that set. A client that was told "saved" therefore never gets older data from the
 * replica, even while the replica is lagging behind.
 *
 * The primary's position is read on every call and nothing is stored. A stored position
 * could be overwritten by an older one when two writes finish out of order.
 */
class ReadRouter
{
    public const REPLICA = 'mysql_replica';

    public function __construct(private readonly ReplicaPosition $position) {}

    public function enabled(): bool
    {
        return (bool) config('performance.replica.enabled');
    }

    /**
     * The connection for a read: ReadRouter::REPLICA, or null for the default (primary) connection.
     */
    public function readConnection(): ?string
    {
        if (! $this->enabled() || DB::connection()->transactionLevel() > 0) {
            // Inside a transaction the code must see its own uncommitted writes, so it uses the primary.
            return null;
        }

        try {
            $executed = $this->position->primaryExecuted();
        } catch (QueryException $e) {
            report($e);

            return null;
        }

        // An empty set means GTIDs are off or nothing has been committed. Only the primary is safe then.
        if ($executed === '') {
            return null;
        }

        return $this->position->replicaHasExecuted($executed) ? self::REPLICA : null;
    }
}
