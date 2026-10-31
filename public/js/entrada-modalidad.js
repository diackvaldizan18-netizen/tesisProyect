document.addEventListener('DOMContentLoaded', () => {
    const entrySpace = document.getElementById('espacio_id');
    const alternative = document.getElementById('casillero-alternativo');
    const hint = document.getElementById('casillero-sugerido');
    const lockerHint = () => {
        if (!hint || !entrySpace) return;
        const code = entrySpace.selectedOptions[0]?.textContent.trim().split(' · ')[0];
        hint.textContent = alternative?.value
            ? `Casillero elegido: T${alternative.value.padStart(2, '0')}. Se comprobará que esté libre al guardar.`
            : entrySpace.value ? `Casillero asignado al registrar: ${code}. Si está ocupado, deberás elegir otro.`
                : 'Selecciona un espacio para conocer el casillero correspondiente.';
    };
    entrySpace?.addEventListener('change', lockerHint);
    alternative?.addEventListener('change', lockerHint);
    lockerHint();
    const abono = document.getElementById('abono_id');
    if (!abono) return;
    const apply = () => {
        const option = abono.selectedOptions[0];
        if (!option?.dataset.space) return;
        const space = document.getElementById('espacio_id');
        const available = Array.from(space.options).some(item => item.value === option.dataset.space);
        space.value = available ? option.dataset.space : '';
        space.dispatchEvent(new Event('change'));
        const plates = option.dataset.plates.split(',');
        if (plates.length === 1) document.getElementById('placa').value = plates[0];
        document.getElementById('abono-help').textContent = available
            ? 'Espacio asignado seleccionado. Verifica la placa y el tipo de vehículo.'
            : 'El espacio asignado no está disponible. Consulta su ticket y reubica el vehículo temporal antes de registrar esta entrada.';
    };
    abono.addEventListener('change', apply);
    if (abono.value) apply();
});
