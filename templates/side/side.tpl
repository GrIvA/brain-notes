<aside id="sidebar">
    <div class="sidebar-logo">
        <a href="/{'home'|getPageURL}">
            <img src="/logo2.png" alt="Logo">
        </a>
        <hr>
    </div>

    <nav>
        {if $user}
            <div class="user-hello">
                <strong>Вітаємо, {$user.name}!</strong>
            </div>

            <div class="side-notebook-selector" x-data="sidebarTree({$common.active_notebook_id ?: 0})" x-init="initTree()">
                    <select id="notebook-select" x-model="activeNotebookId" @change="loadTree()">
                        <template x-for="nb in notebooks" :key="nb.id">
                            <option :value="nb.id" x-text="nb.title" :selected="nb.id == activeNotebookId"></option>
                        </template>
                    </select>
                </div>
                <div id="section-tree"></div>
            </div>
        {/if}
        <ul>
            <li><strong>Навігація</strong></li>
            <li><a href="#">🏠 Головна</a></li>
            <li><a href="#">📊 Дашборд</a></li>
            <li><a href="#">⚙️ Налаштування</a></li>
        </ul>
        <hr>
        <section>
            <small>
                <strong>Службова інфо:</strong><br>
                Статус: <mark>Online</mark><br>
                Версія: 1.2.4
            </small>
        </section>
        <hr>
        <section>
            <small>
                <strong>Контакти:</strong><br>
                📞 +380 44 000 00 00<br>
                📧 <a href="mailto:info@example.com">info@example.com</a>
            </small>
        </section>
        
        {if $common.all_tags}
            <section class="sidebar-tags">
                {include 'components/tag_block.tpl' mode='filter' tags=$common.all_tags}
            </section>
        {/if}
    </nav>
</aside>
