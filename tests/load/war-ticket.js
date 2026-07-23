import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter } from 'k6/metrics';

export const options = {
  vus: Number(__ENV.VUS || 100),
  duration: __ENV.DURATION || '1m',
  thresholds: {
    http_req_failed: ['rate<0.20'],
    checkout_success: ['count<=6000'],
  },
};

const checkoutSuccess = new Counter('checkout_success');
const duplicateInvoice = new Counter('duplicate_invoice_seen');
const seenInvoices = {};

const baseUrl = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const eventSlug = __ENV.EVENT_SLUG || 'demo';
const cookie = __ENV.AUTH_COOKIE || '';

if (!/localhost|127\.0\.0\.1|\.test|staging/i.test(baseUrl) && __ENV.ALLOW_NON_LOCAL !== '1') {
  throw new Error('Refusing to run load test against a non-local/non-staging URL. Set ALLOW_NON_LOCAL=1 only for a safe staging target.');
}

export default function () {
  const headers = cookie ? { Cookie: cookie } : {};
  const eventPage = http.get(`${baseUrl}/ticket/${eventSlug}`, { headers });

  check(eventPage, {
    'event page ok': (res) => res.status === 200,
  });

  const csrf = (eventPage.body.match(/name="_token" value="([^"]+)"/) || [])[1];
  const eventUid = (eventPage.body.match(/name="event_uid" value="([^"]+)"/) || [])[1];
  const hargaIds = [...eventPage.body.matchAll(/name="tickets\[\d+\]\[harga_id\]" value="(\d+)"/g)].map((m) => m[1]);

  if (!csrf || !eventUid || hargaIds.length === 0 || !cookie) {
    sleep(1);
    return;
  }

  const selectedHarga = hargaIds[__VU % hargaIds.length];
  const payload = {
    _token: csrf,
    event_uid: eventUid,
    'tickets[0][harga_id]': selectedHarga,
    'tickets[0][quantity]': '1',
    'tickets[0][orderBy]': '1',
  };

  const checkout = http.post(`${baseUrl}/checkout`, payload, {
    headers,
    redirects: 0,
  });

  const location = checkout.headers.Location || '';
  const invoice = (location.match(/detail-ticket\/([^/]+)/) || [])[1];

  if (checkout.status >= 300 && checkout.status < 400 && location.includes('/detail-ticket/')) {
    checkoutSuccess.add(1);
  }

  if (invoice) {
    if (seenInvoices[invoice]) {
      duplicateInvoice.add(1);
    }
    seenInvoices[invoice] = true;
  }

  check(checkout, {
    'checkout did not 500': (res) => res.status < 500,
    'reservation accepted or rejected gracefully': (res) => [302, 303, 419, 422].includes(res.status),
  });

  sleep(1);
}

