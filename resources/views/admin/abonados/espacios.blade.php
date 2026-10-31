<fieldset class="abono-spaces"><legend>Elige el espacio fijo</legend>
<p class="text-muted">Selecciona un techo o un espacio de patio. Se comprobará su disponibilidad para el periodo al guardar.</p>
@foreach(['techo'=>['Techos',270,1,26],'patio'=>['Patio',240,27,41]] as $zona=>$info)
<details class="abono-zone" open><summary>{{ $info[0] }} <span>S/ {{ $info[1] }} / mes</span></summary><div class="abono-space-grid">
@foreach($espacios as $e)
@php($n=\App\Services\PlanoEspacios::numero($e->codigo))
@if($n >= $info[2] && $n <= $info[3])
<label class="abono-space-choice {{ $zona }}"><input type="radio" name="espacio_id" value="{{ $e->id }}" required data-amount="{{ $info[1] }}" data-code="{{ $e->codigo }}" data-zone="{{ $info[0] }}" @checked(old('espacio_id',$ultimo?->espacio_id ?? null)==$e->id)><span>{{ $e->codigo }}</span></label>
@endif
@endforeach
</div></details>
@endforeach
<p class="abono-selection" data-abono-selection role="status">Selecciona un espacio para ver el importe mensual.</p>
</fieldset>
