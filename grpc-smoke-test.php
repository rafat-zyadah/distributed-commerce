<?php

require __DIR__ . '/vendor/autoload.php';

use Inventory\GetStockRequest;
use Inventory\InventoryServiceClient;
use Inventory\SetStockRequest;
use Grpc\ChannelCredentials;

$host = getenv('INVENTORY_GRPC_HOST') ?: 'inventory:50051';

$client = new InventoryServiceClient($host, [
    'credentials' => ChannelCredentials::createInsecure(),
]);

$productId = 'grpc-smoke-test-product';
$expectedQuantity = 37;

$setRequest = new SetStockRequest();
$setRequest->setProductId($productId);
$setRequest->setQuantity($expectedQuantity);

[$setReply, $setStatus] = $client->SetStock(
    $setRequest,
    [],
    ['timeout' => 5000000]
)->wait();

echo "SetStock status: {$setStatus->code} {$setStatus->details}\n";

if ($setStatus->code !== \Grpc\STATUS_OK) {
    fwrite(STDERR, "SetStock failed\n");
    exit(1);
}

$getRequest = new GetStockRequest();
$getRequest->setProductId($productId);

[$getReply, $getStatus] = $client->GetStock(
    $getRequest,
    [],
    ['timeout' => 5000000]
)->wait();

echo "GetStock status: {$getStatus->code} {$getStatus->details}\n";

if ($getStatus->code !== \Grpc\STATUS_OK) {
    fwrite(STDERR, "GetStock failed\n");
    exit(1);
}

echo "Product ID: {$getReply->getProductId()}\n";
echo "Quantity: {$getReply->getQuantity()}\n";

if (
    $getReply->getProductId() !== $productId ||
    $getReply->getQuantity() !== $expectedQuantity
) {
    fwrite(STDERR, "Unexpected stock reply\n");
    exit(1);
}

echo "gRPC connectivity test passed.\n";
