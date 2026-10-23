// ── Botones de acción ─────────────────────────────
document.querySelectorAll('.action-buttons .btn-primary').forEach(btn => {
    btn.addEventListener('click', () => {
        if (btn.dataset.href && btn.dataset.href !== '#') {
            window.location.href = btn.dataset.href;
        }
    });
});

document.querySelectorAll('.btn-dropdown').forEach(button => {

    button.addEventListener('click', (e) => {

        e.stopPropagation();

        const menu = button.nextElementSibling;

        // cerrar otros
        document.querySelectorAll('.dropdown-menu-notas').forEach(drop => {
            if (drop !== menu) {
                drop.classList.remove('show');
            }
        });

        menu.classList.toggle('show');
    });
});

// cerrar al clickear afuera
document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu-notas').forEach(menu => {
        menu.classList.remove('show');
    });
});
