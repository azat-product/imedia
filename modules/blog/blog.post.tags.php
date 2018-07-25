<?php

class BlogPostTags_ extends bff\db\Tags
{
    protected function initSettings()
    {
        $this->tblTags = TABLE_BLOG_TAGS;
        $this->tblTagsIn = TABLE_BLOG_POSTS_TAGS;
        $this->tblTagsIn_ItemID = 'post_id';
        $this->urlItemsListing = $this->adminLink('posts&tag=', 'blog');
        $this->lang = array(
            'list'         => _t('blog','Список тегов'),
            'add_title'    => _t('blog','Добавление тега'),
            'add_text'     => _t('blog','Введите теги, каждый с новой строки'),
            'edit'         => _t('blog','Редактирование тега'),
            'replace_text' => _t('blog','Введите название тега для замены'),
        );
    }
}