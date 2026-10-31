export function ticketQrUrl(raw, template, origin) {
    try {
        const url = new URL(raw);
        const expected = new URL(template, origin);
        const escape = text => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const pattern = new RegExp('^' + expected.pathname.split('__TICKET__').map(escape).join('[1-9][0-9]*') + '$');
        if (!['http:', 'https:'].includes(url.protocol) || url.origin !== origin || url.username || url.password || url.hash) return null;
        if (!pattern.test(url.pathname) || !/^[a-f0-9]{64}$/.test(url.searchParams.get('signature') ?? '')) return null;
        return url.href;
    } catch { return null; }
}
