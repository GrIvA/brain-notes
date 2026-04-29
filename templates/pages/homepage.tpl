{extends 'layouts/main.tpl'}

{block 'content'}
<section>
    <header>
        <h2><i class="fas fa-home"></i> Головна</h2>
    </header>
    
    <div id="note-list">
        {include 'components/note_list.tpl' notes=$notes}
    </div>

    {if $tags}
        {include 'components/tag_block.tpl' mode='filter' tags=$tags}
    {/if}
</section>
{/block}
