<dialog id="ticket-scanner" class="ticket-scanner" aria-labelledby="scanner-title" data-manual-url="{{ route('admin.estadias.index') }}#buscar" data-ticket-route="{{ route('admin.estadias.escanear',['estadia'=>'__TICKET__']) }}">
<div class="panel-heading"><h2 id="scanner-title">Escanear ticket</h2><button type="button" class="btn btn-secondary" data-scan-close>Cerrar</button></div>
<video autoplay muted playsinline aria-label="Vista de la cámara para leer el QR"></video>
<p data-scan-status role="status" aria-live="polite">Preparando cámara…</p>
<div class="operation-links"><button type="button" class="btn btn-primary" data-scan-start>Activar cámara</button><button type="button" class="btn btn-secondary" data-scan-manual>Buscar por placa o código</button></div>
<p class="text-muted">Solo consulta el ticket. El cobro y la salida requieren confirmación del personal.</p>
</dialog>
@push('scripts')@vite('resources/js/ticket-scanner.js')@endpush
