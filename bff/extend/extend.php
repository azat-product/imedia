<?php
/**
 * Классы с поддержкой наследования
 * Вспомогательная инициализация классов для IDE
 */

# /app
namespace {
    class Module extends Module_ {}
    class Security extends Security_ {}
    class tplAdmin extends tplAdmin_ {}
    class tpl extends tpl_ {}
    class User extends User_ {}
}

# /base
namespace {
    class HTML extends bff\base\HTML {}
    class js extends bff\base\js {}
    class Request extends bff\base\Request {}
    class Response extends bff\base\Response {}
    class View extends bff\base\View {}
    class Logger extends bff\logs\Logger {}
}
namespace bff\base {
    class Input extends Input_ {}
    class Locale extends Locale_ {}
}

# /cache
namespace {
    class Cache extends bff\cache\Cache {}
}

# /db
namespace bff\db {
    class Categories extends Categories_ {}
    class Comments extends Comments_ {}
    class Dynprops extends Dynprops_ {}
    class NestedSetsTree extends NestedSetsTree_ {}
    class Publicator extends Publicator_ {}
    class Sphinx extends Sphinx_ {}
    class Tags extends Tags_ {}
    class Database extends Database_ {}
    class Table extends Table_ {}
    class ImagesField extends ImagesField_ {}
}

# /external
namespace {
    class CMail extends bff\external\Mail {}
    class CSmarty extends bff\external\Smarty {}
    class CWysiwyg extends bff\external\Wysiwyg {}
    class Minifier extends Minifier_ {}
}

# /files
namespace {
    class CUploader extends bff\files\Uploader {}
}
namespace bff\files {
    class Attachment extends Attachment_ {}
    class AttachmentsTable extends AttachmentsTable_ {}
}

# /img
namespace {
    class CImageUploader extends CImageUploader_ {}
    class CImagesUploader extends CImagesUploader_ {}
    class CImagesUploaderField extends CImagesUploaderField_ {}
    class CImagesUploaderTable extends CImagesUploaderTable_ {}
}
namespace bff\img {
    class Thumbnail extends Thumbnail_ {}
}

# logs
namespace bff\logs {
    class File extends File_ {}
}

# /utils
namespace {
    class CSitemapXML extends bff\utils\Sitemap {}
    class func extends bff\utils\func {}
    class Pagination extends bff\utils\Pagination {}
}
namespace bff\utils {
    class Files extends Files_ {}
    class LinksParser extends LinksParser_ {}
    class TextParser extends TextParser_ {}
    class VideoParser extends VideoParser_ {}
}

# /
namespace {
    class Component extends bff\Component {}
    class CronManager extends bff\CronManager {}
    class Errors extends bff\Errors {}
    class CMenu extends bff\Menu {}
    class Model extends bff\db\Model {}
    class Hook extends bff\extend\Hook {}
    class Hooks extends bff\extend\Hooks {}
    class Plugin extends bff\extend\Plugin {}
    class Theme extends bff\extend\theme\Base {}
    class ThemeAddon extends bff\extend\theme\Addon {}
}