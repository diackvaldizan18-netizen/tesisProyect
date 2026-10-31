# Caja de recepción

Una caja física admite un único turno abierto. Cualquier recepcionista puede abrirlo cuando esté cerrada. Los cobros de todos los trabajadores autorizados se asocian a ese turno, conservando quién registró cada uno. El responsable de apertura puede cerrar su turno; el superadmin también puede hacerlo. Otro recepcionista no puede cerrarlo.

Se requiere apertura para registrar cobros de salida, mensualidades, reservas al llegar y aprobaciones de comprobantes antiguos. Cada cobro y su registro de caja comparten transacción: si la caja está cerrada se revierte toda la operación. Una salida cubierta por mensualidad o adelanto, sin cobro adicional, puede continuar con caja cerrada.

El esperado físico es fondo inicial + cobros en efectivo + ingresos manuales − egresos manuales. Yape y Plin se totalizan por separado. El cobro en efectivo incorpora el importe neto después del vuelto, no el billete recibido. Cerrar solo exige confirmación del responsable o del dueño. El sistema calcula el efectivo esperado bajo bloqueo transaccional; contado y diferencia quedan nulos porque no se realizó un conteo físico. Los cierres históricos conservan sus conteos originales. El cierre no retira dinero. Al abrir el siguiente turno se declara el fondo real que queda en el cajón.

Solo el dueño registra ingresos y egresos manuales de efectivo con motivo. No existen rutas para editar o borrar los movimientos o cierres. Los pagos históricos no se asignan retroactivamente a un turno. Los reembolsos administrativos existentes no equivalen a una salida física de dinero de caja: un retiro efectivo debe ser registrado por el dueño, con referencia en el motivo.

## Despliegue

Ejecutar las migraciones pendientes (`php artisan migrate --force`), `php artisan view:clear` y compilar recursos (`npm run build`). La migración de desglose es `2026_10_31_000004_desglose_cobros.php`. No requiere variables adicionales. El primer turno se abre desde Caja; la migración no crea aperturas ni movimientos financieros.

## Alcance

El pago y la salida física están separados. El cobro se registra en el turno activo y el ticket sigue abierto hasta la salida. El importe queda cerrado al pagar, sin contador de cortesía ni recargos posteriores. Consultar `docs/salida-e-incidencias.md`.

Los pagos presenciales de estadías, reservas y mensualidades admiten efectivo más Yape o Plin. El importe digital debe ser positivo y menor al total. Se guarda un único pago y movimiento con desglose en centavos, efectivo entregado y vuelto; no se duplica el total. La referencia digital mantiene su unicidad en estadías y reservas; las mensualidades conservan su política sin referencia obligatoria.
