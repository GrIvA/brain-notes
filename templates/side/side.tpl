<aside id="sidebar">
    <div class="sidebar-logo">
        <a href="/{'home'|getPageURL}">
            <img src="/logo2.png" alt="Logo">
        </a>
    </div>

    <nav>
        {if $user}
            <div class="user-hello">
                <strong>Вітаємо, {$user.name}!</strong>
            </div>

            <hr>

            <div class="side-notebook-selector" x-data="sidebarTree({$common.active_notebook_id ?: 0})" x-init="initTree()">
                    <div class="grid" style="margin-bottom: 0.5rem; align-items: center;">
                        <select id="notebook-select" x-model="activeNotebookId" @change="loadTree()">
                            <template x-for="nb in notebooks" :key="nb.id">
                                <option :value="nb.id" x-text="nb.title" :selected="nb.id == activeNotebookId"></option>
                            </template>
                        </select>
                        <button class="outline secondary" 
                                style="padding: 0.25rem 0.5rem; margin-bottom: 0;" 
                                :disabled="!selectedSectionId"
                                :title="selectedSectionId ? 'Додати нотатку' : 'Спочатку виберіть розділ у дереві'"
                                hx-get="/api/v1/notes/create-ui"
                                :hx-vals="JSON.stringify({ section_id: selectedSectionId })"
                                hx-target="#modal-container">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                <div id="section-tree"></div>
            </div>
        {/if}

        <hr>

        <ul>
            <li><strong>Навігація</strong></li>
            <li><a href="/{'home'|getPageURL}">🏠 Головна</a></li>
            <li><a href="#">📊 Статистика</a></li>
            <li><a href="#">⚙️ Налаштування</a></li>
        </ul>
        <hr>
        <section>
            <small>
                <strong>Службова інфо:</strong><br />
                Статус: <mark>Online</mark><br />
                Версія: {$common.deployment.version}<br />
                IP: {$.server.REMOTE_ADDR}
            </small>
        </section>
        <hr>
        <section>
            <small>
                <strong>Контакти:</strong><br>
                <i class="fa-brands fa-mastodon"></i>&nbsp; {$common.deployment.mastodon}<br>
                <i class="fa-regular fa-envelope"></i>&nbsp; <a href="mailto:{$common.deployment.email}">{$common.deployment.email}</a>
            </small>
        </section>
        <hr>
        
        {if $common.all_tags}
            <section class="sidebar-tags">
                {include 'components/tag_block.tpl' mode='filter' tags=$common.all_tags}
            </section>
        {/if}
    </nav>
</aside>
