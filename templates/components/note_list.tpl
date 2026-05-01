{if $notes}
    <div class="note-list">
        {foreach $notes as $note}
            <article class="note-item">
                <header>
                    <a href="/note/{$note.id}">
                        {if $user && $user.id == $note.user_id}
                            <i class="fa-solid fa-user" title="Ваша нотатка" style="color: var(--pico-secondary); margin-right: 0.3rem;"></i>
                        {elseif $note.attributes|attr:1}
                            <i class="fa-solid fa-users" title="Публічна нотатка" style="color: var(--pico-muted-color); margin-right: 0.3rem;"></i>
                        {/if}
                        {if $note.attributes|attr:1}
                            <i class="fa-solid fa-globe" title="Доступна всім" style="color: var(--pico-primary); margin-right: 0.3rem;"></i>
                        {/if}
                        <strong>{$note.title}</strong>
                    </a>
                </header>
                <div class="note-excerpt">
                    {$note.content|truncate:200|markdown}
                </div>
                {if $note.tags}
                    <div class="note-item-tags" style="margin-top: 0.5rem;">
                        {include 'components/tag_block.tpl' mode='view' tags=$note.tags}
                    </div>
                {/if}
                <footer>
                    <small>{$note.created_at|date_format:"%d.%m.%Y"}</small>
                </footer>
            </article>
        {/foreach}
    </div>
{else}
    <p>Нотаток не знайдено за вибраними тегами.</p>
{/if}
