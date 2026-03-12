// Admin JS

// Mobile sidebar toggle
const adminBurger = document.getElementById('adminBurger');
const adminSidebar = document.querySelector('.admin-sidebar');
if (adminBurger && adminSidebar) {
    adminBurger.addEventListener('click', () => {
        adminSidebar.classList.toggle('open');
    });
}

// Image preview on file input
const fileInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
fileInputs.forEach(input => {
    const preview = input.id ? document.getElementById(input.id + 'Preview') : null;
    if (!preview) return;
    input.addEventListener('change', () => {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
            reader.readAsDataURL(file);
        }
    });
});

// Flash auto-dismiss
const flash = document.querySelector('.flash');
if (flash) setTimeout(() => flash.style.opacity = '0', 3500);
