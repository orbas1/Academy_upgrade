import http from 'k6/http';
import { Trend, Rate, Counter } from 'k6/metrics';
import { sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL ?? 'http://localhost';
const API_PATH = __ENV.API_PATH ?? '/api/v1/me/profile-activity';
const PAGE_SIZE = Number(__ENV.PAGE_SIZE ?? 50);
const COMMUNITY_ID = __ENV.COMMUNITY_ID ? Number(__ENV.COMMUNITY_ID) : null;
const AUTH_TOKEN = __ENV.API_TOKEN ?? '';
const SLEEP_SECONDS = Number(__ENV.SLEEP_SECONDS ?? 0.25);

const requestDuration = new Trend('profile_activity_duration', true);
const requestFailures = new Rate('profile_activity_errors');
const httpRequests = new Counter('profile_activity_requests');

export const options = {
  scenarios: {
    profile_activity: {
      executor: 'ramping-arrival-rate',
      startRate: Number(__ENV.START_RATE ?? 20),
      timeUnit: '1s',
      preAllocatedVUs: Number(__ENV.PREALLOCATED_VUS ?? 50),
      maxVUs: Number(__ENV.MAX_VUS ?? 200),
      stages: [
        { target: Number(__ENV.WARMUP_RATE ?? 50), duration: __ENV.WARMUP_DURATION ?? '2m' },
        { target: Number(__ENV.PEAK_RATE ?? 120), duration: __ENV.PEAK_DURATION ?? '5m' },
        { target: Number(__ENV.COOLDOWN_RATE ?? 0), duration: __ENV.COOLDOWN_DURATION ?? '2m' },
      ],
    },
  },
  thresholds: {
    profile_activity_duration: ['p(95)<800', 'max<2000'],
    profile_activity_errors: ['rate<0.01'],
    http_req_failed: ['rate<0.01'],
  },
  summaryTrendStats: ['avg', 'min', 'max', 'p(90)', 'p(95)', 'p(99)'],
};

let cursor = null;

export default function profileActivityScenario() {
  const params = {
    headers: {
      Accept: 'application/json',
      Authorization: AUTH_TOKEN !== '' ? `Bearer ${AUTH_TOKEN}` : undefined,
    },
    tags: {
      endpoint: 'profile-activity',
    },
  };

  const queryParts = [`per_page=${PAGE_SIZE}`];
  if (COMMUNITY_ID !== null && !Number.isNaN(COMMUNITY_ID)) {
    queryParts.push(`community_id=${COMMUNITY_ID}`);
  }
  if (cursor) {
    queryParts.push(`cursor=${encodeURIComponent(cursor)}`);
  }

  const response = http.get(`${BASE_URL}${API_PATH}?${queryParts.join('&')}`, params);
  httpRequests.add(1);
  requestDuration.add(response.timings.duration);

  if (response.status !== 200) {
    requestFailures.add(1);
    sleep(SLEEP_SECONDS);
    return;
  }

  requestFailures.add(0);

  try {
    const payload = response.json();
    cursor = payload && payload.next_cursor ? payload.next_cursor : null;
  } catch (error) {
    requestFailures.add(1);
    cursor = null;
  }

  sleep(SLEEP_SECONDS);
}
