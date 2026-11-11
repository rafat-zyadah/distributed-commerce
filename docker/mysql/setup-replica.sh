#!/bin/sh
# Connects db-replica to db as a GTID replica, and prepares the replica for the app's reads.
# Run by the replica-setup service in docker-compose.yml. Safe to run again.
set -eu

PRIMARY_HOST=db
REPLICA_HOST=db-replica

primary() { mysql -h "$PRIMARY_HOST" -uroot -p"$ROOT_PASSWORD" -e "$1"; }
replica() { mysql -h "$REPLICA_HOST" -uroot -p"$ROOT_PASSWORD" -e "$1"; }

echo "1/3 Replication user on the primary"
# Try the current privilege name first, then the older one if the server rejects it.
primary "
  CREATE USER IF NOT EXISTS '$REPLICATION_USER'@'%' IDENTIFIED BY '$REPLICATION_PASSWORD';
  GRANT REPLICATION REPLICA ON *.* TO '$REPLICATION_USER'@'%';
" || primary "
  CREATE USER IF NOT EXISTS '$REPLICATION_USER'@'%' IDENTIFIED BY '$REPLICATION_PASSWORD';
  GRANT REPLICATION SLAVE ON *.* TO '$REPLICATION_USER'@'%';
"

echo "2/3 Application database and read-only user on the replica"
# sql_log_bin = 0: these statements must not get GTIDs of their own. Otherwise the replica
# would hold transactions that the primary does not have.
replica "
  SET SESSION sql_log_bin = 0;
  CREATE DATABASE IF NOT EXISTS \`$APP_DATABASE\`;
  CREATE USER IF NOT EXISTS '$APP_USER'@'%' IDENTIFIED BY '$APP_PASSWORD';
  GRANT SELECT ON \`$APP_DATABASE\`.* TO '$APP_USER'@'%';
"

echo "3/3 Starting replication (GTID auto-position)"
# Forget an earlier connection so the script can run again. An error here means there was nothing to stop.
replica "STOP REPLICA;" 2>/dev/null || true
replica "RESET REPLICA ALL;" 2>/dev/null || true
replica "
  CHANGE REPLICATION SOURCE TO
    SOURCE_HOST='$PRIMARY_HOST',
    SOURCE_PORT=3306,
    SOURCE_USER='$REPLICATION_USER',
    SOURCE_PASSWORD='$REPLICATION_PASSWORD',
    SOURCE_AUTO_POSITION=1,
    GET_SOURCE_PUBLIC_KEY=1;
  START REPLICA;
"

# Wait until both replication threads are running (about 60 seconds at most).
attempt=0
status=""
while [ "$attempt" -lt 30 ]; do
  status=$(mysql -h "$REPLICA_HOST" -uroot -p"$ROOT_PASSWORD" -e "SHOW REPLICA STATUS\G")
  if echo "$status" | grep -q "Replica_IO_Running: Yes" && echo "$status" | grep -q "Replica_SQL_Running: Yes"; then
    echo "Replica is running."
    exit 0
  fi
  attempt=$((attempt + 1))
  sleep 2
done

echo "The replica did not start. Last status:" >&2
echo "$status" >&2
exit 1