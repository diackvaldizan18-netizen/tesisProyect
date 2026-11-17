import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';

const source = readFileSync(new URL('../../resources/js/ingreso-abonado.js', import.meta.url), 'utf8');

class Element {
    constructor(value = '') {
        this.value = value;
        this.dataset = {};
        this.hidden = false;
        this.disabled = false;
        this.checked = false;
        this.readOnly = false;
        this.listeners = {};
        this.style = {};
        this.classList = {toggle() {}};
    }
    addEventListener(name, listener) { (this.listeners[name] ??= []).push(listener); }
    dispatchEvent(event) { for (const listener of this.listeners[event.type] ?? []) listener(event); }
    querySelector() { return null; }
    matches() { return false; }
    replaceChildren() { this.options = []; }
    add(option) { this.options.push(option); }
    get selectedOptions() { return (this.options ?? []).filter(option => option.value === this.value); }
    showModal() { this.open = true; }
    close() { this.open = false; }
}

test('DNI vigente fills the real plate, vehicle, space and subscription fields', async () => {
    for (const fixed of [false, true]) {
    const key = new Element();
    const locker = new Element();
    const keyGroup = new Element();
    keyGroup.querySelector = () => locker;
    const dni = new Element('12345678');
    dni.dataset.subscriberUrl = '/abonados/buscar-dni';
    const plate = new Element();
    const plateGroup = new Element();
    const plates = new Element();
    plates.options = [];
    const space = new Element(fixed ? '1' : '');
    space.matches = selector => fixed && selector === '[data-fixed-space]';
    space.options = [{value: '1', dataset: {types: '1', mode: 'manual'}}];
    space.querySelector = selector => selector.startsWith('option[value=') ? space.options[0] : null;
    space.add = option => space.options.push(option);
    const vehicle = new Element();
    vehicle.options = [{value: '', disabled: false}, {value: '1', disabled: !fixed}];
    space.addEventListener('change', () => {
        vehicle.options[1].disabled = !space.options[0].dataset.types.split(',').includes('1');
    });
    const subscription = new Element();
    const message = new Element();
    const monthly = new Element();
    const release = new Element();
    const submit = new Element();
    const note = new Element();
    note.textContent = 'Cobro normal';
    const sensorHelp = new Element();
    const clientLookup = new Element();
    const form = new Element();
    const byId = {
        'entry-dni': dni, 'entry-form': form, placa: plate,
        'entry-subscriber-plates': plateGroup, 'entry-subscriber-plate': plates,
        espacio_id: space, vehiculo_tipo_id: vehicle, abono_id: subscription,
        'entry-subscriber-message': message, 'entry-subscriber-rate': monthly,
        'entry-release-space': release, 'entry-submit': submit, 'entry-client': clientLookup,
    };
    const selectors = {
        '[data-entry-key]': key, '[data-entry-key-location]': keyGroup,
        '[data-entry-normal-note]': note, '[data-sensor-help]': sensorHelp,
    };
    const response = {vigente: true, abono_id: 9, cliente: 'Cliente', placas: ['ABC123'],
        placas_activas: [], espacio_id: 1, espacio_codigo: 'T01', espacio_tipos: [1],
        espacio_modo: 'manual', espacio_estado: 'libre', vehiculo_tipo_id: 1,
        tarifa_mensual: 'Mensual · S/ 270', vence: '30/11/2026', mover_url: null};
    class Option {
        constructor(text, value) { this.text = text; this.value = value; this.dataset = {}; }
    }
    runInNewContext(source, {
        document: {querySelector: selector => selectors[selector] ?? null,
            getElementById: id => byId[id] ?? null, hidden: false},
        location: {search: ''}, URLSearchParams, AbortController, Option,
        Event: class {constructor(type) {this.type = type;}},
        fetch: async () => ({ok: true, json: async () => response}),
        setInterval() {}, setTimeout() {}, clearTimeout() {},
        sessionStorage: {getItem: () => null},
    });
    await new Promise(resolve => setImmediate(resolve));

    assert.equal(locker.required, false);
    assert.equal(subscription.value, 9);
    assert.equal(space.value, '1');
    assert.equal(vehicle.value, '1');
    assert.equal(vehicle.options[1].disabled, false);
    assert.equal(plate.value, 'ABC123');
    assert.equal(plate.readOnly, true);
    assert.equal(plates.value, 'ABC123');
    assert.equal(plateGroup.hidden, true);
    assert.equal(submit.disabled, false);
    }
});

