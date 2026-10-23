// js/Secretaria/administradorCreate.js
document.getElementById('btn-guardar')?.addEventListener('click', () => {
    const btn = document.getElementById('btn-guardar');
    btn.innerHTML = '<i class="ri-check-line"></i> Guardado';
    btn.style.background = '#16a34a';
    setTimeout(() => {
        btn.innerHTML = '<i class="ri-save-line"></i> Guardar';
        btn.style.background = '';
    }, 2000);
});