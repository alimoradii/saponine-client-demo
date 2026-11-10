'use strict';

const http = require('node:http');
const { Readable } = require('node:stream');
const fs = require('node:fs');
const path = require('node:path');

const PORT = Number(process.env.PORT || 10000);
const UPSTREAM = 'https://saponin-motion-showcase.lovable.app';
const CSS = fs.readFileSync(path.join(__dirname, 'adrian.css'), 'utf8');
const JS = fs.readFileSync(path.join(__dirname, 'adrian.js'), 'utf8');
const VERSION = '2026-11-10-2';
const HOP = new Set(['connection','keep-alive','proxy-authenticate','proxy-authorization','te','trailer','transfer-encoding','upgrade']);

function send(res, status, body, headers = {}) {
  const data = Buffer.isBuffer(body) ? body : Buffer.from(String(body));
  res.writeHead(status, { 'content-length': String(data.length), ...headers });
  res.end(data);
}

function publicOrigin(req) {
  const proto = String(req.headers['x-forwarded-proto'] || 'https').split(',')[0].trim();
  return `${proto}://${req.headers.host || 'localhost'}`;
}

function upstreamHeaders(req) {
  const headers = new Headers();
  for (const key of ['accept','accept-language','cache-control','if-match','if-modified-since','if-none-match','if-range','range','user-agent']) {
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
    if (HOP.has(lower) || ['content-length','content-encoding','content-security-policy','content-security-policy-report-only'].includes(lower)) continue;
    res.setHeader(name, lower === 'location' ? value.replace(UPSTREAM, origin) : value);
  }
  res.setHeader('x-saponine-render-proxy', 'active');
}

function inject(html, origin) {
  let output = html.replaceAll(UPSTREAM, origin);
  const head = `<meta name="saponine-render-mode" content="lovable-adrian-product"><link rel="stylesheet" href="/__adrian/adrian.css?v=${VERSION}">`;
  const body = `<script src="/__adrian/adrian.js?v=${VERSION}" defer></script>`;
  output = output.includes('</head>') ? output.replace('</head>', `${head}</head>`) : head + output;
  output = output.includes('</body>') ? output.replace('</body>', `${body}</body>`) : output + body;
  return output;
}

async function proxy(req, res) {
  const incoming = new URL(req.url || '/', publicOrigin(req));
  const target = new URL(incoming.pathname + incoming.search, UPSTREAM);
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 30000);
  req.on('close', () => controller.abort());

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
      res.writeHead(upstream.status);
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

    res.writeHead(upstream.status);
    if (upstream.body) Readable.fromWeb(upstream.body).pipe(res);
    else res.end();
  } catch (error) {
    clearTimeout(timer);
    const timeout = error && error.name === 'AbortError';
    send(res, timeout ? 504 : 502, '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><title>ساپونین</title><body style="font-family:system-ui;padding:40px"><h1>ساپونین</h1><p>سرویس موقتاً در دسترس نیست.</p></body></html>', {
      'content-type': 'text/html; charset=utf-8',
      'cache-control': 'no-store',
    });
  }
}

http.createServer((req, res) => {
  if (req.url === '/__health') return send(res, 200, JSON.stringify({ ok: true, upstream: UPSTREAM, version: VERSION }), { 'content-type': 'application/json; charset=utf-8', 'cache-control': 'no-store' });
  if ((req.url || '').startsWith('/__adrian/adrian.css')) return send(res, 200, CSS, { 'content-type': 'text/css; charset=utf-8', 'cache-control': 'public, max-age=300' });
  if ((req.url || '').startsWith('/__adrian/adrian.js')) return send(res, 200, JS, { 'content-type': 'application/javascript; charset=utf-8', 'cache-control': 'public, max-age=300' });
  if (!['GET','HEAD'].includes(req.method || 'GET')) return send(res, 405, 'Method Not Allowed', { allow: 'GET, HEAD', 'content-type': 'text/plain; charset=utf-8' });
  proxy(req, res);
}).listen(PORT, '0.0.0.0', () => console.log(`Saponine Render proxy listening on ${PORT}`));
