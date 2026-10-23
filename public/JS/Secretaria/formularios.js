document.addEventListener('DOMContentLoaded', function () {

    const btnGuardar = document.getElementById('btn-guardar');
    const form = document.querySelector('form[data-ajax-form]');
    const alertAjax = document.getElementById('alert-ajax');

    if (!btnGuardar || !form || !alertAjax) {
        return;
    }

    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')
        .getAttribute('content');


    // ─────────────────────────────────────────────────────────────
    // Mostrar alerta
    // ─────────────────────────────────────────────────────────────

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


    // ─────────────────────────────────────────────────────────────
    // Limpiar errores
    // ─────────────────────────────────────────────────────────────

    function limpiarErrores() {

        document
            .querySelectorAll('.error-message')
            .forEach(function (error) {

                error.textContent = '';

            });


        document
            .querySelectorAll('.input-error')
            .forEach(function (input) {

                input.classList.remove('input-error');

            });

    }


    // ─────────────────────────────────────────────────────────────
    // Guardar formulario
    // ─────────────────────────────────────────────────────────────

    btnGuardar.addEventListener('click', function () {

        limpiarErrores();


        btnGuardar.disabled = true;

        btnGuardar.innerHTML =
            '<i class="ri-loader-4-line"></i> Guardando...';


        const formData = new FormData(form);

        const datos = {};


        formData.forEach(function (valor, clave) {

            if (clave !== '_token') {

                datos[clave] = valor;

            }

        });


        fetch(form.dataset.storeUrl, {

            method: 'POST',

            headers: {

                'Content-Type': 'application/json',

                'X-CSRF-TOKEN': csrfToken,

                'Accept': 'application/json',

            },

            body: JSON.stringify(datos),

        })


        .then(async function (response) {

            const data = await response.json();

            return {

                status: response.status,

                data: data

            };

        })


        .then(function ({ status, data }) {


            btnGuardar.disabled = false;

            btnGuardar.innerHTML =
                '<i class="ri-save-line"></i> Guardar';


            if (data.success) {


                mostrarAlerta(

                    '✓ ' + data.message,

                    'success'

                );
                form.reset();


            } else {


                if (data.errors) {


                    Object
                        .entries(data.errors)
                        .forEach(function ([campo, errores]) {


                            const input =
                                document.getElementById(campo);


                            if (input) {

                                input.classList.add(
                                    'input-error'
                                );

                            }


                            const error =
                                document.getElementById(
                                    'error-' + campo
                                );


                            if (error) {

                                error.textContent =
                                    errores[0];

                            }

                        });


                    mostrarAlerta(

                        'Hay campos con errores. Revisá el formulario.',

                        'error'

                    );


                } else {


                    mostrarAlerta(

                        data.message ||
                        'Ocurrió un error.',

                        'error'

                    );

                }

            }

        })


        .catch(function () {


            btnGuardar.disabled = false;


            btnGuardar.innerHTML =
                '<i class="ri-save-line"></i> Guardar';


            mostrarAlerta(

                'Error de conexión. Intentá de nuevo.',

                'error'

            );

        });

    });


    // ─────────────────────────────────────────────────────────────
    // Limpiar error al modificar un campo
    // ─────────────────────────────────────────────────────────────

    document
        .querySelectorAll('input, select')
        .forEach(function (campo) {


            const evento =
                campo.tagName === 'SELECT'
                    ? 'change'
                    : 'input';


            campo.addEventListener(evento, function () {


                campo.classList.remove(
                    'input-error'
                );


                const error =
                    document.getElementById(
                        'error-' + campo.id
                    );


                if (error) {

                    error.textContent = '';

                }

            });

        });

});
