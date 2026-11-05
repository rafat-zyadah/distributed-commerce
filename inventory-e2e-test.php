<?php

declare(strict_types=1);

use App\Modules\Orders\Models\Order;
use App\Modules\Products\Models\Product;
use App\Modules\Users\Models\User;
use Grpc\ChannelCredentials;
use Inventory\GetStockRequest;
use Inventory\InventoryServiceClient;
use Inventory\SetStockRequest;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function failTest(string $message): never
{
    fwrite(STDERR, "\nFAIL: {$message}\n");
    exit(1);
}

/**
 * Send an HTTP request to Laravel inside the container.
 *
 * @return array{0:int,1:array<string,mixed>}
 */
function apiRequest(
    string $method,
    string $path,
    ?string $token = null,
    ?array $payload = null
): array {
    $baseUrl = rtrim(getenv('TEST_API_BASE') ?: 'http://127.0.0.1', '/');

    $headers = [
        'Accept: application/json',
    ];

    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $content = '';
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $content = json_encode($payload, JSON_THROW_ON_ERROR);
    }

    $context = stream_context_create([
        'http' => [
            'method' => strtoupper($method),
            'header' => implode("\r\n", $headers),
            'content' => $content,
            'ignore_errors' => true,
            'timeout' => 15,
        ],
    ]);

    $response = @file_get_contents($baseUrl . $path, false, $context);

    if ($response === false) {
        failTest(
            "Could not connect to {$baseUrl}{$path}. "
            . 'Make sure the app container is running and Apache is listening on port 80.'
        );
    }

    $statusCode = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
            $statusCode = (int) $matches[1];
        }
    }

    $decoded = json_decode($response, true);

    if (! is_array($decoded)) {
        $decoded = ['raw_response' => $response];
    }

    return [$statusCode, $decoded];
}

/**
 * Validate the gRPC result and return the inventory message.
 */
function waitForGrpcReply($call, string $operation)
{
    [$reply, $status] = $call->wait();

    if ($status->code !== \Grpc\STATUS_OK) {
        failTest(
            "{$operation} failed over gRPC: "
            . "code={$status->code}; details={$status->details}"
        );
    }

    if ($reply === null) {
        failTest("{$operation} returned an empty gRPC response.");
    }

    return $reply;
}

function setStock(InventoryServiceClient $client, string $productId, int $quantity): int
{
    $request = new SetStockRequest();
    $request->setProductId($productId);
    $request->setQuantity($quantity);

    $reply = waitForGrpcReply(
        $client->SetStock($request, [], ['timeout' => 5_000_000]),
        'SetStock'
    );

    return $reply->getQuantity();
}

function getStock(InventoryServiceClient $client, string $productId): int
{
    $request = new GetStockRequest();
    $request->setProductId($productId);

    $reply = waitForGrpcReply(
        $client->GetStock($request, [], ['timeout' => 5_000_000]),
        'GetStock'
    );

    return $reply->getQuantity();
}

function assertSameValue(mixed $expected, mixed $actual, string $description): void
{
    if ($expected !== $actual) {
        failTest(
            "{$description}: expected " . var_export($expected, true)
            . ', got ' . var_export($actual, true)
        );
    }

    echo "PASS: {$description}\n";
}

/**
 * Send multiple POST requests concurrently.
 *
 * @param array<int,array<string,mixed>> $payloads
 * @return array<int,array{0:int,1:array<string,mixed>}>
 */
function concurrentPostRequests(string $path, string $token, array $payloads): array
{
    if (! function_exists('curl_multi_init')) {
        failTest('The cURL extension is required for the concurrent order test.');
    }

    $baseUrl = rtrim(getenv('TEST_API_BASE') ?: 'http://127.0.0.1', '/');
    $multiHandle = curl_multi_init();
    $handles = [];

    foreach ($payloads as $index => $payload) {
        $handle = curl_init($baseUrl . $path);

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => 15,
        ]);

        curl_multi_add_handle($multiHandle, $handle);
        $handles[$index] = $handle;
    }

    $running = null;

    do {
        $result = curl_multi_exec($multiHandle, $running);

        if ($result !== CURLM_OK) {
            failTest('The concurrent HTTP requests could not be completed.');
        }

        if ($running > 0) {
            $selected = curl_multi_select($multiHandle);

            if ($selected === -1) {
                usleep(1_000);
            }
        }
    } while ($running > 0);

    $responses = [];

    foreach ($handles as $index => $handle) {
        $body = curl_multi_getcontent($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($body === false) {
            failTest('A concurrent HTTP request failed: ' . curl_error($handle));
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            $decoded = ['raw_response' => $body];
        }

        $responses[$index] = [$statusCode, $decoded];

        curl_multi_remove_handle($multiHandle, $handle);
        curl_close($handle);
    }

    curl_multi_close($multiHandle);

    return $responses;
}


