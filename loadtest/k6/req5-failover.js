import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://host.docker.internal:8000';
const TOTAL_REQUESTS = Number(__ENV.TOTAL_REQUESTS || 500);
const VUS = Number(__ENV.VUS || 10);

const successful = new Counter('successful_requests');
const failed = new Counter('failed_requests');

export const options = {
  scenarios: {
    failover_500: {
      executor: 'shared-iterations',
      vus: VUS,
      iterations: TOTAL_REQUESTS,
      maxDuration: '2m',
    },
  },
};

export default function () {
  const response = http.get(
    `${BASE_URL}/api/v1/products?per_page=10`,
    {
      headers: { Accept: 'application/json' },
      timeout: '10s',
    }
  );

  const ok = check(response, {
    'products API returns 200': (r) => r.status === 200,
  });

  if (ok) {
    successful.add(1);
  } else {
    failed.add(1);
  }

  sleep(0.5);
}

export function handleSummary(data) {
  const value = (name, key) =>
    data.metrics[name]?.values?.[key] ?? 0;

  const report = {
    expected_requests: TOTAL_REQUESTS,
    iterations: value('iterations', 'count'),
    http_requests: value('http_reqs', 'count'),
    successful_requests: value('successful_requests', 'count'),
    failed_requests: value('failed_requests', 'count'),
    http_failure_rate: value('http_req_failed', 'rate'),
    check_passes: value('checks', 'passes'),
    check_failures: value('checks', 'fails'),
  };

  return {
    stdout: JSON.stringify(report, null, 2) + '\n',
  };
}
