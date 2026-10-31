document.querySelectorAll('[data-abono-selection]').forEach(label=>{
 const form=label.closest('form');const sync=()=>{const selected=form.querySelector('input[name="espacio_id"]:checked');label.textContent=selected?`${selected.dataset.code} · ${selected.dataset.zone} · S/ ${selected.dataset.amount} al mes`:'Selecciona un espacio para ver el importe mensual.';};form.addEventListener('change',sync);sync();
});
