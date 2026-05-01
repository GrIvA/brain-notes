<!-- jQuery (required for jsTree) -->
<script src="/js/jquery.min.js"></script>

<!-- jsTree -->
<link id="jstree-theme-link" rel="stylesheet" href="/js/jsTree/themes/default/style.min.css">
<script src="/js/jsTree/jstree.min.js"></script>

<script>
{ignore}
    const btn = document.getElementById('toggle-sidebar');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    const toggle = () => {
        if (sidebar && overlay) {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        }
    };

    if (btn) btn.onclick = toggle;
    if (overlay) overlay.onclick = toggle;

    // Перемикач тем (Dark/Light)
    const themeBtn = document.getElementById('theme-switcher');
    const themeIcon = document.getElementById('theme-icon');
    const root = document.documentElement;
    const jstreeThemeLink = document.getElementById('jstree-theme-link');
    
    const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
    const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';

    // Визначення поточної теми (пріоритет темі з сервера)
    let currentTheme = '{$common.theme}';
    
    const updateUI = (theme, initial = false) => {
        if (!initial) {
            root.setAttribute('data-theme', theme);
            // Зберігаємо в Cookie на 1 рік
            document.cookie = `pico-theme=${theme}; path=/; max-age=${60*60*24*365}; SameSite=Lax`;
        }
        
        themeIcon.innerHTML = theme === 'dark' ? sunIcon : moonIcon;
        
        // Оновлення теми jsTree
        if (jstreeThemeLink) {
            const themePath = theme === 'dark' ? '/js/jsTree/themes/default-dark/style.min.css' : '/js/jsTree/themes/default/style.min.css';
            jstreeThemeLink.setAttribute('href', themePath);
        }

        // Повідомляємо компоненти про зміну теми
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme } }));
    };

    updateUI(currentTheme, true);

    themeBtn.onclick = () => {
        currentTheme = currentTheme === 'light' ? 'dark' : 'light';
        updateUI(currentTheme);
    };

    // Alpine.js Sidebar Component
    function sidebarTree(initialNotebookId = 0) {
        return {
            notebooks: [],
            activeNotebookId: initialNotebookId,
            
            async initTree() {
                // Слухаємо зміну теми
                window.addEventListener('theme-changed', (e) => {
                    const $tree = $('#section-tree');
                    if ($tree.jstree(true)) {
                        const newTheme = e.detail.theme === 'dark' ? 'default-dark' : 'default';
                        $tree.jstree(true).set_theme(newTheme);
                    }
                });

                try {
                    const response = await fetch('/api/v1/notebooks');
                    if (response.ok) {
                        this.notebooks = await response.json();
                        
                        // Перевіряємо, чи має користувач доступ до поточного activeNotebookId
                        const hasAccess = this.notebooks.some(nb => nb.id == this.activeNotebookId);

                        // Якщо ID ще не встановлено або немає доступу, шукаємо дефолтний
                        if (!hasAccess || !this.activeNotebookId || this.activeNotebookId === 0) {
                            const defaultNb = this.notebooks.find(nb => (parseInt(nb.attributes) & 1) === 1);
                            if (defaultNb) {
                                this.activeNotebookId = defaultNb.id;
                            } else if (this.notebooks.length > 0) {
                                this.activeNotebookId = this.notebooks[0].id;
                            } else {
                                this.activeNotebookId = 0;
                            }
                        }

                        if (this.activeNotebookId && this.activeNotebookId !== 0) {
                            // Зберігаємо в Cookie та завантажуємо дерево
                            this.saveActiveNotebook();
                            this.loadTree();
                        }
                    }
                } catch (e) {
                    console.error('Failed to load notebooks', e);
                }
            },

            saveActiveNotebook() {
                if (this.activeNotebookId) {
                    document.cookie = `active_notebook_id=${this.activeNotebookId}; path=/; max-age=${60*60*24*365}; SameSite=Lax`;
                }
            },

            async loadTree() {
                if (!this.activeNotebookId) return;
                this.saveActiveNotebook();
                
                try {
                    const response = await fetch(`/api/v1/notebooks/${this.activeNotebookId}/tree`);
                    if (response.ok) {
                        const rawData = await response.json();
                        const treeData = this.mapTreeData(rawData);
                        
                        this.renderJsTree(treeData);
                    }
                } catch (e) {
                    console.error('Failed to load tree', e);
                }
            },

            mapTreeData(data) {
                return data.map(node => ({
                    id: node.id,
                    text: node.title,
                    state: { opened: true },
                    children: node.children ? this.mapTreeData(node.children) : []
                }));
            },

            renderJsTree(data) {
                const $tree = $('#section-tree');
                if ($tree.jstree(true)) {
                    $tree.jstree(true).settings.core.data = data;
                    $tree.jstree(true).refresh();
                } else {
                    $tree.jstree({
                        'core': {
                            'data': data,
                            'themes': {
                                'name': currentTheme === 'dark' ? 'default-dark' : 'default',
                                'responsive': true
                            }
                        },
                        'plugins': ['wholerow', 'types']
                    });

                    $tree.on('select_node.jstree', (e, data) => {
                        // Тут буде логіка відкриття нотаток у розділі
                        console.log('Selected section:', data.node.id);
                    });
                }
            }
        };
    }

    // Закриття при кліку на лінки в мобільному меню
    sidebar.querySelectorAll('a').forEach(a => {
        a.onclick = () => { if(window.innerWidth < 768) toggle(); };
    });
{/ignore}
</script>
