const movePanel=document.querySelector('[data-map-relocation]'), destination=movePanel?.querySelector('[data-move-target]');
if(destination){
 const widget=document.querySelector('[data-parking]'), form=movePanel.querySelector('[data-move-form]');
 const allowed=new Set(JSON.parse(form.querySelector('[data-move-destinations]').textContent).map(String));
 let submitting=false;
 widget.querySelectorAll('[data-space]').forEach(button=>{
  const eligible=allowed.has(button.dataset.space);button.classList.toggle('move-eligible',eligible);button.setAttribute('aria-disabled',String(!eligible));
 });
 widget.addEventListener('click',event=>{
  const button=event.target.closest('[data-space]');if(!button)return;
  event.preventDefault();event.stopImmediatePropagation();if(submitting)return;
  if(!allowed.has(button.dataset.space)){movePanel.querySelector('[data-move-message]').textContent='Elige uno de los espacios libres resaltados.';return;}
  destination.value=button.dataset.space;submitting=true;button.classList.add('space-selected');
  movePanel.querySelector('[data-move-message]').textContent='Registrando traslado a '+button.querySelector('strong').textContent+'…';
  form.requestSubmit();
 },true);
}
