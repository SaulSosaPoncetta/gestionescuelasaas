document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.fechas-row').forEach(function (fila) {

        const botones = Array.from(fila.querySelectorAll('.btn-fecha'));

        botones.forEach(function (boton) {
            boton.addEventListener('click', function () {

                // Solo se puede elegir una fecha por materia: al clickear
                // una, se desmarcan las demás de la misma fila.
                botones.forEach(function (b) {
                    b.classList.remove('seleccionada');
                });
                boton.classList.add('seleccionada');
            });
        });

    });

    // ─── Guardar inscripción ────────────────────────────────────────────
    const btnGuardar = document.getElementById('btn-guardar-inscripcion');

    if (btnGuardar) {
        btnGuardar.addEventListener('click', function () {

            const idsMesa = Array.from(document.querySelectorAll('.btn-fecha.seleccionada'))
                .map(function (boton) {
                    return boton.dataset.idMesa;
                });

            // reemplazar por el endpoint real de inscripción.
            // Ejemplo de cómo quedaría (falta el <meta name="csrf-token">
            // en el <head> de este blade, y la ruta real, ninguna de las
            // dos cosas está resuelta todavía):
            //
            // fetch('/finales/inscribir', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            //     },
            //     body: JSON.stringify({ ids_mesa: idsMesa }),
            // })
            // .then(function (response) { return response.json(); })
            // .then(function (data) {
            //     if (data.success) {
            //         window.location = window.rutaFinalesSec;
            //     } else {
            //         alert(data.message || 'Ocurrió un error al guardar.');
            //     }
            // })
            // .catch(function () {
            //     alert('Error de conexión. Intentá de nuevo.');
            // });

            // Por ahora, sin backend conectado, guarda "en falso" y
            // vuelve directo a la misma pantalla que el botón Volver.
            console.log('Mesas a inscribir (mock):', idsMesa);
            window.location = window.rutaFinalesSec;
        });
    }

});