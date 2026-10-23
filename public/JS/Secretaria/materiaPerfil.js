document.addEventListener('DOMContentLoaded', () => {

    // ─── Sidebar ──────────────────────────────────────────────────────────
    const menuBtn = document.getElementById('menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');

    if (menuBtn) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
    }
    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });

    // ─── Campos y botones ─────────────────────────────────────────────────
    const btnModificar = document.getElementById('btn-modificar');
    const btnGuardar   = document.getElementById('btn-guardar');
    const btnCancelar  = document.getElementById('btn-cancelar');

    const campos = document.querySelectorAll(
        '.form-grid input:not(#profesor-input), .form-grid select, .checkbox-group input[type="checkbox"]'
    );

    console.log(campos);

    let valoresOriginales = [];

    function guardarEstado() {
        valoresOriginales = [...campos].map(c =>
            c.type === 'checkbox' ? c.checked : c.value
        );
        valorProfesorOriginal = profInput ? profInput.value : '';
    }

    function restaurarEstado() {
        campos.forEach((c, i) => {
            if (c.type === 'checkbox') c.checked = valoresOriginales[i];
            else c.value = valoresOriginales[i];
        });
        if (profInput) profInput.value = valorProfesorOriginal;
    }

    function habilitarEdicion() {
        campos.forEach(c => c.disabled = false);
        if (profInput) {
            profInput.disabled = false;
            if (!bsProf) bsProf = new bootstrap.Dropdown(profInput, { autoClose: true });
        }
        btnGuardar.disabled  = false;
        btnCancelar.disabled = false;
        btnModificar.classList.add('activo');
        btnModificar.innerHTML = '<i class="ri-edit-line"></i> Editando...';
    }

    function deshabilitarEdicion() {
        campos.forEach(c => c.disabled = true);
        if (profInput) profInput.disabled = true;
        btnGuardar.disabled  = true;
        btnCancelar.disabled = true;
        btnModificar.classList.remove('activo');
        btnModificar.innerHTML = '<i class="ri-edit-line"></i> Modificar';
    }

    btnModificar.addEventListener('click', () => {
        guardarEstado();
        habilitarEdicion();
    });

    btnCancelar.addEventListener('click', () => {
        restaurarEstado();
        deshabilitarEdicion();
    });

    // ─── Autocomplete Profesor ────────────────────────────────────────────
    const PROFESORES = [
        'García, Roberto', 'González, María', 'López, Carlos',
        'Martínez, Ana', 'Rodríguez, Luis', 'Fernández, Laura',
        'Pérez, Jorge', 'Sánchez, Paula', 'Romero, Diego', 'Torres, Claudia',
    ];

    const profInput = document.getElementById('profesor-input');
    const profList  = document.getElementById('profesor-list');
    let bsProf      = null;
    let valorProfesorOriginal = profInput ? profInput.value : '';

    function escapeRe(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlightProf(text, query) {
        if (!query) return text;
        return text.replace(new RegExp(`(${escapeRe(query)})`, 'gi'), '<strong>$1</strong>');
    }

    function renderProfList(query) {
        if (!bsProf) return;
        const q = query.trim();
        if (!q) { bsProf.hide(); return; }

        const results = PROFESORES.filter(p => p.toLowerCase().includes(q.toLowerCase()));
        profList.innerHTML = '';

        if (results.length === 0) {
            profList.innerHTML = '<li><span class="dropdown-item disabled text-muted">Sin resultados</span></li>';
        } else {
            results.forEach(p => {
                const li = document.createElement('li');
                li.innerHTML = `<a class="dropdown-item d-flex align-items-center gap-2" href="#">
                    <i class="ri-user-line text-secondary"></i>
                    <span>${highlightProf(p, q)}</span>
                </a>`;
                li.querySelector('a').addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    profInput.value = p;
                    bsProf.hide();
                });
                profList.appendChild(li);
            });
        }
        bsProf.show();
    }

    if (profInput) {
        profInput.addEventListener('input', () => renderProfList(profInput.value));
        profInput.addEventListener('focus', () => {
            if (profInput.value.trim()) renderProfList(profInput.value);
        });
        profInput.addEventListener('blur', () => setTimeout(() => bsProf?.hide(), 150));
    }

});
