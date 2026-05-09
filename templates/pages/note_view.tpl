{extends 'layouts/main.tpl'}

{block 'content'}
<article class="note-view-container">
    <nav aria-label="breadcrumb">
        <ul>
            {foreach $breadcrumbs as $crumb}
                <li><a href="{$crumb.url}">{$crumb.title}</a></li>
            {/foreach}
            <li>{$note.title}</li>
        </ul>
    </nav>

    <header>
        <h1 class="note-title">{$note.title}</h1>
        
        {if $canEdit}
            <details class="control-panel">
                <summary class="outline contrast">
                    <span class="panel-label">Панель керування</span>
                </summary>
                
                <div class="panel-content">
                    <h5>Основні параметри</h5>
                    <div class="grid">
                        <div>
                            <label for="edit-title">Заголовок</label>
                            <div role="group">
                                <input type="text" id="edit-title" name="title" value="{$note.title}"
                                       hx-put="/api/v1/notes/{$note.id}"
                                       hx-trigger="change"
                                       hx-target=".note-title"
                                       hx-swap="innerHTML"
                                       hx-vals='js:{ title: document.getElementById("edit-title").value }'>

                             <button class="secondary outline sm" hx-get="/api/v1/notes/move-ui/{$note.id}" hx-target="#modal-container">Перенести</button>
                             <button class="outline sm" style="color: var(--pico-form-element-invalid-border-color); border-color: var(--pico-form-element-invalid-border-color);"
                                     hx-delete="/api/v1/notes/{$note.id}"
                                     hx-confirm="Ви впевнені, що хочете видалити цю нотатку?">
                                 Видалити
                             </button>
                            </div>
                        </div>
                    </div>
                    <div class="grid">
                        <div>
                            <button class="contrast" 
                                    hx-get="/api/v1/notes/{$note.id}/edit-ui" 
                                    hx-target="#note-content-area"
                                    hx-swap="innerHTML">
                                <i class="fas fa-edit"></i> Редагувати вміст
                            </button>
                        </div>
                    </div>
                    <hr>
                    <div class="grid">
                        <div>
                            <h5>Дії з тегами</h5>
                            {include 'components/tag_autocomplete.tpl'}
                        </div>
                        <div>
                            <h5>Керування тегами</h5>
                            {include 'components/tag_block.tpl' mode='manage' tags=$tags}
                        </div>
                    </div>
                </div>

                <div class="panel-content">
                    <h5>Шифрування</h5>
                    <form hx-put="/api/v1/notes/{$note.id}" hx-swap="none" hx-on::after-request="if(event.detail.successful) { alert('Дані шифрування оновлено'); location.reload(); }">
                        <div class="grid">
                            {if $isEncrypted}
                                <label for="old_password">
                                    Поточний пароль
                                    <input type="password" id="old_password" name="old_password" required>
                                </label>
                            {/if}
                            <label for="new_password">
                                {if $isEncrypted}Новий пароль (порожньо — зняти){else}Встановити пароль{/if}
                                <input type="password" id="new_password" name="password" {if !$isEncrypted}required{/if}>
                            </label>
                        </div>
                        <button type="submit" class="sm">{if $isEncrypted}Оновити / Зняти{else}Зашифрувати{/if}</button>
                    </form>
                </div>
            </details>
        {/if}
    </header>

    <section class="note-content article-body" id="note-content-area">
        {if $isEncrypted}
            <div style="text-align: center; padding: 2rem;">
                <i class="fa-solid fa-lock" style="font-size: 3rem; color: var(--pico-muted-color); margin-bottom: 1rem;"></i>
                <p>Ця нотатка зашифрована.</p>
                <button class="contrast" 
                        hx-get="/api/v1/notes/decrypt-ui/{$note.id}" 
                        hx-target="#modal-container">
                    Ввести пароль
                </button>
            </div>
            
            {* Автоматично відкриваємо модалку при завантаженні сторінки *}
            <div hx-get="/api/v1/notes/decrypt-ui/{$note.id}" hx-trigger="load" hx-target="#modal-container"></div>
        {else}
            {$note.content|markdown}
        {/if}
    </section>

    <footer>
        <div class="note-meta">
            {if $tags}
                <div class="note-tags-footer" style="margin-bottom: 1rem;">
                    {include 'components/tag_block.tpl' mode='view' tags=$tags}
                </div>
            {/if}
            <div class="stats-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <small>
                    <i class="far fa-calendar-alt"></i> {$note.created_at|date_format:"%d.%m.%Y %H:%M"}
                    {if $note.attributes|attr:1}
                        <span style="margin-left: 1rem; color: var(--pico-primary);">
                            <i class="fa-solid fa-globe"></i> Публічна
                        </span>
                    {else}
                        <span style="margin-left: 1rem; color: var(--pico-muted-color);">
                            <i class="fa-solid fa-lock"></i> Приватна
                        </span>
                    {/if}
                </small>
                <small>
                    <i class="far fa-eye"></i> 0
                </small>
            </div>
        </div>
    </footer>
</article>
{/block}

{block 'css'}
<style>
    .note-content {
        border: 1px solid var(--pico-muted-border-color);
        border-radius: var(--pico-border-radius);
        padding: 1rem;
        margin-bottom: 1rem;
        background-color: var(--pico-card-background-color);
    }
    .note-title {
        margin-bottom: 0.5rem;
    }
    .control-panel {
        margin-bottom: 2rem;
        border: 1px solid var(--pico-muted-border-color);
        border-radius: var(--pico-border-radius);
        padding: 0.5rem;
    }
    .control-panel summary {
        list-style: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        padding: 0.5rem;
    }
    .control-panel summary::-webkit-details-marker {
        display: none;
    }
    .panel-content {
        padding: 1rem;
        border-top: 1px solid var(--pico-muted-border-color);
        margin-top: 0.5rem;
    }
    .tag {
        margin-right: 0.5rem;
        font-size: 0.8rem;
    }
    .button-group {
        display: flex;
        gap: 0.5rem;
    }
    .sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
</style>
{/block}
