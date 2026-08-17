function xsrfToken(): string {
    const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return m ? decodeURIComponent(m[1]) : '';
}

/** A non-2xx response, carrying the decoded body so callers can read its flags. */
export class HttpError extends Error {
    constructor(
        message: string,
        public readonly data: Record<string, unknown>,
    ) {
        super(message);
        this.name = 'HttpError';
    }
}

/**
 * POST JSON and get JSON back, outside Inertia (for on-demand actions like the
 * AI summary). Throws HttpError(message, body) on a non-2xx response.
 */
export async function postJson<T = unknown>(url: string, body: unknown = {}): Promise<T> {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
        credentials: 'same-origin',
    });

    let data: Record<string, unknown> = {};

    try {
        data = await res.json();
    } catch {
        // no body
    }

    if (!res.ok) {
        throw new HttpError((data.message as string) || 'Nastala chyba.', data);
    }

    return data as T;
}
