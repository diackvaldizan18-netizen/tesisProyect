<div class="form-group"><label for="prioridad">Prioridad</label><select id="prioridad" name="prioridad">
@foreach(\App\Models\Incidencia::PRIORIDADES as $key=>$label)<option value="{{ $key }}" @selected(old('prioridad',$incidencia->prioridad??'media')===$key)>{{ $label }}</option>
@endforeach</select></div>
<div class="form-group"><label for="responsable_id">Responsable de atención</label><select id="responsable_id" name="responsable_id" required>
@foreach($personal as $persona)<option value="{{ $persona->id }}" @selected(old('responsable_id',$incidencia->responsable_id??auth()->id())==$persona->id)>{{ $persona->name }}</option>
@endforeach</select></div>
<label class="checkbox-row"><input type="checkbox" name="escalada" value="1" @checked(old('escalada',$incidencia->escalada??false))> Requiere revisión del dueño</label><small>Prioridad alta o crítica se marca automáticamente para revisión del dueño.</small>
<div class="form-group"><label for="evidencias">Evidencias (opcional)</label><input type="file" id="evidencias" name="evidencias[]" accept="image/jpeg,image/png,application/pdf" multiple><small>Hasta 3 archivos por envío. JPG, PNG o PDF, máximo 5 MB por archivo. Solo el personal puede consultarlos.</small></div>
