// ── Sidebar mobile ────────────────────────────────
const menuBtn = document.getElementById('menu-btn');
const sidebar  = document.querySelector('.sidebar');
const overlay  = document.getElementById('overlay');

menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
});
overlay.addEventListener('click', () => {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
});

// ── Tabs ──────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Desactivar todos
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

        // Activar el clickeado
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});

// ── Botón solicitar ───────────────────────────────
const btnSolicitar = document.querySelector('.btn-solicitar');
btnSolicitar.addEventListener('click', () => {
    // TODO: redirigir a la pantalla de solicitud de constancia
    console.log('Solicitar constancia');
});

// ── Lupas ─────────────────────────────────────────
document.querySelectorAll('.btn-lupa').forEach(btn => {
    btn.addEventListener('click', () => {
        // TODO: redirigir al detalle de la constancia
        const id = btn.dataset.id;
        console.log('Ver detalle constancia id:', id);
    });
});

// ── Imprimir ──────────────────────────────────────
document.getElementById('btn-print').addEventListener('click', () => {
    // TODO: implementar generación de PDF
    // Opción A (simple): window.print()
    // Opción B (recomendada): fetch a una ruta Laravel que devuelva un PDF con DomPDF
    window.print();
});