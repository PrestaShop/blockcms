<?php

class CmsBlockPresenter
{
    private $link;
    private $language;

    public function __construct(Link $link, Language $language)
    {
        $this->link = $link;
        $this->language = $language;
    }

    public function present(CmsBlock $cmsBlock)
    {
        return [
            'id' => $cmsBlock->id,
            'title' => $cmsBlock->name[$this->language->id],
            'hook' => (new Hook($cmsBlock->id_hook))->name,
            'position' => $cmsBlock->position,
            'links' => $this->makeLinks($cmsBlock->content)
        ];
    }

    private function makeLinks($content)
    {
        return $this->makeCmsLinks($content['cms']);
    }

    private function makeCmsLinks($cmsIds)
    {
        $cmsLinks = [];
        foreach ($cmsIds as $cmsId) {
            $cms = new CMS($cmsId);
            $cmsLinks[] = [
                'id' => 'cms-page-'.$cms->id,
                'class' => 'cms-page-link',
                'title' => $cms->meta_title[$this->language->id],
                'description' => $cms->meta_description[$this->language->id],
                'url' => $this->link->getCMSLink($cms),
            ];
        }

        return $cmsLinks;
    }
}
