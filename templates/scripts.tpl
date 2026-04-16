<script>
    const btn = document.getElementById('toggle-sidebar');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    const toggle = () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    };

    btn.onclick = toggle;
    overlay.onclick = toggle;

    // Перемикач тем (Dark/Light)
    const themeBtn = document.getElementById('theme-switcher');
    const themeIcon = document.getElementById('theme-icon');
    const root = document.documentElement;
    
    const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
    const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';

    // Визначення поточної теми
    let currentTheme = localStorage.getItem('pico-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    
    const updateUI = (theme) => {
        root.setAttribute('data-theme', theme);
        themeIcon.innerHTML = theme === 'dark' ? sunIcon : moonIcon;
        localStorage.setItem('pico-theme', theme);
    };

    updateUI(currentTheme);

    themeBtn.onclick = () => {
        currentTheme = currentTheme === 'light' ? 'dark' : 'light';
        updateUI(currentTheme);
    };

    // Закриття при кліку на лінки в мобільному меню
    sidebar.querySelectorAll('a').forEach(a => {
        a.onclick = () => { if(window.innerWidth < 768) toggle(); };
    });
</script>
