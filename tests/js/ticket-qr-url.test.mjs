import test from 'node:test';
import assert from 'node:assert/strict';
import { ticketQrUrl } from '../../resources/js/ticket-qr-url.js';
const origin = 'https://parkeo.example';
const template = `${origin}/admin/estadias/__TICKET__/escanear`;
const signature = 'a'.repeat(64);
test('accepts signed same-site ticket URLs, including a base directory', () => {
    const url = `${origin}/admin/estadias/42/escanear?signature=${signature}`;
    assert.equal(ticketQrUrl(url, template, origin), url);
    const nested = `${origin}/parkeo/admin/estadias/42/escanear?signature=${signature}`;
    assert.equal(ticketQrUrl(nested, template.replace('/admin/', '/parkeo/admin/'), origin), nested);
});
test('rejects external links, credentials, unrelated QR codes and missing signatures', () => {
    for (const url of [
        'javascript:alert(1)', 'ABC123', `${origin}/admin/estadias/42/escanear`,
        `https://other.example/admin/estadias/42/escanear?signature=${signature}`,
        `${origin}/admin/reservas/42/escanear?signature=${signature}`,
        `${origin}/admin/estadias/0/escanear?signature=${signature}`,
        `https://name:password@parkeo.example/admin/estadias/42/escanear?signature=${signature}`,
        `${origin}/admin/estadias/42/escanear?signature=${signature}#fragment`,
        `${origin}/admin/estadias/42/escanear?signature=invalid`,
    ]) assert.equal(ticketQrUrl(url, template, origin), null, url);
});
