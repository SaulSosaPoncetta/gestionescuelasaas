<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mesa de final</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/cargarNota.css') }}">

    {{-- Reusable en cualquier vista que tenga botón Imprimir --}}
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">

    @vite(['resources/css/app.css'])
</head>

<body>
<button class="mobile-menu-btn no-print" id="menu-btn">
    <i class="ri-menu-line"></i>
</button>
    @include('partials.sidebar')

    <div class="main">
        {{-- Puede entrarse desde finalesSec o desde finalesHistorial: se
             vuelve al historial de navegación real, no a una ruta fija.
             Fallback a finalesSec solo si no hay "de dónde volver"
             (ej: entraron directo por URL / pestaña nueva). --}}
        <button class="btn-modificar no-print" type="button" id="btn-volver">
            <i class="ri-arrow-left-line"></i>Volver
        </button>

        {{-- Todo lo que va DENTRO de este div es lo único que se imprime --}}
        <div class="printable-area">

            <div class="perfil-header">
                <h1>Mesa de final</h1>

                <div class="header-actions no-print">
                    <button class="btn-modificar" id="btn-modificar" type="button">
                        <i class="ri-edit-line"></i> Modificar
                    </button>
                    <button class="btn-primary" onclick="window.location.href='{{ route('finalesAgregarEst', $mesa->id_mesa) }}'">Agregar alumno</button>
                    <button class="btn-primary" onclick="window.location.href='{{ route('cargarNotasFinal', $mesa->id_mesa) }}'">Subir Nota</button>
                </div>
            </div>

            <div id="alert-ajax" class="alert no-print" style="display:none;"></div>

            {{-- Un solo form para datos de mesa + notas. "Modificar" habilita
                 todo junto, "Guardar" manda dos PUT en paralelo:
                 - PUT /finales/{id}          → datos de la mesa
                 - PUT /mesaFinalPerfil/{id}  → notas
                 Ambos vía fetch, sin submit real del form. --}}
            <form id="form-mesa-final">
                @csrf
                @method('PUT')

                <div class="form-grid" style="max-width:600px; margin-bottom:24px;">

                    <label>Espacio</label>
                    <div class="input-group">
                        <select class="mesa-input" name="id_materia" id="id_materia" disabled>
                            @foreach ($materias as $materia)
                                <option value="{{ $materia->id_materia }}" @selected($materia->id_materia == $mesa->id_materia)>
                                    {{ $materia->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <small class="error-message no-print" id="error-id_materia"></small>
                    </div>

                    <label>Fecha</label>
                    <div class="input-group">
                        <div class="fecha-hora-row">
                            <input class="mesa-input" type="date" id="fecha_input" value="{{ $mesa->fecha_input }}" disabled>
                            <input class="mesa-input" type="time" id="hora_input" value="{{ $mesa->hora_input }}" disabled>
                        </div>
                        <small class="error-message no-print" id="error-fecha"></small>
                    </div>

                    <label>Presidente de mesa</label>
                    <div class="input-group">
                        <select class="mesa-input" name="docente_titular" id="docente_titular" disabled>
                            <option value="" @selected(!$mesa->id_presidente)>Sin asignar</option>
                            @foreach ($docentes as $docente)
                                <option value="{{ $docente->id_usuario }}" @selected($docente->id_usuario == $mesa->id_presidente)>
                                    {{ $docente->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <small class="error-message no-print" id="error-docente_titular"></small>
                    </div>

                    <label>Vocal</label>
                    <div class="input-group">
                        <select class="mesa-input" name="docente_vocal" id="docente_vocal" disabled>
                            <option value="" @selected(!$mesa->id_vocal)>Sin asignar</option>
                            @foreach ($docentes as $docente)
                                <option value="{{ $docente->id_usuario }}" @selected($docente->id_usuario == $mesa->id_vocal)>
                                    {{ $docente->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <small class="error-message no-print" id="error-docente_vocal"></small>
                        @if (count($mesa->vocales) > 1)
                            <small class="no-print" style="color:#b45309; font-size:12px;">
                                Esta mesa tenía más de un vocal cargado ({{ implode(', ', $mesa->vocales) }}); al guardar solo queda el que elijas acá.
                            </small>
                        @endif
                    </div>

                    <label>N° Aula</label>
                    <div class="input-group">
                        <input class="mesa-input" type="text" id="aula" value="{{ $mesa->aula }}" disabled>
                        <small class="error-message no-print" id="error-aula"></small>
                    </div>

                </div>

                @if ($alumnosFinal->isEmpty())

                    <div class="empty-state">
                        <i class="ri-user-line"></i>
                        <p>Todavía no hay estudiantes agregados a esta mesa.</p>
                    </div>

                @else

                    <div class="table-container">
                        <table class="cursada-table" id="tablaMesaFinal">
                            <thead>
                                <tr>
                                    <th>Alumno</th>
                                    <th style="width:140px;">Nota</th>
                                    <th style="width:100px;">Intentos</th>
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($alumnosFinal as $alumno)
                                <tr>
                                    <td>{{ $alumno->nombre }}</td>
                                    <td class="nota-cell">
                                        <input
                                            type="number"
                                            class="nota-input"
                                            name="nota_{{ $alumno->id_usuario }}"
                                            id="nota_{{ $alumno->id_usuario }}"
                                            value="{{ $alumno->nota }}"
                                            min="1"
                                            max="10"
                                            step="0.01"
                                            disabled
                                        >
                                        <small class="error-message no-print" id="error-nota_{{ $alumno->id_usuario }}"></small>
                                    </td>
                                    <td>{{ $alumno->cant }}</td>
                                </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>

                @endif

                <div class="no-print" style="display:flex; justify-content:flex-start; gap:10px; margin-top:12px;">
                    <button type="button" class="btn-primary" id="btn-guardar" style="display:none;">
                        <i class="ri-save-line"></i> Guardar
                    </button>
                    <button type="button" class="btn-secondary" id="btn-cancelar" style="display:none;">
                        Cancelar
                    </button>
                </div>

            </form>

        </div>
        {{-- fin .printable-area --}}

        <button type="button" class="btn-table no-print" id="btn-imprimir" title="Imprimir mesa" style="width:44px; height:44px; margin-top:20px;">
            <i class="ri-printer-line"></i>
        </button>

    </div>

    @include('partials.footer')

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ─── Volver: navega al referrer con recarga real (no bfcache) ──
        const btnVolver = document.getElementById('btn-volver');
        btnVolver.addEventListener('click', function () {
            const vieneDeLaApp = document.referrer && document.referrer.startsWith(window.location.origin);

            window.location.href = vieneDeLaApp
                ? document.referrer
                : '{{ route('finalesSec') }}';
        });

        const btnModificar = document.getElementById('btn-modificar');
        const btnGuardar    = document.getElementById('btn-guardar');
        const btnCancelar   = document.getElementById('btn-cancelar');
        const btnImprimir   = document.getElementById('btn-imprimir');
        const alertAjax     = document.getElementById('alert-ajax');

        btnImprimir.addEventListener('click', function () {
            window.print();
        });

        // Notas (puede no haber ninguna si la mesa está vacía) + datos de mesa
        const notaInputs = Array.from(document.querySelectorAll('.nota-input'));
        const mesaInputs = Array.from(document.querySelectorAll('.mesa-input'));
        const editableInputs = notaInputs.concat(mesaInputs);

        let valoresOriginales = {};

        function guardarValoresOriginales() {
            editableInputs.forEach(function (input) {
                const key = input.name || input.id;
                valoresOriginales[key] = input.value;
            });
        }

        function restaurarValoresOriginales() {
            editableInputs.forEach(function (input) {
                const key = input.name || input.id;
                input.value = valoresOriginales[key] ?? '';
            });
        }

        function habilitarEdicion() {
            editableInputs.forEach(function (input) {
                input.disabled = false;
            });
            btnModificar.innerHTML = '<i class="ri-edit-line"></i> Editando...';
            btnModificar.classList.add('btn-editing');
            btnGuardar.style.display  = '';
            btnCancelar.style.display = '';
        }

        function deshabilitarEdicion() {
            editableInputs.forEach(function (input) {
                input.disabled = true;
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
            alertAjax.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            if (tipo === 'success') {
                setTimeout(function () { alertAjax.style.display = 'none'; }, 5000);
            }
        }

        function limpiarErrores() {
            document.querySelectorAll('.error-message').forEach(function (error) {
                error.textContent = '';
            });
            document.querySelectorAll('.input-error').forEach(function (input) {
                input.classList.remove('input-error');
            });
        }

        guardarValoresOriginales();

        btnModificar.addEventListener('click', habilitarEdicion);

        btnCancelar.addEventListener('click', function () {
            restaurarValoresOriginales();
            deshabilitarEdicion();
            limpiarErrores();
            alertAjax.style.display = 'none';
        });

        notaInputs.forEach(function (input) {
            input.addEventListener('input', function () {
                input.classList.remove('input-error');
                const error = document.getElementById('error-' + input.id);
                if (error) {
                    error.textContent = '';
                }
            });
        });

        function combinarFechaHora() {
            const fecha = document.getElementById('fecha_input').value;
            const hora  = document.getElementById('hora_input').value;
            if (!fecha) {
                return null;
            }
            return `${fecha}T${hora || '00:00'}:00`;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function putJson(url, datos) {
            return fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(datos),
            }).then(function (response) {
                return response.json().then(function (data) {
                    return { status: response.status, data: data };
                });
            });
        }

        btnGuardar.addEventListener('click', function () {

            limpiarErrores();

            let hayErrores = false;

            // Validación de notas (igual que antes)
            notaInputs.forEach(function (input) {
                const valor = input.value.trim();
                if (valor === '') return;

                const numero = Number(valor);
                if (Number.isNaN(numero) || numero < 1 || numero > 10) {
                    input.classList.add('input-error');
                    const error = document.getElementById('error-' + input.id);
                    if (error) error.textContent = 'La nota debe ser un número entre 1 y 10.';
                    hayErrores = true;
                }
            });

            const fecha = combinarFechaHora();
            if (!fecha) {
                document.getElementById('error-fecha').textContent = 'La fecha es obligatoria.';
                hayErrores = true;
            }

            if (hayErrores) {
                mostrarAlerta('Hay campos con errores. Revisá el formulario.', 'error');
                return;
            }

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

            // ── Payload de datos de mesa (PUT /finales/{id}) ──────────
            const datosMesa = {
                id_materia: document.getElementById('id_materia').value,
                fecha: fecha,
                docente_titular: document.getElementById('docente_titular').value || null,
                docente_vocal: document.getElementById('docente_vocal').value || null,
                aula: document.getElementById('aula').value || null,
            };

            // ── Payload de notas (PUT /mesaFinalPerfil/{id}) ──────────
            const datosNotas = {};
            notaInputs.forEach(function (input) {
                datosNotas[input.name] = input.value;
            });

            const requests = [
                putJson('{{ route('finales.update', $mesa->id_mesa) }}', datosMesa),
            ];

            if (notaInputs.length > 0) {
                requests.push(putJson('{{ route('mesaFinalPerfil.update', $mesa->id_mesa) }}', datosNotas));
            }

            Promise.all(requests)
                .then(function (resultados) {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

                    const conError = resultados.find(function (r) {
                        return r.status < 200 || r.status >= 300;
                    });

                    if (!conError) {
                        guardarValoresOriginales();
                        deshabilitarEdicion();
                        mostrarAlerta('✓ Cambios guardados correctamente.', 'success');
                        return;
                    }

                    if (conError.data.errors) {
                        Object.entries(conError.data.errors).forEach(function ([campo, errores]) {
                            const input = document.getElementById(campo);
                            if (input) input.classList.add('input-error');

                            const error = document.getElementById('error-' + campo);
                            if (error) error.textContent = errores[0];
                        });
                        mostrarAlerta('Hay campos con errores. Revisá el formulario.', 'error');
                    } else {
                        mostrarAlerta(conError.data.message || 'Ocurrió un error.', 'error');
                    }
                })
                .catch(function () {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';
                    mostrarAlerta('Error de conexión. Intentá de nuevo.', 'error');
                });
        });

    });
    </script>

</body>
</html>