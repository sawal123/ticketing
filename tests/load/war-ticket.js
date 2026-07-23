import http from "k6/http";
import { check, fail, sleep } from "k6";
import { Counter, Rate } from "k6/metrics";

export const options = {
    vus: Number(__ENV.VUS || 10),
    duration: __ENV.DURATION || "30s",
    thresholds: {
        http_req_failed: ["rate<0.02"],
        checks: ["rate>0.95"],
        server_error: ["rate<0.01"],
        unexpected_response: ["rate<0.02"],
        http_req_duration: ["p(95)<2000", "p(99)<5000"],
    },
};

const checkoutAccepted = new Counter("checkout_accepted");
const checkoutSoldOut = new Counter("checkout_sold_out");
const csrfError = new Rate("csrf_error");
const validationError = new Rate("validation_error");
const rateLimited = new Rate("rate_limited");
const serverError = new Rate("server_error");
const unexpectedResponse = new Rate("unexpected_response");

const baseUrl = (__ENV.BASE_URL || "http://127.0.0.1:8000").replace(/\/$/, "");
const eventSlug = __ENV.EVENT_SLUG || "demo";
const cookie = __ENV.AUTH_COOKIE || "";
let warnedSingleCookie = false;

if (
    !/localhost|127\.0\.0\.1|\.test|staging/i.test(baseUrl) &&
    __ENV.ALLOW_NON_LOCAL !== "1"
) {
    throw new Error(
        "Refusing to run load test against a non-local/non-staging URL. Set ALLOW_NON_LOCAL=1 only for a safe staging target.",
    );
}

export function setup() {
    if (!cookie) {
        throw new Error(
            "AUTH_COOKIE is required. This smoke-test script must run as an authenticated test user.",
        );
    }
}

export default function () {
    if (!warnedSingleCookie) {
        console.warn(
            "AUTH_COOKIE tunggal aktif: ini smoke-test authenticated flow, bukan simulasi banyak pembeli unik.",
        );
        warnedSingleCookie = true;
    }

    const eventUrl = `${baseUrl}/ticket/${eventSlug}`;
    const headers = {
        Cookie: cookie,
        Referer: eventUrl,
    };

    const eventPage = http.get(eventUrl, { headers });
    const eventPageOk = check(eventPage, {
        "event page ok": (res) => res.status === 200,
    });

    if (!eventPageOk) {
        recordRates({
            csrf: 0,
            validation: 0,
            rateLimited: 0,
            server: eventPage.status >= 500 ? 1 : 0,
            unexpected: 1,
        });
        fail(
            `Event page unavailable or unexpected status: ${eventPage.status}`,
        );
    }

    const csrf = (eventPage.body.match(/name="_token" value="([^"]+)"/) ||
        [])[1];
    const eventUid = (eventPage.body.match(
        /name="event_uid" value="([^"]+)"/,
    ) || [])[1];
    const hargaIds = [
        ...eventPage.body.matchAll(
            /name="tickets\[\d+\]\[harga_id\]" value="(\d+)"/g,
        ),
    ].map((m) => m[1]);

    if (!csrf) {
        recordRates({
            csrf: 1,
            validation: 0,
            rateLimited: 0,
            server: 0,
            unexpected: 0,
        });
        fail("CSRF token not found on event page.");
    }

    if (!eventUid) {
        recordRates({
            csrf: 0,
            validation: 0,
            rateLimited: 0,
            server: 0,
            unexpected: 1,
        });
        fail("event_uid not found on event page.");
    }

    if (hargaIds.length === 0) {
        recordRates({
            csrf: 0,
            validation: 0,
            rateLimited: 0,
            server: 0,
            unexpected: 1,
        });
        fail(
            "No harga_id found on event page. Ensure the staging event has active tickets.",
        );
    }

    const selectedHarga = hargaIds[(__VU - 1) % hargaIds.length];
    const payload = {
        _token: csrf,
        event_uid: eventUid,
        "tickets[0][harga_id]": selectedHarga,
        "tickets[0][quantity]": "1",
        "tickets[0][orderBy]": "1",
    };

    const checkout = http.post(`${baseUrl}/checkout`, payload, {
        headers,
        redirects: 0,
    });

    classifyCheckoutResponse(checkout, headers);
    sleep(1);
}

function classifyCheckoutResponse(response, headers) {
    const rates = {
        csrf: 0,
        validation: 0,
        rateLimited: 0,
        server: 0,
        unexpected: 0,
    };
    const location =
        response.headers.Location || response.headers.location || "";
    const accepted =
        [302, 303].includes(response.status) &&
        location.includes("/detail-ticket/");

    if (accepted) {
        checkoutAccepted.add(1);
        recordRates(rates);
        check(response, {
            "checkout accepted": () => true,
        });
        return;
    }

    if ([302, 303].includes(response.status)) {
        if (isSoldOutRedirect(location, headers)) {
            checkoutSoldOut.add(1);
            recordRates(rates);
            check(response, {
                "checkout sold out gracefully": () => true,
            });
            return;
        }

        rates.unexpected = 1;
        recordRates(rates);
        check(response, {
            "unexpected redirect is failure": () => false,
        });
        return;
    }

    if (response.status === 419) {
        rates.csrf = 1;
        recordRates(rates);
        check(response, {
            "csrf error is failure": () => false,
        });
        return;
    }

    if (response.status === 422) {
        rates.validation = 1;
        recordRates(rates);
        check(response, {
            "validation error is failure": () => false,
        });
        return;
    }

    if (response.status === 429) {
        rates.rateLimited = 1;
        recordRates(rates);
        check(response, {
            "rate limited is failure": () => false,
        });
        return;
    }

    if (response.status >= 500) {
        rates.server = 1;
        recordRates(rates);
        check(response, {
            "server error is failure": () => false,
        });
        return;
    }

    rates.unexpected = 1;
    recordRates(rates);
    check(response, {
        "unexpected response is failure": () => false,
    });
}

function recordRates(rates) {
    csrfError.add(rates.csrf);
    validationError.add(rates.validation);
    rateLimited.add(rates.rateLimited);
    serverError.add(rates.server);
    unexpectedResponse.add(rates.unexpected);
}

function isSoldOutRedirect(location, headers) {
    if (!location) {
        return false;
    }

    const redirectUrl = location.startsWith("http")
        ? location
        : `${baseUrl}${location.startsWith("/") ? "" : "/"}${location}`;
    const redirected = http.get(redirectUrl, { headers });
    const body = String(redirected.body || "").toLowerCase();

    return (
        redirected.status === 200 &&
        (body.includes("stok tiket") ||
            body.includes("baru saja habis") ||
            body.includes("sold out") ||
            body.includes("tidak mencukupi") ||
            body.includes("tiket habis"))
    );
}

