{foreach $cmsBlocks as $cmsBlock}
  <h3>{$cmsBlock.title}</h3>
  <ul>
    {foreach $cmsBlock.links as $link}
      <li>
        <a  id="{$link.id}"
            class="{$link.class}"
            href="{$link.url}"
            title="{$link.description}">
          {$link.title}
        </a>
      </li>
    {/foreach}
  </ul>
{/foreach}
