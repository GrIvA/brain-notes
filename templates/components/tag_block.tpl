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
            let url = '/api/v1/notes/list?' + this.activeTagIds.map(id => 'tag_ids[]=' + id).join('&');
            htmx.ajax('GET', url, {
                target: '#note-list'
            });
        }
     }">
{/ignore}
    <hr>
    <h5><i class="fas fa-tags"></i> Теги</h5>
    <div class="tags-cloud">
        {if $mode == 'filter'}
            {foreach $tags as $tag}
                <button 
                    class="tag-badge" 
                    :class="activeTagIds.includes({$tag.id}) ? 'contrast' : 'outline'"
                    @click="toggleTag({$tag.id})"
                    style="margin-right: 0.5rem; margin-bottom: 0.5rem; padding: 0.2rem 0.6rem; font-size: 0.85rem;"
                >
                    {$tag.name}
                </button>
            {/foreach}
        {elseif $mode == 'manage'}
            {foreach $tags as $tag}
                <span class="tag-badge-manage" id="note-tag-{$tag.id}" style="display: inline-flex; align-items: center; margin-right: 0.5rem; margin-bottom: 0.5rem; border: 1px solid var(--pico-muted-border-color); border-radius: var(--pico-border-radius); padding: 0.2rem 0.5rem;">
                    <small>{$tag.name}</small>
                    {if $canEdit}
                        <i class="fas fa-times" 
                           style="margin-left: 0.5rem; cursor: pointer; color: var(--pico-form-element-invalid-border-color);"
                           hx-delete="/api/v1/notes/{$note.id}/tags/{$tag.id}"
                           hx-target="#note-tag-{$tag.id}"
                           hx-swap="outerHTML"
                           hx-confirm="Видалити цей тег?"
                        ></i>
                    {/if}
                </span>
            {/foreach}
            {if $canEdit}
                <div class="add-tag-inline" style="margin-top: 1rem; max-width: 400px;">
                    <small>Додати новий тег:</small>
                    {include 'components/tag_autocomplete.tpl'}
                </div>
            {/if}
        {/if}
    </div>
</div>