try {
    $grpcHost = getenv('INVENTORY_GRPC_HOST') ?: 'inventory:50051';

    echo "Connecting to gRPC service at {$grpcHost}...\n";

    $inventoryClient = new InventoryServiceClient($grpcHost, [
        'credentials' => ChannelCredentials::createInsecure(),
    ]);

    /*
     * Create test users. The admin role is assigned directly in the database
     * because public registration creates customer accounts only.
     */
    $suffix = bin2hex(random_bytes(5));
    $password = 'TestPass123';

    $customer = User::create([
        'name' => 'Inventory E2E Customer ' . $suffix,
        'email' => 'inventory-customer-' . $suffix . '@example.test',
        'password' => $password,
    ]);

    $admin = User::create([
        'name' => 'Inventory E2E Admin ' . $suffix,
        'email' => 'inventory-admin-' . $suffix . '@example.test',
        'password' => $password,
    ]);

    DB::table('users')
        ->where('id', $admin->id)
        ->update(['role' => User::ROLE_ADMIN]);

    /*
     * Create an active test product.
     * The products.stock column in MySQL is not the inventory source of truth;
     * the inventory source being tested is the Python service over gRPC.
     */
    $product = Product::create([
        'name' => 'Inventory E2E Product ' . $suffix,
        'description' => 'Temporary product created by inventory-e2e-test.php',
        'category' => 'e2e-test',
        'price' => 12.50,
        'stock' => 0,
        'status' => Product::STATUS_ACTIVE,
    ]);

    $productId = (string) $product->id;

    echo "Product ID: {$productId}\n";
    echo "Logging in through the REST API...\n";

    [$customerLoginStatus, $customerLogin] = apiRequest(
        'POST',
        '/api/v1/auth/login',
        null,
        [
            'email' => $customer->email,
            'password' => $password,
        ]
    );

    assertSameValue(200, $customerLoginStatus, 'Customer login');

    $customerToken = $customerLogin['access_token'] ?? null;
    if (! is_string($customerToken) || $customerToken === '') {
        failTest('The customer login response does not contain an access_token.');
    }

    [$adminLoginStatus, $adminLogin] = apiRequest(
        'POST',
        '/api/v1/auth/login',
        null,
        [
            'email' => $admin->email,
            'password' => $password,
        ]
    );

    assertSameValue(200, $adminLoginStatus, 'Admin login');

    $adminToken = $adminLogin['access_token'] ?? null;
    if (! is_string($adminToken) || $adminToken === '') {
        failTest('The admin login response does not contain an access_token.');
    }

    /*
     * Test 1: placing an order reserves inventory.
     */
    echo "\nTest 1: Reserve inventory when placing an order\n";

    $initialStock = setStock($inventoryClient, $productId, 10);
    assertSameValue(10, $initialStock, 'Set initial stock to 10');

    [$placeStatus, $placeBody] = apiRequest(
        'POST',
        '/api/v1/orders',
        $customerToken,
        [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
        ]
    );

    assertSameValue(201, $placeStatus, 'Create order through the REST API');

    // Laravel JsonResource responses are commonly wrapped in a data property.
    $orderData = $placeBody['data'] ?? $placeBody;
    $orderId = $orderData['id'] ?? null;

    if (! is_numeric($orderId)) {
        failTest('Could not find the order ID in the POST /api/v1/orders response.');
    }

    assertSameValue('pending', $orderData['status'] ?? null, 'Initial order status is pending');

    $stockAfterOrder = getStock($inventoryClient, $productId);
    assertSameValue(7, $stockAfterOrder, 'Reserve 3 units and reduce stock from 10 to 7');

    /*
     * Test 2: cancelling an order returns inventory.
     */
    echo "\nTest 2: Return inventory when cancelling an order\n";

    [$cancelStatus, $cancelBody] = apiRequest(
        'PATCH',
        "/api/v1/orders/{$orderId}/status",
        $adminToken,
        ['status' => Order::STATUS_CANCELLED]
    );

    assertSameValue(200, $cancelStatus, 'Cancel order through the REST API');

    $cancelData = $cancelBody['data'] ?? $cancelBody;
    assertSameValue(
        Order::STATUS_CANCELLED,
        $cancelData['status'] ?? null,
        'Update order status to cancelled'
    );

    $stockAfterCancellation = getStock($inventoryClient, $productId);
    assertSameValue(10, $stockAfterCancellation, 'Return the three units to inventory');

    /*
     * Repeating the cancellation must not return the quantity a second time.
     */
    [$secondCancelStatus] = apiRequest(
        'PATCH',
        "/api/v1/orders/{$orderId}/status",
        $adminToken,
        ['status' => Order::STATUS_CANCELLED]
    );

    assertSameValue(200, $secondCancelStatus, 'Repeated cancellation request succeeds');

    $stockAfterSecondCancellation = getStock($inventoryClient, $productId);
    assertSameValue(
        10,
        $stockAfterSecondCancellation,
        'Repeated cancellation does not return inventory twice'
    );

    /*
     * Test 3: reject an order when the requested quantity exceeds available stock.
     */
    echo "\nTest 3: Reject an order that exceeds available stock\n";

    $lowStock = setStock($inventoryClient, $productId, 1);
    assertSameValue(1, $lowStock, 'Set stock to one unit');

    $itemCountBeforeFailure = DB::table('order_items')
        ->where('product_id', $product->id)
        ->count();

    [$insufficientStatus, $insufficientBody] = apiRequest(
        'POST',
        '/api/v1/orders',
        $customerToken,
        [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]
    );

    if ($insufficientStatus !== 422) {
        failTest(
            'An order exceeding available stock should return HTTP 422, '
            . "but returned HTTP {$insufficientStatus}. "
            . 'Response: '
            . json_encode($insufficientBody, JSON_UNESCAPED_UNICODE)
            . '. If the response is HTTP 500, check how RuntimeException is handled in OrderService.'
        );
    }

    assertSameValue(422, $insufficientStatus, 'Reject insufficient stock with HTTP 422');

    $stockAfterRejectedOrder = getStock($inventoryClient, $productId);
    assertSameValue(1, $stockAfterRejectedOrder, 'Stock remains unchanged after reservation is rejected');

    $itemCountAfterFailure = DB::table('order_items')
        ->where('product_id', $product->id)
        ->count();

    assertSameValue(
        $itemCountBeforeFailure,
        $itemCountAfterFailure,
        'A rejected order does not add order items to MySQL'
    );

    /*
 * Test 4: two concurrent orders compete for the final unit.
 */
echo "\nTest 4: Concurrent orders compete for the final unit\n";

setStock($inventoryClient, $productId, 1);

$itemCountBeforeConcurrentOrders = DB::table('order_items')
    ->where('product_id', $product->id)
    ->count();

$concurrentResponses = concurrentPostRequests(
    '/api/v1/orders',
    $customerToken,
    [
        [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ],
        [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ],
    ]
);

$concurrentStatuses = array_column($concurrentResponses, 0);
sort($concurrentStatuses);

assertSameValue(
    [201, 422],
    $concurrentStatuses,
    'Exactly one concurrent order succeeds and the other is rejected'
);

$successfulOrderResponse = null;

foreach ($concurrentResponses as [$status, $body]) {
    if ($status === 201) {
        $successfulOrderResponse = $body;
        break;
    }
}

if (! is_array($successfulOrderResponse)) {
    failTest('Could not find the successful concurrent order response.');
}

$successfulOrderData = $successfulOrderResponse['data'] ?? $successfulOrderResponse;
$concurrentOrderId = $successfulOrderData['id'] ?? null;

if (! is_numeric($concurrentOrderId)) {
    failTest('The successful concurrent order response does not contain an order ID.');
}

$stockAfterConcurrentOrders = getStock($inventoryClient, $productId);
assertSameValue(0, $stockAfterConcurrentOrders, 'The final unit is reserved only once');

$itemCountAfterConcurrentOrders = DB::table('order_items')
    ->where('product_id', $product->id)
    ->count();

assertSameValue(
    $itemCountBeforeConcurrentOrders + 1,
    $itemCountAfterConcurrentOrders,
    'Only one concurrent order adds an order item'
);

/*
 * Cancel the successful test order to restore inventory.
 */
[$cleanupStatus] = apiRequest(
    'PATCH',
    "/api/v1/orders/{$concurrentOrderId}/status",
    $adminToken,
    ['status' => Order::STATUS_CANCELLED]
);

assertSameValue(200, $cleanupStatus, 'Cancel the successful concurrent test order');

$stockAfterCleanup = getStock($inventoryClient, $productId);
assertSameValue(1, $stockAfterCleanup, 'Cancellation restores the final unit');


    echo "\nAll integration tests passed.\n";
    echo "Order ID created and then cancelled: {$orderId}\n";
    echo "Product ID used for testing: {$productId}\n";
    echo "Test data was left in the development database for review.\n";

    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, "\nThe test failed with an exception:\n");
    fwrite(STDERR, get_class($exception) . ': ' . $exception->getMessage() . "\n");
    fwrite(STDERR, $exception->getFile() . ':' . $exception->getLine() . "\n");
    exit(1);
}
