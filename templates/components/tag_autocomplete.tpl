<script src="/js/tag-autocomplete.js"></script>

<div x-data="tagAutocomplete({$allUserTags|json_encode|escape})" class="tag-autocomplete" style="position: relative;">
    <div class="grid" style="grid-template-columns: 1fr auto; gap: 0.5rem; align-items: start;">
        <div style="position: relative;">
            <input 
                type="text" 
                name="tag" 
                placeholder="Введіть тег..." 
                x-model="query" 
                @input="open = true"
                @click.away="open = false"
                @keydown.escape="open = false"
                style="margin-bottom: 0;"
            >
            <ul x-show="open && filteredTags.length > 0" 
                class="autocomplete-list" 
                style="position: absolute; width: 100%; z-index: 100; list-style: none; padding: 0; margin: 0; border: 1px solid var(--pico-muted-border-color); border-radius: var(--pico-border-radius); background: var(--pico-background-color); max-height: 200px; overflow-y: auto;">
                <template x-for="tag in filteredTags" :key="tag.id">
                    <li @click="selectTag(tag.name)" 
                        style="padding: 0.5rem; cursor: pointer; border-bottom: 1px solid var(--pico-muted-border-color);"
                        class="autocomplete-item">
                        <span x-text="tag.name"></span>
                    </li>
                </template>
            </ul>
        </div>
        <button 
            type="button"
            class="outline"
            style="padding: 0.5rem 1rem; margin-bottom: 0;"
            hx-post="/api/v1/notes/{$note.id}/tags/add"
            hx-target="body"
            hx-include="closest .tag-autocomplete"
        >
            Додати
        </button>
    </div>
</div>
