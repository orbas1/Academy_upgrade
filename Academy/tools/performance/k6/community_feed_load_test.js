import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'https://staging.api.academy.local';
const TOKEN = __ENV.TOKEN || '';
const VUS = parseInt(__ENV.VUS || '120', 10);
const DURATION = __ENV.DURATION || '3m';
const RAMP_UP = __ENV.RAMP_UP || '1m';
const RAMP_DOWN = __ENV.RAMP_DOWN || '1m';

export const options = {
  scenarios: {
    steady_feed: {
      executor: 'ramping-arrival-rate',
      startRate: 40,
      timeUnit: '1s',
      preAllocatedVUs: VUS,
      maxVUs: VUS + 40,
      stages: [
        { target: 80, duration: RAMP_UP },
        { target: 120, duration: DURATION },
        { target: 40, duration: RAMP_DOWN },
      ],
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<250', 'p(99)<400'],
    http_req_failed: ['rate<0.01'],
    'community_feed_time{type:list}': ['p(95)<220'],
  },
};

const feedTrend = new Trend('community_feed_time', true);
const engagementRate = new Rate('community_engagement_success');

function authHeaders() {
  const headers = { 'Content-Type': 'application/json' };
  if (TOKEN) {
    headers.Authorization = `Bearer ${TOKEN}`;
  }
  return headers;
}

function fetchCommunities() {
  const res = http.get(`${BASE_URL}/v1/communities`, { headers: authHeaders() });
  feedTrend.add(res.timings.duration, { type: 'list' });
  const ok = check(res, {
    'communities status is 200': (r) => r.status === 200,
    'communities payload has data': (r) => r.json('data')?.length >= 1,
  });
  if (!ok) {
    engagementRate.add(false);
  }
  return res.json('data') || [];
}

function fetchFeed(community) {
  const params = {
    headers: authHeaders(),
    tags: { community: community.slug || community.id },
  };
  const res = http.get(`${BASE_URL}/v1/communities/${community.id}/feed?filter=new`, params);
  feedTrend.add(res.timings.duration, { type: 'feed' });
  const ok = check(res, {
    'feed status is 200': (r) => r.status === 200,
    'feed returns posts array': (r) => Array.isArray(r.json('data')),
  });
  engagementRate.add(ok);
  return res;
}

function createPost(community) {
  const payload = JSON.stringify({
    title: `Load test post ${Date.now()}`,
    body: 'Automated load test content to measure write path latency.',
    visibility: 'community',
  });
  const res = http.post(`${BASE_URL}/v1/communities/${community.id}/posts`, payload, {
    headers: authHeaders(),
  });
  check(res, {
    'post create accepted': (r) => [200, 201, 202].includes(r.status),
  });
  engagementRate.add(res.status < 400);
  return res;
}

export function setup() {
  return { communities: fetchCommunities().slice(0, 5) };
}

export default function (data) {
  group('community feed read/write', () => {
    const target = data.communities[Math.floor(Math.random() * data.communities.length)];
    if (!target) {
      return;
    }
    fetchFeed(target);
    if (__ITER % 5 === 0) {
      // throttle writes to avoid flooding moderation queue
      createPost(target);
    }
  });
  sleep(1);
}

export function teardown() {
  console.log(`engagement success rate: ${engagementRate.rate}`);
}
