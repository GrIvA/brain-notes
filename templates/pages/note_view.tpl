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
                
                <div class="panel-content grid">
                    <div>
                        <h5>Статистика</h5>
                        <small>Переглядів: 0 (плейсхолдер)</small>
                    </div>
                    <div>
                        <h5>Дії</h5>
                        <div class="button-group" style="margin-bottom: 1rem;">
                            <a href="#" role="button" class="secondary outline sm">Редагувати</a>
                            <button class="secondary outline sm" hx-get="/api/v1/sections/move-ui/{$note.id}" hx-target="#modal-container">Перенести</button>
                        </div>
                        <h5>Додати тег</h5>
                        {include 'components/tag_autocomplete.tpl'}
                        <hr>
                        <h5>Керування тегами</h5>
                        {include 'components/tag_block.tpl' mode='manage' tags=$tags}
                    </div>
                </div>
            </details>
        {/if}
    </header>

    <section class="note-content article-body">
        {$note.content|markdown}
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
