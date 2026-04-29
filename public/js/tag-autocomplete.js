document.addEventListener('alpine:init', () => {
    Alpine.data('tagAutocomplete', (tagsData) => ({
        query: '',
        open: false,
        tags: tagsData,
        get filteredTags() {
            if (this.query === '') return [];
            return this.tags.filter(tag => tag.name.toLowerCase().includes(this.query.toLowerCase()));
        },
        selectTag(name) {
            this.query = name;
            this.open = false;
        }
    }));
});
