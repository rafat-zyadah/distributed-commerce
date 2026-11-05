<?php

namespace Inventory;

use Grpc\BaseStub;
use Grpc\UnaryCall;

class InventoryServiceClient extends BaseStub
{
    public function GetStock(
        GetStockRequest $request,
        array $metadata = [],
        array $options = []
    ): UnaryCall {
        return $this->_simpleRequest(
            '/inventory.InventoryService/GetStock',
            $request,
            [StockReply::class, 'decode'],
            $metadata,
$options

        );
    }

    public function SetStock(
        SetStockRequest $request,
        array $metadata = [],
        array $options = []
    ): UnaryCall {
        return $this->_simpleRequest(
            '/inventory.InventoryService/SetStock',
            $request,
            [StockReply::class, 'decode'],
            $metadata,
$options

        );
    }

    public function ReserveStock(
        ChangeStockRequest $request,
        array $metadata = [],
        array $options = []
    ): UnaryCall {
        return $this->_simpleRequest(
            '/inventory.InventoryService/ReserveStock',
            $request,
            [StockReply::class, 'decode'],
            $metadata,
$options

        );
    }

    public function ReleaseStock(
        ChangeStockRequest $request,
        array $metadata = [],
        array $options = []
    ): UnaryCall {
        return $this->_simpleRequest(
            '/inventory.InventoryService/ReleaseStock',
            $request,
            [StockReply::class, 'decode'],
            $metadata,
$options

        );
    }
}
