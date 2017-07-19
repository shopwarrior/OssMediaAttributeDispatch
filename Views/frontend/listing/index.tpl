{extends file="parent:frontend/listing/index.tpl"}

{* Content top container *}
{block name="frontend_index_content_top" prepend}
<div class="attributes-wrapper">
    {if !empty($ossCategoryBanners)}

        {foreach $ossCategoryBanners as $banner}
            {if $banner.thumbnails}
                {$baseSource = $banner.thumbnails[0].source}
                {$srcSet = ''}
                {$itemSize = ''}

                {foreach $banner.thumbnails as $image}
                    {$srcSet = "{if $srcSet}{$srcSet}, {/if}{$image.source} {$image.maxWidth}w"}

                    {if $image.retinaSource}
                        {$srcSet = "{if $srcSet}{$srcSet}, {/if}{$image.retinaSource} {$image.maxWidth * 2}w"}
                    {/if}
                {/foreach}
            {else}
                {$baseSource = $banner.source}
            {/if}
            <div class="image-wrapper">
                <img src="{$baseSource}"/>
            </div>
        {/foreach}
    {/if}
</div>
{/block}
