// Load test for Requirement 3. See PERFORMANCE.md for how to run it and how to read the results.
//
//   k6 run -e BASE_URL=http://localhost:8000 -e LABEL=before loadtest/k6/perf.js
//
// Needs the data from `php artisan perf:seed`. Writes loadtest/results/<LABEL>.json
// and prints p50 / p95 / p99 for each endpoint group.

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE = __ENV.BASE_URL || 'http://localhost:8000';
const LABEL = __ENV.LABEL || 'run';
const API = `${BASE}/api/v1`;
const JSON_HEADERS = { 'Content-Type': 'application/json', Accept: 'application/json' };

// The accounts that `php artisan perf:seed` creates (app/Console/Commands/PerfSeed.php).
const CUSTOMER_PASSWORD = 'Perf12345';
const ADMIN = { email: 'admin@test.com', password: 'Admin1234' };

const DURATION = __ENV.DURATION || '2m';
const BROWSE_VUS = Number(__ENV.BROWSE_VUS || 50);
const ORDER_VUS = Number(__ENV.ORDER_VUS || 10);
const CUSTOMERS = Number(__ENV.CUSTOMERS || 20);
// Admin edits per minute. Each edit clears the whole catalog cache, so keep this realistic.
const EDITS_PER_MINUTE = Number(__ENV.EDITS_PER_MINUTE || 12);

const catalogListMs = new Trend('catalog_list_ms', true);
const catalogPageMs = new Trend('catalog_show_ms', true);
const ordersListMs = new Trend('orders_list_ms', true);
const orderPageMs = new Trend('order_show_ms', true);
const editThenReadMs = new Trend('edit_then_read_ms', true);
const notModified = new Rate('http_304_share');
const staleReads = new Counter('stale_reads');

