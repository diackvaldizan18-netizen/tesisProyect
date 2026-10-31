<fieldset data-presencial data-total="{{ $totalCentavos ?? 0 }}" style="border:0;padding:0;margin:0">
<legend>Pago recibido</legend>
<div class="form-group"><label>Medio de pago<select name="metodo_pago" data-presencial-method required>@foreach(['efectivo'=>'Efectivo','yape'=>'Yape','plin'=>'Plin'] as $value=>$label)<option value="{{ $value }}" @selected(old('metodo_pago')===$value)>{{ $label }}</option>@endforeach</select></label></div>
<label class="checkbox-row" data-mixed-option><input type="checkbox" name="pago_combinado" value="1" data-mixed @checked(old('pago_combinado'))> Completar una parte con efectivo</label>
<div class="form-group" data-digital-field><label>Importe por Yape / Plin (S/)<input type="number" min="0.01" step="0.01" max="999999" name="monto_digital" data-digital value="{{ old('monto_digital') }}"></label></div>
<div class="form-group" data-received-field><label>Efectivo que entrega el cliente (S/)<input type="number" min="0" step="0.01" max="999999" name="efectivo_recibido" data-received value="{{ old('efectivo_recibido') }}"></label></div>
@if($referenciaObligatoria ?? true)
<div class="form-group" data-reference-field><label>Número de operación verificada<input name="referencia_pago" maxlength="80" value="{{ old('referencia_pago') }}"></label><small>Comprueba el abono en la cuenta del negocio.</small></div>
@endif
<p class="alert alert-info" data-change role="status" aria-live="polite"></p>
<label class="checkbox-row"><input type="checkbox" name="pago_verificado" value="1" required> Confirmo que recibí el efectivo y/o verifiqué el abono digital.</label>
</fieldset>
@once
@push('scripts')<script src="{{ asset('js/pago-presencial.js') }}?v={{ filemtime(public_path('js/pago-presencial.js')) }}" defer></script>@endpush
@endonce
