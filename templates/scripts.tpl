<!-- Sortable.js -->
<script src="/js/sortable.min.js"></script>

<!-- AimaraJS -->
<link rel="stylesheet" href="/css/Aimara.css">
<script src="/js/Aimara.js"></script>

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

    /* Перемикач тем (Dark/Light) */
    const themeBtn = document.getElementById('theme-switcher');
    const themeIcon = document.getElementById('theme-icon');
    const root = document.documentElement;
    
    const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
    const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';

    /* Визначення поточної теми (пріоритет темі з сервера) */
    let currentTheme = window.BN_THEME || 'light';
    
    const updateUI = (theme, initial = false) => {
        if (!initial) {
            root.setAttribute('data-theme', theme);
            /* Зберігаємо в Cookie на 1 рік */
            document.cookie = `pico-theme=${theme}; path=/; max-age=${60*60*24*365}; SameSite=Lax`;
        }
        
        themeIcon.innerHTML = theme === 'dark' ? sunIcon : moonIcon;
        
        /* Повідомляємо компоненти про зміну теми */
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme } }));
    };

    updateUI(currentTheme, true);

    themeBtn.onclick = () => {
        currentTheme = currentTheme === 'light' ? 'dark' : 'light';
        updateUI(currentTheme);
    };

    /* Alpine.js Sidebar Component */
    function sidebarTree(initialNotebookId = 0, initialSectionId = 0) {
        const instance = {
            notebooks: [],
            activeNotebookId: initialNotebookId,
            selectedSectionId: initialSectionId,
            treeInstance: null,
            
            async initTree() {
                try {
                    const response = await fetch('/api/v1/notebooks');
                    if (response.ok) {
                        this.notebooks = await response.json();
                        
                        /* Перевіряємо, чи має користувач доступ до поточного activeNotebookId */
                        const hasAccess = this.notebooks.some(nb => nb.id == this.activeNotebookId);

                        /* Якщо ID ще не встановлено або немає доступу, шукаємо дефолтний */
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
                            /* Зберігаємо в Cookie та завантажуємо дерево */
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
                if (!this.activeNotebookId) {
                    if (this.treeInstance) {
                        this.treeInstance.nodes = [];
                        this.treeInstance.drawTree();
                    }
                    return;
                }
                
                /* Ми скидаємо selectedSectionId тільки якщо він не був переданий при ініціалізації (тобто він не з URL) */
                /* В іншому випадку ми зберігаємо його для підсвічування після завантаження */
                
                this.saveActiveNotebook();
                
                try {
                    const response = await fetch(`/api/v1/notebooks/${this.activeNotebookId}/tree`);
                    if (response.ok) {
                        const treeData = await response.json();
                        this.renderAimaraTree(treeData);
                    }
                } catch (e) {
                    console.error('Failed to load tree', e);
                }
            },

            renderAimaraTree(data) {
                this.treeInstance = new Tree('section-tree');
                
                /* Реєструємо колбек для переініціалізації Sortable після кожного перемальовування дерева */
                this.treeInstance.afterDraw = () => {
                    this.initSortable();
                    /* Повідомляємо HTMX про нові елементи (наприклад, кнопки дій) */
                    htmx.process(document.getElementById('section-tree'));
                };

                /* Define actions callback */
                this.treeInstance.onRenderActions = (node) => {
                    if (window.BN_PAGE_ID !== '1') return null;

                    var actions = document.createElement('span');
                    actions.className = 'node-actions';
                    
                    /* Add Section button */
                    var addSec = document.createElement('i');
                    addSec.className = 'fas fa-plus-circle node-action-btn';
                    addSec.title = 'Додати підрозділ';
                    addSec.setAttribute('hx-get', '/api/v1/sections/create-ui?notebook_id=' + this.activeNotebookId + '&parent_id=' + node.id);
                    addSec.setAttribute('hx-target', '#modal-container');
                    actions.appendChild(addSec);

                    /* Add Note button */
                    var addNote = document.createElement('i');
                    addNote.className = 'fas fa-file-circle-plus node-action-btn';
                    addNote.title = 'Додати нотатку';
                    addNote.setAttribute('hx-get', '/api/v1/notes/create-ui?section_id=' + node.id);
                    addNote.setAttribute('hx-target', '#modal-container');
                    actions.appendChild(addNote);

                    return actions;
                };

                /* Recursive builder */
                let initialNodeToSelect = null;
                const buildNodes = (nodes, parentNode = null) => {
                    nodes.forEach(dataNode => {
                        const node = this.treeInstance.createNode(
                            dataNode.title, 
                            dataNode.id, 
                            'fa-solid fa-folder', 
                            parentNode, 
                            false
                        );
                        
                        if (node.id == this.selectedSectionId) {
                            initialNodeToSelect = node;
                        }

                        if (dataNode.children && dataNode.children.length > 0) {
                            buildNodes(dataNode.children, node);
                        }
                    });
                };

                buildNodes(data);
                
                this.treeInstance.onNodeSelected = (node) => {
                    this.selectedSectionId = node.id;
                    
                    if (window.BN_PAGE_ID !== '1') {
                        /* Редирект на головну з параметром */
                        window.location.href = '/home?section_id=' + node.id;
                        return;
                    }

                    console.log('Selected section:', this.selectedSectionId);
                    
                    /* Викликаємо фільтрацію нотаток через HTMX */
                    htmx.ajax('GET', '/api/v1/notes/list', {
                        values: { section_id: node.id },
                        target: '#note-list'
                    });

                    /* Оновлюємо HTMX елементи, що залежать від цього ID (наприклад, кнопка +) */
                    this.$nextTick(() => {
                        htmx.process(document.getElementById('sidebar'));
                    });
                };

                if (initialNodeToSelect) {
                    this.treeInstance.selectedNode = initialNodeToSelect;
                    /* Розгортаємо батьківські вузли */
                    let p = initialNodeToSelect.parent;
                    while (p) {
                        p.expanded = true;
                        p = p.parent;
                    }
                }

                this.treeInstance.drawTree();
            },

            initSortable() {
                const treeContainer = document.getElementById('section-tree');
                const uls = treeContainer.querySelectorAll('ul');
                
                uls.forEach(el => {
                    /* Запобігаємо подвійній ініціалізації */
                    if (el.sortable) {
                        el.sortable.destroy();
                    }

                    el.sortable = new Sortable(el, {
                        group: {
                            name: 'nested',
                            put: ['nested', 'notes']
                        },
                        animation: 150,
                        fallbackOnBody: true,
                        swapThreshold: 0.65,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        dragClass: 'sortable-drag',
                        onEnd: (evt) => {
                            if (evt.item.getAttribute('data-type') === 'note') return;

                            const itemEl = evt.item;
                            const sectionId = itemEl.getAttribute('data-id');
                            const newParentId = evt.to.getAttribute('data-id');
                            
                            if (evt.from === evt.to && evt.oldIndex === evt.newIndex) return;

                            /* Використовуємо нативний fetch для уникнення конфліктів HTMX при асинхронних операціях */
                            fetch(`/api/v1/sections/${sectionId}/move`, {
                                method: 'PATCH',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ parent_id: newParentId === '0' ? null : newParentId })
                            }).then(res => {
                                if (res.ok) this.loadTree();
                            });
                        },
                        onAdd: (evt) => {
                            if (evt.item.getAttribute('data-type') === 'note') {
                                const noteId = evt.item.getAttribute('data-id');
                                const targetSectionId = evt.to.getAttribute('data-id');
                                
                                if (!targetSectionId || targetSectionId === '0') {
                                    alert('Нотатки не можна переміщувати в корінь зошита. Оберіть розділ.');
                                    this.loadTree();
                                    return;
                                }

                                fetch('/api/v1/notes/move', {
                                    method: 'PATCH',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ 
                                        note_ids: [parseInt(noteId)],
                                        target_section_id: parseInt(targetSectionId) 
                                    })
                                }).then(res => {
                                    if (res.ok) {
                                        if (window.BN_PAGE_ID === '4') {
                                            location.reload();
                                        } else {
                                            this.loadTree();
                                            const noteList = document.getElementById('note-list');
                                            if (noteList) {
                                                htmx.trigger(noteList, 'refresh');
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    });
                });

                this.initNoteDropZone();
            },

            initNoteDropZone() {
                const noteHandle = document.querySelector('.note-drag-handle');
                if (noteHandle && !noteHandle.classList.contains('sortable-initialized')) {
                    new Sortable(noteHandle.parentElement, {
                        group: {
                            name: 'notes',
                            pull: 'clone',
                            put: false
                        },
                        sort: false,
                        animation: 150
                    });
                    noteHandle.classList.add('sortable-initialized');
                }
            }
        };

        window.sidebarTreeInstance = instance;
        
        /* Слухаємо подію для оновлення сайдбару */
        document.addEventListener('refresh-sidebar', () => {
            instance.initTree();
        });

        return instance;
    }

    /* Закриття при кліку на лінки в мобільному меню */
    sidebar.querySelectorAll('a').forEach(a => {
        a.onclick = () => { if(window.innerWidth < 768) toggle(); };
    });

    document.body.addEventListener('close-decrypt-modal', function() {
        document.getElementById('decrypt-modal')?.remove();
    });
{/ignore}
</script>
