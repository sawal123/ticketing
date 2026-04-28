/**
 * Admin Panel Javascript
 * Extracted from admin_template.html
 */

document.addEventListener('livewire:navigated', function() {
    // Theme initialization
    initTheme();

    // Lucide Icons initialization
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Sidebar overlay click listener
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
});

// Re-initialize icons after Livewire updates (e.g. tab switching, pagination)
document.addEventListener('livewire:init', function() {
    Livewire.hook('morph.updated', (el, component) => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
});

// Profile dropdown click outside listener
document.addEventListener('click', function(e) {
    const profileBtn = document.getElementById('profileBtn');
    const dropdown = document.getElementById('profileDropdown');
    if (profileBtn && dropdown && !profileBtn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
        const chevron = document.getElementById('chevronProfile');
        if (chevron) chevron.style.transform = 'rotate(0deg)';
    }
});

window.openSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar && overlay) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100');
        document.body.style.overflow = 'hidden';
    }
};

window.closeSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar && overlay) {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        overlay.classList.remove('opacity-100');
        setTimeout(() => overlay.classList.add('hidden'), 300);
        document.body.style.overflow = '';
    }
};

window.setActiveSidebar = function(el) {
    document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));
    el.classList.add('active');
};

window.toggleTheme = function() {
    const html = document.documentElement;
    html.classList.toggle('dark');
    const isDark = html.classList.contains('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateThemeIcons();
};

function updateThemeIcons() {
    const isDark = document.documentElement.classList.contains('dark');
    const sun = document.getElementById('iconSun');
    const moon = document.getElementById('iconMoon');
    if (sun) sun.classList.toggle('hidden', !isDark);
    if (moon) moon.classList.toggle('hidden', isDark);
}

function initTheme() {
    const saved = localStorage.getItem('theme');
    const prefers = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (saved === 'dark' || (!saved && prefers)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    updateThemeIcons();
}

window.toggleProfileDropdown = function() {
    const dropdown = document.getElementById('profileDropdown');
    const chevron = document.getElementById('chevronProfile');
    if (dropdown) {
        dropdown.classList.toggle('active');
        if (chevron) {
            chevron.style.transform = dropdown.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
        }
    }
};
