@include('admin.abonados.espacios')
<div class="form-group"><label for="inicio">Primer día del periodo</label><input type="date" id="inicio" name="inicio" required value="{{ old('inicio',isset($ultimo) ? $ultimo->fin->format('Y-m-d') : now()->format('Y-m-d')) }}"><small>Un mes desde las 00:00 del día elegido, hasta las 00:00 del mismo día del mes siguiente (ajustado al último día si no existe).</small></div>
@include('admin.payment-fields',['referenciaObligatoria'=>false])
@once
@push('scripts')<script src="{{ asset('js/abonados.js') }}" defer></script>@endpush
@endonce
