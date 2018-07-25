<?php namespace bff\external;

class Wysiwyg
{
    protected $type = 'FCK';
    protected $isSecond = false;

    function setType($sTypeWYSIWYG = 'FCK')
    {
        $this->type = mb_strtoupper($sTypeWYSIWYG);
    }

    function init($sFieldName, $sContent, $sWidth = '800px', $sHeight = '300px', $sToolbarMode = 'full', $sTheme = 'sd')
    {
        switch ($this->type) {
            case 'FCK':
            {
                if (!$this->isSecond) {
                    require_once modification(PATH_PUBLIC . 'js/bff/admin/fcke2050/fckeditor.php');
                    $this->isSecond = true;
                }

                $oFCKeditor = new \FCKeditor($sFieldName);
                $oFCKeditor->BasePath = '../js/bff/admin/fcke2050/';

                if ($sWidth) {
                    $oFCKeditor->Width = $sWidth;
                }
                if ($sHeight) {
                    $oFCKeditor->Height = $sHeight;
                }

                $sToolbarMode = strtolower($sToolbarMode);
                switch ($sToolbarMode) {
                    case 'basic':
                        $oFCKeditor->ToolbarSet = 'Basic';
                        break;
                    case 'mini':
                        $oFCKeditor->ToolbarSet = 'Mini';
                        break;
                    case 'medium':
                        $oFCKeditor->ToolbarSet = 'Medium';
                        break;
                    case 'average':
                        $oFCKeditor->ToolbarSet = 'Average';
                        break;
                    default:
                        $oFCKeditor->ToolbarSet = 'Default';
                        break;

                }

                $oFCKeditor->Config['SkinPath'] = "/js/bff/admin/fcke2050/editor/skins/$sTheme/";
                $oFCKeditor->Value = $sContent;

                return $oFCKeditor->CreateHTML();
            }
            break;
            case 'TEXTAREA':
            {
                return '<textarea name="' . $sFieldName . '" style="width:' . $sWidth . ';height:' . $sHeight . '">' . $sContent . '</textarea>';
            }
            break;
        }
    }
}