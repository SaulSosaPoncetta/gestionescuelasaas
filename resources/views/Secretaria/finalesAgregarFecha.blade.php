<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Final</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/sideBar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filtro.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/alumnosSec.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/materiaPerfil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Secretaria/agregarFinal.css') }}">

    @vite(['resources/css/app.css'])
</head>

<body>
    <button class="mobile-menu-btn" id="menu-btn">
        <i class="ri-menu-line"></i>
    </button>

    @include('partials.sidebar')

    <div class="main">

        <button class="btn-modificar" type="button" onclick="window.location='{{ route('finalesSec') }}'">
            <i class="ri-arrow-left-line"></i>Volver
        </button>
        <br>

        <div class="top-bar">
            <h1>Agregar Final</h1>
        </div>

        <div id="alert-ajax" class="alert" style="display:none;"></div>

        <form id="form-agregar-final">
            @csrf

            <div class="card-container">
                <div class="form-grid agregarFinal-grid">

                    <label>Carrera</label>
                    <div class="input-group">
                        <select id="filtro-carrera">
                            <option value="" selected disabled>Seleccionar</option>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id_carrera }}">{{ $carrera->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label>Materia</label>
                    <div class="input-group">
                        <select name="id_materia" id="id_materia" required disabled>
                            <option value="" selected disabled>Elegí primero una carrera</option>
                            @foreach ($materias as $materia)
                                <option value="{{ $materia->id_materia }}" data-carrera="{{ $materia->id_carrera }}" hidden>
                                    {{ $materia->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-id_materia"></small>
                    </div>

                    <label>Tipo de mesa</label>
                    <div class="input-group">
                        <select name="id_tipo_mesa" id="id_tipo_mesa" required>
                            <option value="" selected disabled>Seleccionar</option>
                            @foreach ($tiposMesa as $tipo)
                                <option value="{{ $tipo->id_tipo_mesa }}">{{ $tipo->descripcion }}</option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-id_tipo_mesa"></small>
                    </div>

                    <label>Llamado</label>
                    <div class="input-group">
                        <select name="id_llamado" id="id_llamado">
                            <option value="" selected>Sin especificar</option>
                            @foreach ($llamados as $llamado)
                                <option value="{{ $llamado->id_llamado }}">{{ $llamado->descripcion }}</option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-id_llamado"></small>
                    </div>

                    <label>Ciclo lectivo</label>
                    <div class="input-group">
                        <select name="id_ciclo" id="id_ciclo" required>
                            <option value="" selected disabled>Seleccionar</option>
                            @foreach ($ciclos as $ciclo)
                                <option value="{{ $ciclo->id_ciclo }}">{{ $ciclo->anio }}</option>
                            @endforeach
                        </select>
                        <small class="error-message" id="error-id_ciclo"></small>
                    </div>

                    <label>Aula</label>
                    <div class="input-group">
                        <div class="fecha-hora-row">
                            <input type="text" name="aula" id="aula" placeholder="Número de aula">
                        </div>
                        <small class="error-message" id="error-aula"></small>
                    </div>

                    <label>Primer Fecha</label>
                    <div class="input-group">
                        <div class="fecha-hora-row">
                            <input type="date" id="fecha_final_1" required>
                            <input type="time" id="hora_final_1" required>
                        </div>
                        <small class="error-message" id="error-fecha_1_desde"></small>
                    </div>

                    <label>Segunda Fecha</label>
                    <div class="input-group">
                        <div class="fecha-hora-row">
                            <input type="date" id="fecha_final_2">
                            <input type="time" id="hora_final_2">
                        </div>
                        <small class="error-message" id="error-fecha_2_desde"></small>
                    </div>


                </div>
            </div>

            <div class="table-container">

                <div class="top-bar">
                    <h1>Docentes Asignados</h1>
                </div>

                <div class="dropdown" id="docente-dropdown">
                    <input
                        type="text"
                        id="buscar-docente"
                        class="input-buscar-docente"
                        placeholder="Buscar docente"
                        autocomplete="off"
                    >
                    <div class="dropdown-menu" id="docente-dropdown-menu"></div>
                </div>
                <small class="error-message" id="error-docente_titular"></small>

                <table class="cursada-table" id="tabla-docentes-asignados">
                    <thead>
                        <tr>
                            <th>Nombre y Apellido</th>
                            <th style="width:160px;">Cargo</th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>

                    <tbody id="tabla-docentes-body">
                        <!-- Filas agregadas por JS al elegir un docente del buscador -->
                    </tbody>
                </table>

                <input type="hidden" name="docente_titular" id="docente_titular">
                <input type="hidden" name="docente_vocal" id="docente_vocal">

            </div>

            {{-- OJO: id_usuario_administracion es required en store(). Se manda
                 automáticamente con el id del usuario logueado (se asume que
                 quien carga el final es un Administrador). Si esa asunción no
                 aplica, ajustar acá o aflojar la validación en el controller. --}}
            <input type="hidden" name="id_usuario_administracion" id="id_usuario_administracion" value="{{ auth()->id() }}">

            <div class="form-actions">
                <button type="submit" class="btn-primary" id="btn-guardar">
                    <i class="ri-save-line"></i> Guardar
                </button>
            </div>

        </form>

    </div>

    @include('partials.footer')

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ─── Filtro Carrera → Materia ──────────────────────────────
        const filtroCarrera = document.getElementById('filtro-carrera');
        const selectMateria = document.getElementById('id_materia');
        const opcionesMateria = Array.from(selectMateria.querySelectorAll('option[data-carrera]'));

        filtroCarrera.addEventListener('change', function () {
            const idCarrera = filtroCarrera.value;

            opcionesMateria.forEach(function (option) {
                const coincide = option.dataset.carrera === idCarrera;
                option.hidden = !coincide;
                option.disabled = !coincide;
            });

            selectMateria.value = '';
            selectMateria.disabled = false;
        });

        // Docentes reales, servidos por MesaExamenController::agregarFechaForm()
        const docentes = @json($docentes->values());

        const buscarInput   = document.getElementById('buscar-docente');
        const dropdownMenu  = document.getElementById('docente-dropdown-menu');
        const tablaBody     = document.getElementById('tabla-docentes-body');
        const inputTitular  = document.getElementById('docente_titular');
        const inputVocal    = document.getElementById('docente_vocal');
        const alertAjax     = document.getElementById('alert-ajax');
        const form          = document.getElementById('form-agregar-final');
        const btnGuardar    = document.getElementById('btn-guardar');

        // ─── Buscador de docentes ──────────────────────────────────
        buscarInput.addEventListener('input', function () {
            const term = buscarInput.value.trim().toLowerCase();
            dropdownMenu.innerHTML = '';

            if (term.length === 0) {
                dropdownMenu.style.display = 'none';
                return;
            }

            const asignadosIds = [inputTitular.value, inputVocal.value].filter(Boolean).map(Number);

            const resultados = docentes.filter(function (d) {
                return d.nombre.toLowerCase().includes(term) && !asignadosIds.includes(d.id_usuario);
            }).slice(0, 8);

            if (resultados.length === 0) {
                dropdownMenu.style.display = 'none';
                return;
            }

            resultados.forEach(function (docente) {
                const item = document.createElement('div');
                item.className = 'dropdown-item';
                item.textContent = docente.nombre;
                item.addEventListener('click', function () {
                    agregarDocente(docente);
                    buscarInput.value = '';
                    dropdownMenu.innerHTML = '';
                    dropdownMenu.style.display = 'none';
                });
                dropdownMenu.appendChild(item);
            });

            dropdownMenu.style.display = 'block';
        });

        document.addEventListener('click', function (e) {
            if (!document.getElementById('docente-dropdown').contains(e.target)) {
                dropdownMenu.style.display = 'none';
            }
        });

        // ─── Agregar / quitar filas de la tabla ────────────────────
        function agregarDocente(docente) {
            const cargoDefault = inputTitular.value === '' ? 'titular' : 'vocal';

            if (cargoDefault === 'titular') {
                inputTitular.value = docente.id_usuario;
            } else if (inputVocal.value === '') {
                inputVocal.value = docente.id_usuario;
            } else {
                mostrarAlerta('Ya asignaste titular y vocal. Quitá uno para agregar otro.', 'error');
                return;
            }

            const row = document.createElement('tr');
            row.dataset.idUsuario = docente.id_usuario;

            const cargoOptions = ['titular', 'vocal'].map(function (c) {
                const selected = c === cargoDefault ? 'selected' : '';
                const label = c === 'titular' ? 'Titular' : 'Vocal';
                return `<option value="${c}" ${selected}>${label}</option>`;
            }).join('');

            row.innerHTML = `
                <td>${docente.nombre}</td>
                <td>
                    <select class="cargo-select">${cargoOptions}</select>
                </td>
                <td>
                    <button type="button" class="btn-table btn-quitar-docente" title="Quitar">
                        <i class="ri-close-line"></i>
                    </button>
                </td>
            `;

            tablaBody.appendChild(row);

            row.querySelector('.cargo-select').addEventListener('change', function (e) {
                actualizarCargo(docente.id_usuario, e.target.value, row);
            });

            row.querySelector('.btn-quitar-docente').addEventListener('click', function () {
                quitarDocente(docente.id_usuario, row);
            });
        }

        function actualizarCargo(idUsuario, nuevoCargo, row) {
            const yaEsTitular = Number(inputTitular.value) === idUsuario;
            const yaEsVocal = Number(inputVocal.value) === idUsuario;

            if (nuevoCargo === 'titular' && inputTitular.value !== '' && !yaEsTitular) {
                mostrarAlerta('Ya hay un titular asignado. Quitalo primero.', 'error');
                row.querySelector('.cargo-select').value = yaEsVocal ? 'vocal' : 'titular';
                return;
            }
            if (nuevoCargo === 'vocal' && inputVocal.value !== '' && !yaEsVocal) {
                mostrarAlerta('Ya hay un vocal asignado. Quitalo primero.', 'error');
                row.querySelector('.cargo-select').value = yaEsTitular ? 'titular' : 'vocal';
                return;
            }

            if (yaEsTitular) inputTitular.value = '';
            if (yaEsVocal) inputVocal.value = '';

            if (nuevoCargo === 'titular') {
                inputTitular.value = idUsuario;
            } else {
                inputVocal.value = idUsuario;
            }
        }

        function quitarDocente(idUsuario, row) {
            if (Number(inputTitular.value) === idUsuario) inputTitular.value = '';
            if (Number(inputVocal.value) === idUsuario) inputVocal.value = '';
            row.remove();
        }

        // ─── Alertas y errores ──────────────────────────────────────
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
            document.querySelectorAll('.error-message').forEach(function (el) {
                el.textContent = '';
            });
        }

        // ─── Combinar fecha + hora en un solo string ISO ────────────
        function combinarFechaHora(fechaId, horaId) {
            const fecha = document.getElementById(fechaId).value;
            const hora = document.getElementById(horaId).value;

            if (!fecha) {
                return null;
            }

            return `${fecha}T${hora || '00:00'}:00`;
        }

        // ─── Submit ───────────────────────────────────────────────
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            limpiarErrores();
            alertAjax.style.display = 'none';

            if (!inputTitular.value) {
                mostrarAlerta('Falta asignar un docente titular.', 'error');
                return;
            }

            const fecha1 = combinarFechaHora('fecha_final_1', 'hora_final_1');
            if (!fecha1) {
                document.getElementById('error-fecha_1_desde').textContent = 'La primera fecha es obligatoria.';
                mostrarAlerta('Revisá el formulario, hay campos obligatorios sin completar.', 'error');
                return;
            }

            const fecha2 = combinarFechaHora('fecha_final_2', 'hora_final_2');

            const datos = {
                id_materia: document.getElementById('id_materia').value,
                id_tipo_mesa: document.getElementById('id_tipo_mesa').value,
                id_llamado: document.getElementById('id_llamado').value || null,
                id_ciclo: document.getElementById('id_ciclo').value,
                id_usuario_administracion: document.getElementById('id_usuario_administracion').value,
                aula: document.getElementById('aula').value || null,
                docente_titular: inputTitular.value,
                // 'habilitado' ya no se manda desde acá: el service lo
                // fuerza a true por default en create().
                docente_vocal: inputVocal.value || null,
                fecha_1_desde: fecha1,
                fecha_2_desde: fecha2,
            };

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('{{ route('finales.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(datos),
            })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { status: response.status, data: data };
                });
            })
            .then(function ({ status, data }) {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="ri-save-line"></i> Guardar';

                if (status === 201) {
                    mostrarAlerta('✓ Mesa(s) creada(s) correctamente.', 'success');
                    setTimeout(function () {
                        window.location = '{{ route('finalesSec') }}';
                    }, 1200);
                    return;
                }

                if (data.errors) {
                    Object.entries(data.errors).forEach(function ([campo, errores]) {
                        const error = document.getElementById('error-' + campo);
                        if (error) {
                            error.textContent = errores[0];
                        }
                    });
                    mostrarAlerta('Hay campos con errores. Revisá el formulario.', 'error');
                } else {
                    mostrarAlerta(data.message || 'Ocurrió un error.', 'error');
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