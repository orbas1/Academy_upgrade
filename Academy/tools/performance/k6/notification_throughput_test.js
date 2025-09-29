import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'https://staging.api.academy.local';
const TOKEN = __ENV.TOKEN || '';
const VUS = parseInt(__ENV.VUS || '50', 10);
const DURATION = __ENV.DURATION || '10m';

export const options = {
  vus: VUS,
  duration: DURATION,
  thresholds: {
    http_req_failed: ['rate<0.02'],
    http_req_duration: ['p(95)<350'],
  },
};

function headers() {
  const value = { 'Content-Type': 'application/json' };
  if (TOKEN) {
    value.Authorization = `Bearer ${TOKEN}`;
  }
  return value;
}

export default function () {
  const payload = JSON.stringify({
    type: 'post_created',
    channel: 'push',
    actor_id: 101,
    resource_id: 202,
    metadata: { priority: 'high' },
  });
  const res = http.post(`${BASE_URL}/v1/notifications/test-dispatch`, payload, { headers: headers() });
  check(res, {
    'dispatch accepted': (r) => [200, 202].includes(r.status),
  });
  sleep(1);
}
