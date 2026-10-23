document.addEventListener('DOMContentLoaded', () => {


    // ─── Autocomplete Materia ─────────────────────────────────────────────
    const nombre = [ 'Lucio R','Nico I','Inia'];

    const nombreAlumno = document.getElementById('nombre-Alumno');
    const materiaList  = document.getElementById('materia-list');
    let activeIndex    = -1;

    function escapeRe(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlight(text, query) {
        if (!query) return text;
        return text.replace(new RegExp(`(${escapeRe(query)})`, 'gi'), '<strong>$1</strong>');
    }

    function showDropdown() { materiaList.classList.add('show'); }
    function hideDropdown() { materiaList.classList.remove('show'); activeIndex = -1; }

    function renderMateria(query) {
        const q = query.trim();
        if (!q) { hideDropdown(); return; }

        const results = MATERIAS.filter(m => m.toLowerCase().includes(q.toLowerCase()));
        materiaList.innerHTML = '';

        if (results.length === 0) {
            materiaList.innerHTML = `<div class="dropdown-item disabled">Sin resultados para "<strong>${q}</strong>"</div>`;
        } else {
            results.forEach((m, i) => {
                const item = document.createElement('div');
                item.className = 'dropdown-item';
                item.innerHTML = `<span>${highlight(m, q)}</span>`;
                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    materiaInput.value = m;
                    hideDropdown();
                });
                materiaList.appendChild(item);
            });
        }

        activeIndex = -1;
        showDropdown();
    }

    materiaInput?.addEventListener('input', () => renderMateria(materiaInput.value));
    materiaInput?.addEventListener('focus', () => { if (materiaInput.value.trim()) renderMateria(materiaInput.value); });
    materiaInput?.addEventListener('blur', () => setTimeout(hideDropdown, 150));

    materiaInput?.addEventListener('keydown', (e) => {
        const items = materiaList.querySelectorAll('.dropdown-item:not(.disabled)');
        if (!materiaList.classList.contains('show')) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            materiaInput.value = items[activeIndex].querySelector('span').textContent;
            hideDropdown();
        } else if (e.key === 'Escape') {
            hideDropdown();
        }

        items.forEach((item, i) => item.classList.toggle('active', i === activeIndex));
        if (activeIndex >= 0) items[activeIndex]?.scrollIntoView({ block: 'nearest' });
    });
});
