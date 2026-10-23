document.addEventListener('DOMContentLoaded', function () {

    const selectCarrera = document.getElementById('select-carrera-finales');
    const botonesTab     = Array.from(document.querySelectorAll('.btn-tab'));
    const tablas          = Array.from(document.querySelectorAll('.tabla-finales'));
    const btnImprimir     = document.getElementById('btn-imprimir');

    if (!selectCarrera || botonesTab.length === 0 || tablas.length === 0) return;

    function tabActiva() {
        const boton = botonesTab.find(function (b) {
            return b.classList.contains('activo');
        });
        return boton ? boton.dataset.tabla : botonesTab[0].dataset.tabla;
    }

    function actualizarVista() {
        const carrera = selectCarrera.value;
        const tabla   = tabActiva();

        tablas.forEach(function (t) {
            const coincide = t.dataset.carrera === carrera && t.dataset.tabla === tabla;
            t.style.display = coincide ? '' : 'none';
        });
    }

    botonesTab.forEach(function (boton) {
        boton.addEventListener('click', function () {
            botonesTab.forEach(function (b) {
                b.classList.remove('activo');
            });
            boton.classList.add('activo');
            actualizarVista();
        });
    });

    selectCarrera.addEventListener('change', actualizarVista);

    if (btnImprimir) {
        btnImprimir.addEventListener('click', function () {
            window.print();
        });
    }

    actualizarVista();

    // -----------------------------------------------------------------
    // BAJA DE INSCRIPCIÓN A MESA (solo visible/activo en la pestaña
    // "Inscripto"). window.rutaBajaFinal lo define la vista Blade antes
    // de cargar este script.
    // -----------------------------------------------------------------

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-baja-final');
        if (!btn) return;

        const idMesa = btn.dataset.idMesa;
        if (!idMesa) return;

        if (!confirm('¿Estás seguro de que querés darte de baja de esta mesa?')) {
            return;
        }

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (!csrfMeta || !window.rutaBajaFinal) {
            alert('No se pudo procesar la baja (falta configuración de la página).');
            return;
        }

        btn.disabled = true;

        fetch(window.rutaBajaFinal, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfMeta.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ id_mesa: idMesa }),
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (data.success) {
                    window.location.reload();
                } else {
                    btn.disabled = false;
                    alert(data.message || 'No se pudo procesar la baja.');
                }
            })
            .catch(function () {
                btn.disabled = false;
                alert('Error de red al intentar dar de baja.');
            });
    });

});