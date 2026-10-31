@if($modalidad === 'abonado')
<div class="form-group"><label for="abono_id">Abonado vigente · placa y espacio asignado</label><select id="abono_id" name="abono_id" required><option value="">Selecciona el abonado</option>@foreach($abonos as $a)<option value="{{ $a->id }}" data-space="{{ $a->espacio_id }}" data-plates="{{ implode(',', $a->abonado->placas) }}" @selected(old('abono_id',request('abono_id'))==$a->id)>{{ $a->abonado->nombre }} · {{ implode(', ', $a->abonado->placas) }} · {{ $a->espacio->codigo }}</option>@endforeach</select><p id="abono-help" role="status">Verifica la placa autorizada. Si el espacio está ocupado, reubica primero el vehículo temporal desde su ticket.</p></div>
@endif
<fieldset><legend>Autorización y llaves</legend>
<label class="checkbox-row"><input type="checkbox" name="autoriza_movimiento" value="1" @checked(old('autoriza_movimiento'))> El cliente autoriza al personal a reubicar su vehículo.</label>
<p>El casillero se asigna con el mismo número del espacio (T04 → casillero T04). Después de estacionar, confirma la recepción física de las llaves desde el ticket.</p>
<p id="casillero-sugerido" role="status"></p>
<details @if($errors->has('casillero') || old('casillero')) open @endif><summary>Elegir otro casillero si el correspondiente está ocupado</summary><div class="form-group"><label for="casillero-alternativo">Casillero alternativo</label><select id="casillero-alternativo" name="casillero"><option value="">Usar el número del espacio</option>@for($i=1;$i<=41;$i++)<option value="{{ $i }}" @selected(old('casillero')==$i)>T{{ str_pad($i,2,'0',STR_PAD_LEFT) }}</option>@endfor</select><small>El servidor comprobará que esté libre. La asignación no confirma que recibiste las llaves.</small></div></details>
</fieldset>
