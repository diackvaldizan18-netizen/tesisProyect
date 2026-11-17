const mapa = document.querySelector('[data-admin-refresh]');
if (mapa) {
    const lista = document.querySelector('[data-active-vehicles]');
    const filas = lista?.querySelector('[data-active-rows]');
    const buscar = lista?.querySelector('[data-active-search]');
    const tipo = lista?.querySelector('[data-active-type]');
    const tarifa = lista?.querySelector('[data-active-rate]');
    const tiempo = entrada => `${Math.max(0, Math.floor((Date.now() - new Date(entrada).getTime()) / 60000))} min`;
    const actualizarTiempos = () => filas?.querySelectorAll('[data-entry-time]').forEach(celda => {
        celda.textContent = tiempo(celda.dataset.entryTime);
    });
    const filtrarLista = () => {
        if (!filas) return;
        let visibles = 0;
        filas.querySelectorAll('tr[data-plate]').forEach(fila => {
            const coincide = fila.dataset.plate.toLocaleLowerCase().includes(buscar.value.trim().toLocaleLowerCase())
                && (!tipo.value || fila.dataset.type === tipo.value)
                && (!tarifa.value || fila.dataset.rate === tarifa.value);
            fila.hidden = !coincide;
            if (coincide) visibles++;
        });
        const vacio = filas.querySelector('[data-active-empty]');
        if (vacio) vacio.hidden = visibles > 0;
    };
    const opciones = (selector, valores) => {
        const elegido = selector.value;
        selector.replaceChildren(selector.options[0]);
        [...new Set([...valores, ...(elegido ? [elegido] : [])])].sort((a, b) => a.localeCompare(b, 'es')).forEach(valor => {
            const opcion = document.createElement('option');
            opcion.value = opcion.textContent = valor;
            selector.append(opcion);
        });
        selector.value = elegido;
    };
    const celda = (fila, etiqueta, valor) => {
        const td = document.createElement('td');
        td.dataset.label = etiqueta;
        td.textContent = valor;
        fila.append(td);
        return td;
    };
    const actualizarLista = vehiculos => {
        if (!filas || !Array.isArray(vehiculos)) return;
        opciones(tipo, vehiculos.map(vehiculo => vehiculo.tipo));
        opciones(tarifa, vehiculos.map(vehiculo => vehiculo.tarifa));
        const nuevas = document.createDocumentFragment();
        vehiculos.forEach(vehiculo => {
            const fila = document.createElement('tr');
            fila.dataset.plate = vehiculo.placa;
            fila.dataset.type = vehiculo.tipo;
            fila.dataset.rate = vehiculo.tarifa;
            const placa = celda(fila, 'Placa', '');
            const fuerte = document.createElement('strong');
            fuerte.textContent = vehiculo.placa;
            placa.append(fuerte);
            celda(fila, 'Vehículo', vehiculo.tipo);
            celda(fila, 'Espacio', vehiculo.espacio);
            celda(fila, 'Tarifa', vehiculo.tarifa);
            celda(fila, 'Entrada', vehiculo.entrada);
            const transcurrido = celda(fila, 'Tiempo', tiempo(vehiculo.entrada_iso));
            transcurrido.dataset.entryTime = vehiculo.entrada_iso;
            celda(fila, 'Importe acumulado', `S/ ${vehiculo.importe}`);
            const estado = celda(fila, 'Estado', '');
            const insignia = document.createElement('span');
            insignia.className = 'badge';
            insignia.textContent = vehiculo.estado;
            estado.append(insignia);
            const accion = celda(fila, 'Acción', '');
            const enlace = document.createElement('a');
            enlace.className = 'btn btn-secondary';
            enlace.href = vehiculo.url;
            enlace.textContent = 'Ver';
            accion.append(enlace);
            nuevas.append(fila);
        });
        const vacio = document.createElement('tr');
        vacio.dataset.activeEmpty = '';
        celda(vacio, '', 'No hay vehículos para estos filtros.').colSpan = 9;
        nuevas.append(vacio);
        filas.replaceChildren(nuevas);
        lista.querySelector('[data-active-count]').textContent = vehiculos.length;
        filtrarLista();
    };
    [buscar, tipo, tarifa].forEach(control => control?.addEventListener(control === buscar ? 'input' : 'change', filtrarLista));
    filtrarLista();
    let actualizando = false;
    const refrescar = async () => {
        if (actualizando || document.hidden) return;
        actualizando = true;
        try {
            const respuesta = await fetch(mapa.dataset.adminRefresh, {
                headers: {Accept: 'application/json'}, cache: 'no-store',
            });
            if (!respuesta.ok) throw new Error('No se pudo consultar el mapa.');
            const datos = await respuesta.json();
            const estados = new Map(datos.espacios.map(espacio => [String(espacio.id), espacio]));
            mapa.querySelectorAll('[data-space]').forEach(elemento => {
                const estado = estados.get(elemento.dataset.space);
                if (!estado) return;
                elemento.dataset.state = estado.estado_visual;
                elemento.dataset.available = estado.puede_reservar ? '1' : '0';
                elemento.querySelector('[data-state-label]').textContent = estado.estado_texto;
                elemento.setAttribute('aria-label', `${elemento.querySelector('strong').textContent}: ${estado.estado_texto}`);
            });
            mapa.querySelector('[data-map-status]').textContent = `Actualizado: ${new Date().toLocaleTimeString('es-PE')}`;
            actualizarLista(datos.vehiculos_activos);
        } catch (_) {
            mapa.querySelector('[data-map-status]').textContent = 'No se pudo actualizar el mapa. Se reintentará automáticamente.';
        } finally {
            actualizando = false;
        }
    };
    setInterval(refrescar, 10000);
    setInterval(actualizarTiempos, 60000);
    window.addEventListener('pageshow', evento => { if (evento.persisted) refrescar(); });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refrescar(); });
}
