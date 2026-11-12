<?php

namespace Tests\Unit;

use App\Support\Replication\ReadRouter;
use App\Support\Replication\ReplicaPosition;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\FakeReplicaPosition;
use Tests\TestCase;

class ReadRouterTest extends TestCase
{
    private FakeReplicaPosition $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->position = new FakeReplicaPosition();
        $this->app->instance(ReplicaPosition::class, $this->position);
        config(['performance.replica.enabled' => true]);
    }

    private function router(): ReadRouter
    {
        return $this->app->make(ReadRouter::class);
    }

    public function test_reads_stay_on_the_primary_when_the_replica_is_disabled(): void
    {
        config(['performance.replica.enabled' => false]);

        $this->assertNull($this->router()->readConnection());
        $this->assertSame([], $this->position->asked);
    }

    public function test_reads_use_the_replica_when_it_has_applied_everything_the_primary_has(): void
    {
        $this->position->replicaCaughtUp = true;

        $this->assertSame(ReadRouter::REPLICA, $this->router()->readConnection());
    }

    public function test_reads_use_the_primary_while_the_replica_is_behind(): void
    {
        $this->position->replicaCaughtUp = false;

        $this->assertNull($this->router()->readConnection());
    }

    public function test_the_replica_is_asked_about_the_primarys_current_position(): void
    {
        $this->position->primary = 'abc:1-42';

        $this->router()->readConnection();

        $this->assertSame(['abc:1-42'], $this->position->asked);
    }

    public function test_the_position_is_read_again_on_every_call(): void
    {
        // A write that commits between two reads must be seen by the second read.
        $this->position->primary = 'abc:1-1';
        $this->position->replicaCaughtUp = true;
        $this->assertSame(ReadRouter::REPLICA, $this->router()->readConnection());

        $this->position->primary = 'abc:1-2';
        $this->position->replicaCaughtUp = false;
        $this->assertNull($this->router()->readConnection());
    }

    public function test_an_empty_position_uses_the_primary(): void
    {
        // Empty means GTIDs are off or nothing was committed. The replica cannot be trusted then.
        $this->position->primary = '';
        $this->position->replicaCaughtUp = true;

        $this->assertNull($this->router()->readConnection());
        $this->assertSame([], $this->position->asked);
    }

    public function test_a_primary_error_falls_back_to_the_primary(): void
    {
        $this->position->primaryError = new QueryException(
            'mysql',
            'SELECT @@GLOBAL.gtid_executed',
            [],
            new RuntimeException('server has gone away'),
        );

        $this->assertNull($this->router()->readConnection());
    }

    public function test_reads_inside_a_transaction_use_the_primary(): void
    {
        DB::transaction(function () {
            $this->assertNull($this->router()->readConnection());
        });

        $this->assertSame([], $this->position->asked);
    }
}
