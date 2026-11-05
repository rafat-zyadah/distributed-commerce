<?php

namespace App\Modules\Orders\Services;

use Grpc\ChannelCredentials;
use Inventory\ChangeStockRequest;
use Inventory\GetStockRequest;
use Inventory\InventoryServiceClient;
use Inventory\SetStockRequest;
use RuntimeException;

class InventoryGrpc
{
    private InventoryServiceClient $client;

    public function __construct()
    {
        $host = getenv('INVENTORY_GRPC_HOST') ?: 'inventory:50051';

        $this->client = new InventoryServiceClient($host, [
            'credentials' => ChannelCredentials::createInsecure(),
        ]);
    }

    public function reserve(int|string $productId, int $quantity): void
    {
        $request = new ChangeStockRequest();
        $request->setProductId((string) $productId);
        $request->setQuantity($quantity);

        [, $status] = $this->client->ReserveStock(
            $request,
            [],
            ['timeout' => 5000000]
        )->wait();

        $this->checkStatus($status->code, $status->details);
    }

    public function release(int|string $productId, int $quantity): void
    {
        $request = new ChangeStockRequest();
        $request->setProductId((string) $productId);
        $request->setQuantity($quantity);

        [, $status] = $this->client->ReleaseStock(
            $request,
            [],
            ['timeout' => 5000000]
        )->wait();

        $this->checkStatus($status->code, $status->details);
    }

    private function checkStatus(int $code, string $details): void
    {
        if ($code !== \Grpc\STATUS_OK) {
            throw new RuntimeException(
                "Inventory gRPC request failed ({$code}): {$details}"
            );
        }
    }
}
