import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'https://staging.api.academy.local';
const TOKEN = __ENV.TOKEN || '';

const feedLatency = new Trend('community_feed_capacity_latency', true);
const writeLatency = new Trend('community_feed_write_latency', true);

export const options = {
  scenarios: {
    feed_reads: {
      executor: 'ramping-arrival-rate',
      startRate: 100,
      timeUnit: '1s',
      preAllocatedVUs: 600,
      maxVUs: 800,
      stages: [
        { target: 250, duration: '1m' },
        { target: 500, duration: '4m' },
        { target: 0, duration: '1m' },
      ],
      exec: 'readFeed',
    },
    feed_writes: {
      executor: 'ramping-arrival-rate',
      startRate: 20,
      timeUnit: '1s',
      preAllocatedVUs: 180,
      maxVUs: 240,
      stages: [
        { target: 60, duration: '1m' },
        { target: 100, duration: '4m' },
        { target: 0, duration: '1m' },
      ],
      exec: 'writePost',
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<350', 'p(99)<600'],
    http_req_failed: ['rate<0.005'],
    community_feed_capacity_latency: ['p(95)<240'],
    community_feed_write_latency: ['p(95)<420'],
  },
};

function authHeaders() {
  const headers = { 'Content-Type': 'application/json' };
  if (TOKEN) {
    headers.Authorization = `Bearer ${TOKEN}`;
  }
  return headers;
}

export function setup() {
  const response = http.get(`${BASE_URL}/v1/communities?per_page=5`, {
    headers: authHeaders(),
  });

  check(response, {
    'bootstrap communities status 200': (r) => r.status === 200,
  });

  const communities = response.json('data') || [];
  if (!Array.isArray(communities) || communities.length === 0) {
    throw new Error('No communities returned to run capacity test');
  }

  return { communities };
}

export function readFeed(data) {
  const community = data.communities[Math.floor(Math.random() * data.communities.length)];
  if (!community) {
    sleep(1);
    return;
  }

  const res = http.get(`${BASE_URL}/v1/communities/${community.id}/feed?filter=new&page_size=20`, {
    headers: authHeaders(),
    tags: { scenario: 'read' },
  });

  feedLatency.add(res.timings.duration);
  check(res, {
    'feed read ok': (r) => r.status === 200,
    'feed payload array': (r) => Array.isArray(r.json('data')),
  });

  sleep(0.2);
}

export function writePost(data) {
  const community = data.communities[Math.floor(Math.random() * data.communities.length)];
  if (!community) {
    sleep(1);
    return;
  }

  const payload = JSON.stringify({
    title: `Capacity post ${Date.now()}`,
    body_md: 'Automated capacity test payload from k6.',
    visibility: 'community',
  });

  const res = http.post(`${BASE_URL}/v1/communities/${community.id}/posts`, payload, {
    headers: authHeaders(),
    tags: { scenario: 'write' },
  });

  writeLatency.add(res.timings.duration);
  check(res, {
    'write accepted': (r) => [200, 201, 202].includes(r.status),
  });

  sleep(0.5);
}