test('occupied subscriber space opens the modal and keeps the entry draft when moving', async () => {
    const dni = new Element('12345678');
    dni.dataset.subscriberUrl = '/abonados/buscar-dni';
    const plate = new Element('ABO123');
    const plates = new Element();
    plates.options = [];
    const space = new Element('');
    space.options = [];
    space.querySelector = () => null;
    space.add = option => space.options.push(option);
    const type = new Element();
    const abonado = new Element();
    const release = new Element();
    const cancel = new Element();
    const modal = new Element();
    const detail = new Element();
    const submit = new Element();
    const form = new Element();
    const stored = new Map();
    let navigation = null;
    class Option {
        constructor(text, value) { this.text = text; this.value = value; this.dataset = {}; }
    }
    const byId = {
        'entry-dni': dni, 'entry-form': form, placa: plate,
        'entry-subscriber-plates': new Element(), 'entry-subscriber-plate': plates,
        espacio_id: space, vehiculo_tipo_id: type, abono_id: abonado,
        'entry-subscriber-message': new Element(), 'entry-subscriber-rate': new Element(),
        'entry-occupied-modal': modal, 'entry-occupied-detail': detail,
        'entry-release-space': release, 'entry-release-cancel': cancel, 'entry-submit': submit,
    };
    runInNewContext(source, {
        document: {querySelector: () => null, getElementById: id => byId[id] ?? null, hidden: false},
        location: {search: '', href: 'http://localhost/admin/estadias/crear'},
        window: {location: {assign: url => { navigation = url; }}},
        URL, URLSearchParams, AbortController, Option,
        Event: class {constructor(type) { this.type = type; }},
        FormData: class {entries() { return Object.entries({dni: dni.value, placa: plate.value, espacio_id: space.value, vehiculo_tipo_id: type.value, abono_id: abonado.value}); }},
        fetch: async () => ({ok: true, json: async () => ({
            vigente: true, abono_id: 9, cliente: 'Cliente', placas: ['ABO123'], placas_activas: [],
            espacio_id: 1, espacio_codigo: 'T01', espacio_tipos: [1], espacio_modo: 'manual',
            espacio_estado: 'ocupado', ocupante_placa: 'TMP123', vehiculo_tipo_id: 1,
            tarifa_mensual: 'Mensual', vence: '30/11/2026',
            mover_url: '/admin/operacion/espacios/1?vista=mover&retorno_ingreso=1',
        })}),
        setInterval() {}, setTimeout() {}, clearTimeout() {},
        sessionStorage: {setItem: (key, value) => stored.set(key, value), getItem: key => stored.get(key) ?? null},
    });
    await new Promise(resolve => setImmediate(resolve));

    assert.equal(modal.open, true);
    assert.equal(submit.disabled, true);
    assert.equal(release.disabled, false);
    assert.match(detail.textContent, /TMP123/);
    cancel.dispatchEvent({type: 'click'});
    assert.equal(modal.open, false);
    assert.equal(plate.value, 'ABO123');
    dni.dispatchEvent({type: 'change'});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(modal.open, false);
    release.dispatchEvent({type: 'click'});
    assert.equal(navigation, '/admin/operacion/espacios/1?vista=mover&retorno_ingreso=1');
    assert.equal(JSON.parse(stored.get('parkeo-ingreso-abonado')).placa, 'ABO123');
});
