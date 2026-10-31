document.querySelectorAll('[data-presencial]').forEach(box => {
    const form=box.closest('form'), method=box.querySelector('[data-presencial-method]'), mixed=box.querySelector('[data-mixed]');
    const digital=box.querySelector('[data-digital]'), received=box.querySelector('[data-received]'), status=box.querySelector('[data-change]');
    const cents=value=>Math.round(Number(value||0)*100), money=value=>'S/ '+(value/100).toFixed(2);
    const field=(selector,show)=>{const el=box.querySelector(selector);if(el){el.hidden=!show;el.querySelectorAll('input').forEach(input=>{input.disabled=!show;input.required=show;});}};
    const sync=()=>{
        const selected=form.querySelector('input[name="espacio_id"]:checked');
        const total=selected?.dataset.amount ? cents(selected.dataset.amount) : Number(box.dataset.total);
        const isDigital=method.value!=='efectivo', combined=isDigital&&mixed.checked;
        box.querySelector('[data-mixed-option]').hidden=!isDigital;mixed.disabled=!isDigital;
        field('[data-digital-field]',combined);field('[data-received-field]',!isDigital||combined);field('[data-reference-field]',isDigital);
        const part=combined?cents(digital.value):(isDigital?total:0), cash=total-part, tender=cents(received.value);
        digital.setCustomValidity(combined&&(part<=0||part>=total)?'Indica una parte digital mayor a cero y menor al total.':'');
        received.setCustomValidity(cash>0&&received.value!==''&&tender<cash?'El efectivo no cubre la parte pendiente.':'');
        if(!total){status.textContent='Selecciona el espacio para calcular el importe.';return;}
        if(combined&&(part<=0||part>=total)){status.textContent='Indica cuánto se recibió por '+method.value.toUpperCase()+'.';return;}
        status.textContent='Total: '+money(total)+' · Efectivo neto: '+money(cash)+(part?' · '+method.value.toUpperCase()+': '+money(part):'')+(cash>0?(received.value===''?' · Ingresa el efectivo para calcular el vuelto.':tender<cash?' · Falta: '+money(cash-tender):' · Vuelto: '+money(tender-cash)):'');
    };
    form.addEventListener('input',sync);form.addEventListener('change',sync);sync();
});
