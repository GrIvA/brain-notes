<div class="tag-block-container">
    <div class="tags-cloud">
        {if $mode == 'filter'}
            <h5><i class="fas fa-tags"></i> Фільтр</h5>
            {foreach $tags as $tag}
                <button 
                    class="tag-badge" 
                    :class="activeTagIds.includes({$tag.id}) ? 'contrast' : 'outline'"
                    @click="toggleTag({$tag.id})"
                >
                    {$tag.name}
                </button>
            {/foreach}
        {elseif $mode == 'manage'}
            {foreach $tags as $tag}
                <span class="tag-badge-manage" id="note-tag-{$tag.id}">
                    <small>{$tag.name}</small>
                    {if $canEdit}
                        <i class="fas fa-times" style="margin-left:5px;"
                           hx-delete="/api/v1/notes/{$note.id}/tags/{$tag.id}"
                           hx-target="#note-tag-{$tag.id}"
                           hx-swap="outerHTML"
                           hx-confirm="Видалити цей тег?"
                        ></i>
                    {/if}
                </span>
            {/foreach}
        {elseif $mode == 'view'}
            {foreach $tags as $tag}
                <button 
                    class="tag-badge-view" 
                    :class="activeTagIds.includes({$tag.id}) ? 'contrast' : 'outline'"
                    @click="toggleTag({$tag.id})"
                >
                    <small>{$tag.name}</small>
                </button>
            {/foreach}
        {/if}
    </div>
</div>