export const options = {
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(95)', 'p(99)'],
  scenarios: {
    browse_catalog: {
      executor: 'constant-vus',
      vus: BROWSE_VUS,
      duration: DURATION,
      exec: 'browseCatalog',
    },
    customer_orders: {
      executor: 'constant-vus',
      vus: ORDER_VUS,
      duration: DURATION,
      exec: 'customerOrders',
    },
    edit_then_read: {
      executor: 'constant-arrival-rate',
      rate: EDITS_PER_MINUTE,
      timeUnit: '1m',
      duration: DURATION,
      preAllocatedVUs: 2,
      maxVUs: 5,
      exec: 'editThenRead',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    checks: ['rate>0.99'],
    stale_reads: ['count<1'],
  },
};

// Runs once before the scenarios: tokens for the admin and the customers, and product ids to read.
export function setup() {
  const adminToken = login(ADMIN.email, ADMIN.password);

  const customerTokens = [];
  for (let i = 1; i <= CUSTOMERS; i++) {
    customerTokens.push(login(`perf-customer-${i}@example.test`, CUSTOMER_PASSWORD));
  }

  const productIds = [];
  for (let page = 1; page <= 5; page++) {
    const body = http.get(`${API}/products?per_page=50&page=${page}`).json();
    for (const product of body.data || []) {
      productIds.push(product.id);
    }
  }
  if (productIds.length === 0) {
    throw new Error('The catalog is empty. Run: php artisan perf:seed');
  }

  return { adminToken, customerTokens, productIds };
}

// Each virtual user keeps the ETags it has seen, the way a browser does.
const etags = {};

export function browseCatalog(data) {
  const page = 1 + Math.floor(Math.random() * 20);
  getRevalidated(`${API}/products?per_page=10&page=${page}`, catalogListMs, 'catalog_list');
  getRevalidated(`${API}/products/${pick(data.productIds)}`, catalogPageMs, 'catalog_show');
  sleep(0.1);
}

export function customerOrders(data) {
  const headers = { Authorization: `Bearer ${pick(data.customerTokens)}`, Accept: 'application/json' };

  const list = http.get(`${API}/orders?per_page=10`, { headers, tags: { name: 'orders_list' } });
  ordersListMs.add(list.timings.duration);
  check(list, { 'orders list 200': (r) => r.status === 200 });

  const first = list.status === 200 ? (list.json().data || [])[0] : undefined;
  if (first) {
    const page = http.get(`${API}/orders/${first.id}`, { headers, tags: { name: 'order_show' } });
    orderPageMs.add(page.timings.duration);
    check(page, { 'order show 200': (r) => r.status === 200 });
  }
  sleep(0.2);
}

// An admin edits a product, and the very next public read must show that edit.
export function editThenRead(data) {
  const id = pick(data.productIds);
  const name = `probe ${__VU}-${__ITER}-${Date.now()}`;

  const update = http.put(`${API}/products/${id}`, JSON.stringify({ name }), {
    headers: { ...JSON_HEADERS, Authorization: `Bearer ${data.adminToken}` },
    tags: { name: 'admin_update' },
  });
  check(update, { 'admin update 200': (r) => r.status === 200 });

  const read = http.get(`${API}/products/${id}`, { tags: { name: 'show_after_edit' } });
  editThenReadMs.add(read.timings.duration);

  const seen = read.status === 200 ? read.json().data.name : null;
  if (update.status === 200 && seen !== name) {
    staleReads.add(1);
  }
}

function getRevalidated(url, trend, name) {
  const headers = {};
  if (etags[url]) {
    headers['If-None-Match'] = etags[url];
  }

  const res = http.get(url, { headers, tags: { name } });
  trend.add(res.timings.duration);
  notModified.add(res.status === 304);
  check(res, { '200 or 304': (r) => r.status === 200 || r.status === 304 });

  const etag = header(res, 'etag');
  if (etag) {
    etags[url] = etag;
  }
  return res;
}

function login(email, password) {
  const res = http.post(`${API}/auth/login`, JSON.stringify({ email, password }), { headers: JSON_HEADERS });
  if (res.status !== 200) {
    throw new Error(`login failed for ${email} (HTTP ${res.status})`);
  }
  return res.json().access_token;
}

function header(res, name) {
  const wanted = name.toLowerCase();
  const key = Object.keys(res.headers).find((k) => k.toLowerCase() === wanted);
  return key ? res.headers[key] : null;
}

function pick(items) {
  return items[Math.floor(Math.random() * items.length)];
}

function metricValue(data, metric, key) {
  const m = data.metrics[metric];
  return m && m.values && m.values[key] !== undefined ? m.values[key] : undefined;
}

function fmt(value) {
  return value === undefined ? 'n/a'.padStart(10) : value.toFixed(1).padStart(10);
}

function table(data) {
  const rows = [
    ['catalog list (10 per page)', 'catalog_list_ms'],
    ['catalog product page', 'catalog_show_ms'],
    ['customer order list', 'orders_list_ms'],
    ['customer order page', 'order_show_ms'],
    ['admin edit, then read', 'edit_then_read_ms'],
  ];

  const lines = [
    `\nLabel: ${LABEL}`,
    `${'endpoint'.padEnd(28)}${'p50 ms'.padStart(10)}${'p95 ms'.padStart(10)}${'p99 ms'.padStart(10)}`,
  ];
  for (const [title, metric] of rows) {
    lines.push(
      `${title.padEnd(28)}${fmt(metricValue(data, metric, 'med'))}` +
        `${fmt(metricValue(data, metric, 'p(95)'))}${fmt(metricValue(data, metric, 'p(99)'))}`
    );
  }

  const stale = metricValue(data, 'stale_reads', 'count') || 0;
  const share = metricValue(data, 'http_304_share', 'rate') || 0;
  lines.push(`stale reads after an edit: ${stale}`);
  lines.push(`revalidations answered 304: ${(share * 100).toFixed(1)}%`);

  return lines.join('\n') + '\n';
}

export function handleSummary(data) {
  const report = {
    label: LABEL,
    base_url: BASE,
    duration: DURATION,
    browse_vus: BROWSE_VUS,
    order_vus: ORDER_VUS,
    edits_per_minute: EDITS_PER_MINUTE,
    metrics: data.metrics,
  };

  return {
    [`loadtest/results/${LABEL}.json`]: JSON.stringify(report, null, 2),
    stdout: table(data),
  };
}
