<?php

namespace Tests\Support;

use App\Support\Replication\ReplicaPosition;
use Throwable;

/**
 * A ReplicaPosition whose answers the test controls, used instead of a real primary and replica.
 */
class FakeReplicaPosition implements ReplicaPosition
{
    /** What the "primary" reports as executed. */
    public string $primary = 'primary-uuid:1-10';

    /** Whether the "replica" has applied everything it is asked about. */
    public bool $replicaCaughtUp = true;

    /** When set, primaryExecuted() throws it, to simulate a primary error. */
    public ?Throwable $primaryError = null;

    /** @var list<string> every set that replicaHasExecuted() was asked about */
    public array $asked = [];

    public function primaryExecuted(): string
    {
        if ($this->primaryError !== null) {
            throw $this->primaryError;
        }

        return $this->primary;
    }

    public function replicaHasExecuted(string $gtidSet): bool
    {
        $this->asked[] = $gtidSet;

        return $this->replicaCaughtUp;
    }
}
