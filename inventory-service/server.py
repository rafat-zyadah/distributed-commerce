import os
import sqlite3
from concurrent import futures

import grpc

import inventory_pb2
import inventory_pb2_grpc


DB_PATH = os.getenv("INVENTORY_DB_PATH", "/data/inventory.sqlite3")


def connect_db():
    connection = sqlite3.connect(DB_PATH, timeout=10)
    connection.row_factory = sqlite3.Row
    return connection


def initialize_database():
    os.makedirs(os.path.dirname(DB_PATH), exist_ok=True)

    with connect_db() as connection:
        connection.execute("""
            CREATE TABLE IF NOT EXISTS inventory (
                product_id TEXT PRIMARY KEY,
                quantity INTEGER NOT NULL CHECK (quantity >= 0)
            )
        """)


class InventoryService(inventory_pb2_grpc.InventoryServiceServicer):
    def GetStock(self, request, context):
        product_id = request.product_id.strip()
        if not product_id:
            context.abort(grpc.StatusCode.INVALID_ARGUMENT, "product_id is required")

        with connect_db() as connection:
            row = connection.execute(
                "SELECT quantity FROM inventory WHERE product_id = ?",
                (product_id,),
            ).fetchone()

        return inventory_pb2.StockReply(
            product_id=product_id,
            quantity=row["quantity"] if row else 0,
        )

    def SetStock(self, request, context):
        product_id = request.product_id.strip()

        if not product_id:
            context.abort(grpc.StatusCode.INVALID_ARGUMENT, "product_id is required")
        if request.quantity < 0:
            context.abort(grpc.StatusCode.INVALID_ARGUMENT, "quantity cannot be negative")

        with connect_db() as connection:
            connection.execute(
                """
                INSERT INTO inventory (product_id, quantity)
                VALUES (?, ?)
                ON CONFLICT(product_id)
                DO UPDATE SET quantity = excluded.quantity
                """,
                (product_id, request.quantity),
            )

        return inventory_pb2.StockReply(
            product_id=product_id,
            quantity=request.quantity,
        )

    def ReserveStock(self, request, context):
        product_id = request.product_id.strip()

        if not product_id:
            context.abort(grpc.StatusCode.INVALID_ARGUMENT, "product_id is required")
        if request.quantity <= 0:
            context.abort(grpc.StatusCode.INVALID_ARGUMENT, "quantity must be greater than zero")

        # BEGIN IMMEDIATE serializes competing stock updates in SQLite.
        with connect_db() as connection:
            connection.execute("BEGIN IMMEDIATE")
            cursor = connection.execute(
                """
                UPDATE inventory
                SET quantity = quantity - ?
                WHERE product_id = ? AND quantity >= ?
                """,
                (request.quantity, product_id, request.quantity),
            )

            if cursor.rowcount == 0:
                row = connection.execute(
                    "SELECT quantity FROM inventory WHERE product_id = ?",
                    (product_id,),
                ).fetchone()

                if row is None:
                    context.abort(grpc.StatusCode.NOT_FOUND, "product inventory not found")
                context.abort(grpc.StatusCode.FAILED_PRECONDITION, "insufficient stock")

            row = connection.execute(
                "SELECT quantity FROM inventory WHERE product_id = ?",
                (product_id,),
            ).fetchone()

        return inventory_pb2.StockReply(
            product_id=product_id,
            quantity=row["quantity"],
        )

    def ReleaseStock(self, request, context):
        product_id = request.product_id.strip()

        if not product_id:
            context.abort(grpc.StatusCode.INVALID_ARGUMENT, "product_id is required")
        if request.quantity <= 0:
            context.abort(grpc.StatusCode.INVALID_ARGUMENT, "quantity must be greater than zero")

        with connect_db() as connection:
            connection.execute("BEGIN IMMEDIATE")
            connection.execute(
                """
                INSERT INTO inventory (product_id, quantity)
                VALUES (?, ?)
                ON CONFLICT(product_id)
                DO UPDATE SET quantity = quantity + excluded.quantity
                """,
                (product_id, request.quantity),
            )
            row = connection.execute(
                "SELECT quantity FROM inventory WHERE product_id = ?",
                (product_id,),
            ).fetchone()

        return inventory_pb2.StockReply(
            product_id=product_id,
            quantity=row["quantity"],
        )


def serve():
    initialize_database()

    server = grpc.server(futures.ThreadPoolExecutor(max_workers=10))
    inventory_pb2_grpc.add_InventoryServiceServicer_to_server(
        InventoryService(),
        server,
    )

    server.add_insecure_port("[::]:50051")
    server.start()
    print("Inventory gRPC service listening on port 50051", flush=True)
    server.wait_for_termination()


if __name__ == "__main__":
    serve()
