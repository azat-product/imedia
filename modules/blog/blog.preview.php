<?php

class BlogPostPreview_ extends CImageUploader
{
    const szList = 'l';  # list - список, форма
    const szIndex = 'i'; # index - список, форма

    function initSettings()
    {
        $this->path = bff::path('blog', 'images');
        $this->pathTmp = bff::path('tmp', 'images');

        $this->url = bff::url('blog', 'images');
        $this->urlTmp = bff::url('tmp', 'images');

        $this->table = TABLE_BLOG_POSTS;
        $this->fieldID = 'id';
        $this->fieldImage = 'preview';
        $this->filenameLetters = 6;
        $this->folderByID = false;
        $this->maxSize   = config::sysAdmin('blog.post.preview.maxsize', 3145728, TYPE_UINT); # 3мб
        $this->minWidth  = config::sysAdmin('blog.post.preview.width.min', 100, TYPE_UINT);
        $this->minHeight = config::sysAdmin('blog.post.preview.height.min', 100, TYPE_UINT);
        $this->maxWidth  = config::sysAdmin('blog.post.preview.width.max', 1500, TYPE_UINT);
        $this->maxHeight = config::sysAdmin('blog.post.preview.height.max', 1500, TYPE_UINT);
        $this->sizes = bff::filter('blog.post.preview.sizes', array(
            self::szList   => array('width' => 100, 'height' => 100),
            self::szIndex  => array('width' => 300, 'height' => 150),
        ));
    }

    public static function url($nShopID, $sFilename, $sSizePrefix, $bTmp = false, $bDefault = false)
    {
        static $i;
        if (!isset($i)) {
            $i = new static();
        }
        $i->setRecordID($nShopID);

        if (empty($sFilename)) {
            return ($bDefault ? $i->urlDefault($sSizePrefix) : false);
        }

        return $i->getURL($sFilename, $sSizePrefix, $bTmp);
    }

    /**
     * Обработка формы загрузки картинки
     * @param bool $bOnCreate загрузка картинки при создании поста
     * @param string $sInputFile имя поля загрузки картинки (input="file")
     * @param string $sInputDeleteCheckbox имя поля удаления картинки (input="checkbox")
     * @return mixed
     */
    public function onSubmit($bOnCreate, $sInputFile = 'preview', $sInputDeleteCheckbox = 'preview_del')
    {
        if ($bOnCreate) {
            $this->setAssignErrors(false);
            $aUpload = $this->uploadFILES($sInputFile, false, false);
            if (!empty($aUpload['filename'])) {
                return $aUpload['filename'];
            }
        } else {
            $aUpload = $this->uploadFILES($sInputFile, true, false);
            if (!empty($aUpload['filename'])) {
                return $aUpload['filename'];
            } else {
                if ($this->input->postget($sInputDeleteCheckbox, TYPE_BOOL)) {
                    if ($this->delete(false)) {
                        return '';
                    }
                }
            }

            return false;
        }
    }
}