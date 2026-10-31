# Pago y salida fÃ­sica

El QR abre el ticket. Si existe saldo, **Registrar pago recibido** verifica el medio de pago, registra el cobro en Caja y conserva la estadÃ­a y el espacio ocupado. Repetir el envÃ­o no registra otro pago. La cotizaciÃ³n tiene su vigencia habitual de cinco minutos; esta vigencia es distinta de la cortesÃ­a que comienza al registrar el pago.

Desde el pago se muestran cinco minutos de cortesÃ­a. Por decisiÃ³n del usuario, vencer ese plazo **solo genera una alerta**: no agrega recargos ni libera el espacio. El importe liquidado queda conservado. El dashboard muestra tickets pagados pendientes de salida y cortesÃ­as vencidas.

**Confirmar salida** exige confirmar la salida fÃ­sica y haber registrado la devoluciÃ³n de las llaves recibidas. El servidor verifica que no haya saldo pendiente. Cierra el ticket y libera la ocupaciÃ³n; no registra otro cobro ni requiere Caja abierta. Una estadÃ­a cubierta por mensualidad o adelanto, sin saldo, puede confirmar la salida directamente. No se inventa un pago para iniciar una cortesÃ­a cuando no se cobrÃ³ dinero. Si el abono vence antes de salir y no se habÃ­a liquidado, se aplican las reglas de vencimiento ya existentes.

Los pagos y salidas anteriores conservan sus registros. La migraciÃ³n no convierte tickets histÃ³ricos ni cambia tarifas.

# Incidencias

RecepciÃ³n puede registrar incidencias con tipo, descripciÃ³n, prioridad y responsable activo. Puede vincular un ticket (se toma su placa y espacio actual), o indicar placa/espacio sin ticket. Desde cada ticket existe un acceso para registrar la incidencia vinculada.

Estados: Abierta â†’ En atenciÃ³n â†’ Resuelta â†’ Cerrada. Una abierta puede resolverse directamente; una resuelta o cerrada puede volver a En atenciÃ³n, siempre con nota. Cada actualizaciÃ³n conserva quiÃ©n la hizo, cuÃ¡ndo, los valores anteriores/nuevos y una nota. Se rechazan actualizaciones desde versiones desactualizadas para no sobrescribir el trabajo de otro empleado.

Prioridad alta/crÃ­tica y el indicador RevisiÃ³n del dueÃ±o escalan el caso en el panel. No se envÃ­an correos o mensajes externos. Las incidencias abiertas y en atenciÃ³n aparecen en Inicio. No hay rutas para eliminar el historial.

Evidencias: hasta 3 JPG, PNG o PDF de 5 MB por envÃ­o, tambiÃ©n en seguimientos. Se guardan en el disco privado `local`, con nombres generados y descarga solo para personal autorizado. En Railway, este disco necesita un volumen persistente para conservar archivos entre despliegues; no basta con tener la base de datos en Postgres. No se publica `storage/app/private/incidencias` mediante un enlace pÃºblico.

# Despliegue y pruebas

MigraciÃ³n: `2026_10_31_000003_salida_fisica_incidencias.php`. Ejecutar `php artisan migrate --force` y `php artisan view:clear` despuÃ©s de subir los archivos; `npm run build` en la compilaciÃ³n habitual. No requiere variables nuevas y no registra cobros reales.

Pruebas principales: `SalidaFisicaTest`, `IncidenciasTest`, `ParkingOperationsTest`, `AbonadosTest`, `CajaTest` y `CustodiaTest`.
