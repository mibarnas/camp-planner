function xsrfToken(): string {
    const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return m ? decodeURIComponent(m[1]) : '';
}

/**
 * POST JSON and get JSON back, outside Inertia (for on-demand actions like the
 * AI summary). Throws Error(message) on a non-2xx response.
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
        throw new Error((data.message as string) || 'Nastala chyba.');
    }

    return data as T;
}
