# Recepción: modalidades y custodia

La entrada se divide en Por hora, Abonado y Reserva web. Las reservas conservan su cobro existente. Los importes de mensualidades y tarifas no cambian.

Cada nuevo ticket reserva el casillero con el número de su espacio (T04 → T04), sin marcar las llaves como recibidas. Si está ocupado, el ingreso se revierte y recepción debe seleccionar explícitamente otro casillero. Reubicar el vehículo no cambia su casillero. Si el vehículo sale sin haberse registrado recepción de llaves, se libera la asignación pendiente dejando un evento, sin inventar una devolución.

El ticket impreso incluye un QR firmado, distinto por estadía y estable al reimprimir. Escanear con la cámara del teléfono abre el ticket y saldo actualizado; requiere sesión de personal. No ejecuta cobros ni salidas. El dominio APP_URL debe corresponder al dominio accesible desde el dispositivo; un QR de localhost solo sirve en ese equipo.

Desde Operaciones, **Escanear ticket** abre la cámara tras conceder permiso. Usa preferentemente la cámara trasera, procesa el video en el dispositivo con jsQR y abre únicamente enlaces firmados de tickets del mismo origen. El servidor verifica la firma y los permisos. Cerrar el diálogo, salir de la página o pasar a otra aplicación detiene la cámara. Permisos denegados y dispositivos sin cámara mantienen la búsqueda manual. Requiere HTTPS (o localhost en el mismo equipo). Falta validar enfoque y lectura física con la tablet y el papel de la ticketera reales.

Abonados muestra tarjetas con estado, placas, mensualidad, espacio y vigencia. La selección de espacio usa una cuadrícula separada por techos y patio. Por decisión del usuario no se pide referencia de Yape/Plin en mensualidades; sí se exige confirmar la recepción del dinero. Se conservan bloqueo de periodos superpuestos y transacciones. Sin referencia bancaria no se puede detectar la reutilización de una operación digital en pagos de periodos o espacios diferentes. Los pagos históricos conservan sus referencias y el resto de flujos no cambia.

Tras estacionar, abrir el ticket y registrar la recepción física en un casillero del 1 al 41. Cada casillero admite una custodia activa; permanece reservado cuando el trabajador retira la llave para mover el vehículo. El mismo trabajador registra que vuelve a guardarla. La devolución al cliente libera el casillero y debe confirmarse antes de cerrar el ticket. Cada acción conserva empleado, fecha y casillero.

Los tickets anteriores conservan su ubicación y autorización. Si tienen llaves recibidas sin custodia estructurada, recepción debe verificar físicamente el casillero y regularizarlo desde el ticket. No se asignan casilleros ni se inventan horas históricas en la migración. Las ubicaciones antiguas en formato `Casillero N` o `N` se respetan al comprobar disponibilidad; las descripciones libres requieren revisión del personal.

Inicio de recepción muestra entradas, salidas, espacios libres sin abono, espacios libres asignados a abonados y llaves pendientes. Operaciones permite consultar la custodia junto al ticket.

## Aplicación

Después de subir el código, ejecutar en el entorno correspondiente:

```sh
php artisan migrate --force
php artisan view:clear
npm run build
```

No se requieren variables nuevas. La migración es `2026_10_31_000001_custodia_llaves.php`. No ejecutar migrate:fresh ni reemplazar datos existentes.

## Etapas pendientes

Caja, separación entre pago y salida física e Incidencias ya están implementadas; consultar `docs/caja.md` y `docs/salida-e-incidencias.md`. El importe queda cerrado al pagar, sin contador de cortesía posterior. La vigencia de la cotización se conserva. No se ha introducido ningún recargo nuevo ni cambiado el trato de abonados.

El Inicio de Recepción prioriza indicadores compactos, entrada, escaneo de ticket, búsqueda y reservas; después muestra el plano con alertas y llegadas. Las estadísticas están en una sección desplegable inferior. El detalle de espacios se abre en un panel lateral (pantalla completa en móvil). Los estados corresponden al momento de consulta y se actualizan con el botón correspondiente. Las coordenadas y reglas de ingreso no cambian; el servidor vuelve a validar cada operación.

Reubicación en el plano: solo está disponible para un vehículo por horas que ocupa un espacio con abono vigente y pago aprobado; se oculta y bloquea para abonos futuros, vencidos o sin pagar y para espacios sin abono. Abrir el espacio ocupado, elegir «Reubicar en el plano» y tocar un destino resaltado registra el traslado, sin formulario adicional. El aviso indica que se debe tocar el destino después del movimiento físico. Conserva autorización y recepción de llave como requisitos; una llave guardada permite el traslado rápido sin registrar una retirada separada y conserva su estado/casillero, sin inventar eventos de custodia. Si otro trabajador tiene la llave, no permite mover. Valida de nuevo ambos espacios bajo bloqueo y conserva ticket, tiempo, tarifa y pagos. La selección manual del destino sigue disponible en el ticket bajo la misma regla de abono vigente.
