import { API_URL } from './config';
import { useAuth } from './auth';

export class ApiError extends Error {
  constructor(message: string, public status: number, public offline = false) {
    super(message);
  }
}

type Options = { method?: 'GET' | 'POST'; json?: unknown; form?: FormData; timeoutMs?: number };

// One place for every request: base URL, the bearer token, timeouts, and honest errors.
// Render's free plan can take ~50 s to wake, so the default timeout is generous.
export async function api<T = any>(path: string, { method, json, form, timeoutMs = 60000 }: Options = {}): Promise<T> {
  const token = useAuth.getState().token;
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;
  if (json !== undefined) headers['Content-Type'] = 'application/json';

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  let res: Response;
  try {
    res = await fetch(API_URL + path, {
      method: method ?? (json !== undefined || form ? 'POST' : 'GET'),
      headers,
      body: form ?? (json !== undefined ? JSON.stringify(json) : undefined),
      signal: controller.signal,
    });
  } catch (e: any) {
    const timedOut = e?.name === 'AbortError';
    throw new ApiError(timedOut ? 'The server is taking too long. Try again in a moment.' : 'No connection. Check your internet and try again.', 0, true);
  } finally {
    clearTimeout(timer);
  }

  const text = await res.text();
  let body: any = null;
  try {
    body = text ? JSON.parse(text) : null;
  } catch {
    body = null;
  }

  if (res.status === 401 && token) {
    // The token was revoked or the account is gone: sign out locally.
    useAuth.getState().signOut();
  }
  const failed = !res.ok || body?.status === 'error' || body?.success === false;
  if (failed) {
    throw new ApiError(body?.message || body?.error || `Something went wrong (${res.status}).`, res.status);
  }
  return body as T;
}
