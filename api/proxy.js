'use strict';

const fs = require('node:fs');
const path = require('node:path');
const { Readable } = require('node:stream');

const UPSTREAM = 'https://saponin-motion-showcase.lovable.app';
const CSS = fs.readFileSync(path.join(process.cwd(), 'render-proxy', 'adrian.css'), 'utf8');
const JS = fs.readFileSync(path.join(process.cwd(), 'render-proxy', 'adrian.js'), 'utf8');
const VERSION = '2026-11-10-3';
const HOP_BY_HOP = new Set([
  'connection',
  'keep-alive',
  'proxy-authenticate',
  'proxy-authorization',
  'te',
  'trailer',
  'transfer-encoding',
  'upgrade',
]);

function send(res, status, body, headers = {}) {
  const data = Buffer.isBuffer(body) ? body : Buffer.from(String(body));
  res.statusCode = status;
  for (const [name, value] of Object.entries(headers)) res.setHeader(name, value);
  res.setHeader('content-length', String(data.length));
  res.end(data);
}

function publicOrigin(req) {
  const proto = String(req.headers['x-forwarded-proto'] || 'https').split(',')[0].trim();
  const host = String(req.headers['x-forwarded-host'] || req.headers.host || '').split(',')[0].trim();
  return `${proto}://${host}`;
}

function requestedPath(req) {
  const raw = req.query && req.query.__proxy_path;
  if (Array.isArray(raw)) return raw.join('/');
  return typeof raw === 'string' ? raw : '';
}

function requestedSearch(req) {
  const params = new URLSearchParams();
  const query = req.query || {};
  for (const [key, value] of Object.entries(query)) {
    if (key === '__proxy_path' || value == null) continue;
    if (Array.isArray(value)) value.forEach((item) => params.append(key, String(item)));
    else params.append(key, String(value));
  }
  const output = params.toString();
  return output ? `?${output}` : '';
}

function upstreamHeaders(req) {
  const headers = new Headers();
  for (const key of [
    'accept',
    'accept-language',
    'cache-control',
    'if-match',
    'if-modified-since',
    'if-none-match',
    'if-range',
    'range',
    'user-agent',
  ]) {
    const value = req.headers[key];
    if (value) headers.set(key, Array.isArray(value) ? value.join(', ') : value);
  }
  headers.set('accept-encoding', 'identity');
  headers.set('referer', `${UPSTREAM}/`);
  return headers;
}

function copyHeaders(upstream, res, req) {
  const origin = publicOrigin(req);
  for (const [name, value] of upstream.headers.entries()) {
    const lower = name.toLowerCase();
    if (
      HOP_BY_HOP.has(lower) ||
      ['content-length', 'content-encoding', 'content-security-policy', 'content-security-policy-report-only'].includes(lower)
    ) continue;
    res.setHeader(name, lower === 'location' ? value.replace(UPSTREAM, origin) : value);
  }
  res.setHeader('x-saponine-vercel-proxy', 'active');
}

function inject(html, origin) {
  let output = html.replaceAll(UPSTREAM, origin);
  const head = `<meta name="saponine-host-mode" content="vercel-lovable-adrian-product"><link rel="stylesheet" href="/__adrian/adrian.css?v=${VERSION}">`;
  const body = `<script src="/__adrian/adrian.js?v=${VERSION}" defer></script>`;
  output = output.includes('</head>') ? output.replace('</head>', `${head}</head>`) : head + output;
  output = output.includes('</body>') ? output.replace('</body>', `${body}</body>`) : output + body;
  return output;
}

async function proxy(req, res) {
  const relativePath = requestedPath(req);
  const pathname = `/${relativePath}`.replace(/\/+/g, '/');
  const target = new URL(pathname + requestedSearch(req), UPSTREAM);
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 25000);

  try {
    const upstream = await fetch(target, {
      method: req.method,
      headers: upstreamHeaders(req),
      redirect: 'manual',
      signal: controller.signal,
    });
    clearTimeout(timer);
    copyHeaders(upstream, res, req);
    const type = upstream.headers.get('content-type') || '';

    if (req.method === 'HEAD' || upstream.status === 204 || upstream.status === 304) {
      res.statusCode = upstream.status;
      res.end();
      return;
    }

    if (type.includes('text/html')) {
      const output = inject(await upstream.text(), publicOrigin(req));
      send(res, upstream.status, output, {
        'content-type': 'text/html; charset=utf-8',
        'cache-control': 'no-cache, no-store, must-revalidate',
      });
      return;
    }

    res.statusCode = upstream.status;
    if (upstream.body) Readable.fromWeb(upstream.body).pipe(res);
    else res.end();
  } catch (error) {
    clearTimeout(timer);
    const timeout = error && error.name === 'AbortError';
    send(
      res,
      timeout ? 504 : 502,
      '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><title>ساپونین</title><body style="font-family:system-ui;padding:40px"><h1>ساپونین</h1><p>سرویس موقتاً در دسترس نیست.</p></body></html>',
      {
        'content-type': 'text/html; charset=utf-8',
        'cache-control': 'no-store',
      },
    );
  }
}

module.exports = async function handler(req, res) {
  const pathName = `/${requestedPath(req)}`.replace(/\/+/g, '/');

  if (pathName === '/__health') {
    return send(
      res,
      200,
      JSON.stringify({ ok: true, upstream: UPSTREAM, version: VERSION, host: 'vercel' }),
      { 'content-type': 'application/json; charset=utf-8', 'cache-control': 'no-store' },
    );
  }

  if (pathName === '/__adrian/adrian.css') {
    return send(res, 200, CSS, {
      'content-type': 'text/css; charset=utf-8',
      'cache-control': 'public, max-age=300',
    });
  }

  if (pathName === '/__adrian/adrian.js') {
    return send(res, 200, JS, {
      'content-type': 'application/javascript; charset=utf-8',
      'cache-control': 'public, max-age=300',
    });
  }

  if (!['GET', 'HEAD'].includes(req.method || 'GET')) {
    return send(res, 405, 'Method Not Allowed', {
      allow: 'GET, HEAD',
      'content-type': 'text/plain; charset=utf-8',
    });
  }

  return proxy(req, res);
};
