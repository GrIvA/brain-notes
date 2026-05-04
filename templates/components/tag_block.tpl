{ignore}
<div class="tag-block-container" 
     x-data="{ 
        activeTagIds: [], 
        toggleTag(id) {
            if (this.activeTagIds.includes(id)) {
                this.activeTagIds = this.activeTagIds.filter(i => i !== id);
            } else {
                this.activeTagIds.push(id);
            }
            
            let queryParams = this.activeTagIds.map(id => 'tag_ids[]=' + id).join('&');
            let isHome = window.BN_PAGE_ID === '1';
            
            if (isHome) {
                // На головній — оновлюємо існуючий список
                let url = '/api/v1/notes/list?' + queryParams;
                htmx.ajax('GET', url, {
                    target: '#note-list'
                });
            } else {
                // На інших сторінках — відкриваємо модалку
                if (this.activeTagIds.length > 0) {
                    let url = '/api/v1/notes/list?view=modal&' + queryParams;
                    htmx.ajax('GET', url, {
                        target: '#modal-container'
                    });
                }
            }
        }
     }">
{/ignore}
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
                <span class="tag-badge-manage" id="note-tag-{$tag.id}>
                    <small>{$tag.name}</small>
                    {if $canEdit}
                        <i class="fas fa-times" 
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
                <span class="tag-badge-view">
                    <small>{$tag.name}</small>
                </span>
            {/foreach}
        {/if}
    </div>
</div>
