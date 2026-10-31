<section class="card" id="custodia"><h2>Custodia de llaves</h2>
<p><strong>{{ ['guardada'=>'Guardada en casillero','con_personal'=>'En poder del personal','devuelta'=>'Devuelta al cliente'][$estadia->custodia_estado] ?? ($estadia->llave_recibida ? 'Custodia anterior: verificar y registrar casillero' : 'Pendiente de recepción') }}</strong></p>
@if($estadia->ubicacion_llave)<p>{{ $estadia->ubicacion_llave }}</p>@endif
@if($estadia->custodia_responsable_id)<p>Último responsable: {{ $estadia->custodiaResponsable?->name }}</p>@endif
@if(!$estadia->hora_salida)
<form method="post" action="{{ route('admin.estadias.custodia',$estadia) }}">@csrf
@if(!$estadia->custodia_estado || in_array($estadia->custodia_estado,['devuelta','pendiente']))
<input type="hidden" name="accion" value="recibir">
<label for="casillero">Casillero individual</label><select id="casillero" name="casillero" required><option value="">Selecciona el casillero verificado</option>@for($i=1;$i<=41;$i++)<option value="{{ $i }}" @selected(old('casillero',$estadia->casillero_activo)==$i)>T{{ str_pad($i,2,'0',STR_PAD_LEFT) }}</option>@endfor</select>
@elseif($estadia->custodia_estado === 'con_personal')
<input type="hidden" name="accion" value="guardar"><p>Registra que vuelves a guardar la llave después del movimiento.</p>
@else
<label for="accion-custodia">Acción</label><select id="accion-custodia" name="accion"><option value="devolver">Devolver al cliente tras verificar ticket e identidad</option>@if($estadia->autoriza_movimiento)<option value="retirar">Retirar para mover el vehículo</option>@endif</select>
@endif
<label class="checkbox-row"><input type="checkbox" name="confirmado" value="1" required> Confirmo que realicé la entrega o recepción física de la llave.</label>
<button class="btn btn-secondary">Registrar custodia</button></form>
@endif
<details><summary>Historial de custodia</summary>@forelse($custodiaEventos as $evento)<p>{{ $evento->created_at }} · {{ $evento->name }} · {{ ['asignar'=>'Asignó casillero (llave pendiente)','liberar_sin_recepcion'=>'Liberó casillero al salir sin recepción de llave','recibir'=>'Recibió','retirar'=>'Retiró para movimiento','guardar'=>'Guardó','devolver'=>'Devolvió al cliente'][$evento->accion] ?? $evento->accion }} · Casillero T{{ str_pad($evento->casillero,2,'0',STR_PAD_LEFT) }}</p>@empty<p>Sin movimientos registrados.</p>@endforelse</details>
</section>
