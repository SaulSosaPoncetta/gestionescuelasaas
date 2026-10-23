// ─── Sidebar toggle ───────────────────────────────────────────────────

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

// Submenú de usuarios para hacerlo aparecer cuando el usuario haga click

const usuariosToggle = document.getElementById('usuarios-toggle');
const usuariosSubmenu = document.getElementById('usuarios-submenu');

usuariosToggle.addEventListener('click', function (e) {
    e.preventDefault();
    this.classList.toggle('open');
    usuariosSubmenu.classList.toggle('open');
});