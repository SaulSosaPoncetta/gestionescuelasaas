document.addEventListener('DOMContentLoaded', function () {

    const form = document.querySelector('form[data-perfil-editable]');
    if (!form) return;

    const btnModificar = document.getElementById('btn-modificar');
    const btnGuardar   = document.getElementById('btn-guardar');
    const btnCancelar  = document.getElementById('btn-cancelar');
    const alertAjax    = document.getElementById('alert-ajax');

    if (!btnModificar || !btnGuardar || !btnCancelar || !alertAjax) return;

    const url = form.dataset.updateUrl;

    const camposEditables = Array.from(
        form.querySelectorAll('input[name], select[name]')
    ).filter(el => el.name !== '_token' && el.name !== '_method');

    let valoresOriginales = {};

    function guardarValoresOriginales() {
        camposEditables.forEach(function (campo) {
            valoresOriginales[campo.name] = campo.value;
        });
    }

    function restaurarValoresOriginales() {
        camposEditables.forEach(function (campo) {
            campo.value = valoresOriginales[campo.name] ?? '';
        });
    }

    function habilitarEdicion() {
        camposEditables.forEach(function (campo) {
            campo.disabled = false;
        });
        btnModificar.innerHTML = '<i class="ri-edit-line"></i> Editando...';
        btnModificar.classList.add('btn-editing');
        btnGuardar.style.display  = '';
        btnCancelar.style.display = '';
    }

    function deshabilitarEdicion() {
        camposEditables.forEach(function (campo) {
            campo.disabled = true;
        });
        btnModificar.innerHTML = '<i class="ri-edit-line"></i> Modificar';
        btnModificar.classList.remove('btn-editing');
        btnGuardar.style.display  = 'none';
        btnCancelar.style.display = 'none';
    }

    function mostrarAlerta(mensaje, tipo) {
        alertAjax.textContent = mensaje;
        alertAjax.className = 'alert alert-' + tipo;
        alertAjax.style.display = 'block';

        alertAjax.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });

        if (tipo === 'success') {
            setTimeout(function () {
                alertAjax.style.display = 'none';
            }, 5000);
        }
    }

    function limpiarErrores() {
        document.querySelectorAll(".error-message").forEach(function (error) {
            error.textContent = "";
        });
        document.querySelectorAll(".input-error").forEach(function (input) {
            input.classList.remove("input-error");
        });
    }

    guardarValoresOriginales();

    btnModificar.addEventListener('click', function () {
        habilitarEdicion();
    });

    btnCancelar.addEventListener('click', function () {
        restaurarValoresOriginales();
        deshabilitarEdicion();
        limpiarErrores();
        alertAjax.style.display = 'none';
    });

    btnGuardar.addEventListener('click', function () {

        limpiarErrores();

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

        const datos = {};
        camposEditables.forEach(function (campo) {
            datos[campo.name] = campo.value;
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify(datos),
        })
        .then(function (response) {
            return response.json().then(function (data) {
                return { status: response.status, data: data };
            });
        })
        .then(function ({ status, data }) {
            btnGuardar.disabled  = false;
            btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

            if (data.success) {

                guardarValoresOriginales();
                deshabilitarEdicion();
                mostrarAlerta('✓ ' + data.message, 'success');

            } else {

                if (data.errors) {

                    Object.entries(data.errors).forEach(function ([campo, errores]) {

                        const input = document.getElementById(campo);

                        if (input) {
                            input.classList.add("input-error");
                        }

                        const error = document.getElementById("error-" + campo);

                        if (error) {
                            error.textContent = errores[0];
                        }

                    });

                    mostrarAlerta("Hay campos con errores. Revisá el formulario.", "error");

                } else {

                    mostrarAlerta(data.message || "Ocurrió un error.", "error");

                }
            }
        })
        .catch(function () {
            btnGuardar.disabled  = false;
            btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
            mostrarAlerta('Error de conexión. Intentá de nuevo.', 'error');
        });
    });

    // Limpiar error al escribir
    camposEditables.forEach(function (campo) {
        const evento = campo.tagName === "SELECT" ? "change" : "input";

        campo.addEventListener(evento, function () {
            campo.classList.remove("input-error");
            const error = document.getElementById("error-" + campo.id);
            if (error) {
                error.textContent = "";
            }
        });
    });

    // Recargar la página al volver con la flecha del navegador
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
        const perfEntries = performance.getEntriesByType('navigation');
        if (perfEntries.length > 0 && perfEntries[0].type === 'back_forward') {
            window.location.reload();
        }
    });

});