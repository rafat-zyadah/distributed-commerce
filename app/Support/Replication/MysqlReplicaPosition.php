<?php

namespace App\Support\Replication;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * MySQL implementation of ReplicaPosition using GTIDs.
 *
 * - @@GLOBAL.gtid_executed on the primary lists every transaction the primary has committed.
 * - WAIT_FOR_EXECUTED_GTID_SET(set, 0) on the replica returns 0 at once when the replica
 *   has applied all of "set", and 1 when it has not. A timeout of 0 means "do not wait".
 *   It returns NULL if the wait is interrupted; that counts as "not caught up".
 */
class MysqlReplicaPosition implements ReplicaPosition
{
    public function primaryExecuted(): string
    {
        return (string) DB::connection()
            ->selectOne('SELECT @@GLOBAL.gtid_executed AS gtid')
            ->gtid;
    }

    public function replicaHasExecuted(string $gtidSet): bool
    {
        try {
            $row = DB::connection(ReadRouter::REPLICA)->selectOne(
                'SELECT WAIT_FOR_EXECUTED_GTID_SET(?, 0) AS timed_out',
                [$gtidSet]
            );
        } catch (QueryException $e) {
            // Replica down, or the server has no GTID functions (for example MariaDB): read the primary.
            report($e);

            return false;
        }

        // Only an explicit 0 means every transaction was applied. NULL must not count as success.
        return $row->timed_out !== null && (int) $row->timed_out === 0;
    }
}
