{if $notes}
    <div class="note-list">
        {foreach $notes as $note}
            <article class="note-item">
                <header>
                    <a href="/note/{$note.id}"><strong>{$note.title}</strong></a>
                </header>
                <div class="note-excerpt">
                    {$note.content|truncate:200|markdown}
                </div>
                <footer>
                    <small>{$note.created_at|date_format:"%d.%m.%Y"}</small>
                </footer>
            </article>
        {/foreach}
    </div>
{else}
    <p>Нотаток не знайдено за вибраними тегами.</p>
{/if}
