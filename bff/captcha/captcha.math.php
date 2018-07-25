<?php

/**
 * Отрисовка математической капчи
 * @version  0.6
 * @modified 18.aug.2017
 */

# Устанавливаем необходимый часовой пояс
if (class_exists('config')) {
    $config = config::sys([]);
} else {
    $config = require(PATH_BASE . 'config/sys.php');
    if (!empty($config['date.timezone'])) {
        date_default_timezone_set($config['date.timezone']);
    }
}

# Настройки:
$width = 115; # Ширина
$height = 30; # Высота
$position_x = 13; # Начальная координата X
$position_y = 22; # Начальная координата Y
$font = PATH_CORE . 'fonts/duality.ttf'; # Шрифт
$font_size = rand(17, 21); # Размер шрифта
$font_size_spaces = 2; # Размер шрифта для пробелов
$angle_max = rand(23, 33); # Максимальный угол отклонения от горизонтали по часовой стрелке и против
# Цвет:
$color_digits_rand = array(0x317FC4, 0x2E9CFE, 0x5DA1DD, 0x3190E2, 0x006699, 0x0184BA, 0x037DD3);
$color_digits = $color_digits_rand[array_rand($color_digits_rand)]; # цифры (синий)
$color_question = 0xF9051E; # знак вопроса (красный)
$color_background = 0xFFFFFF; # фон (белый)

if (isset($_GET['bg']) && strlen($_GET['bg']) == 6) {
    $color_background_custom = hexdec($_GET['bg']);
    if (!empty($color_background_custom)) {
        $color_background = $color_background_custom;
    }
}

# Генерируем
if (!class_exists('CCaptchaProtection')) {
    require PATH_CORE . 'captcha' . DIRECTORY_SEPARATOR . 'captcha.protection.php';
}
$oProtection = new CCaptchaProtection();
if (isset($_GET['key'])) { $sKey = trim(preg_replace('/[^a-zA-Z0-9_\-]/', '', strval($_GET['key']))); }
if (empty($sKey)) $sKey = (!empty($config['cookie.captcha.math']) ? $config['cookie.captcha.math'] : 'c2');
# Сохраняем результат в куки: срок 20 мин
setcookie($sKey, $oProtection->generateMath(false), time() + (60 * 20), '/'.(!empty($config['site.host.subdir']) ? trim($config['site.host.subdir'], ' /').'/' : ''), (!empty($_SERVER['HTTP_HOST']) ? '.' . str_replace(array(
                'http://', 'https://', 'www.'
            ), '', $_SERVER['HTTP_HOST']
        ) : null), false, true /* http-only */
);
$text = $oProtection->gendata['text'];

/* для проверки:
    CCaptchaProtection::isCorrect($value, 'math');
*/

# Инициализируем контекст для рисования
$im = @ImageCreateTrueColor($width, $height);
$im_clr_background = @ImageColorAllocate($im, (integer)($color_background % 0x1000000 / 0x10000), (integer)($color_background % 0x10000 / 0x100), $color_background % 0x100);
$im_clr_digits = @ImageColorAllocate($im, (integer)($color_digits % 0x1000000 / 0x10000), (integer)($color_digits % 0x10000 / 0x100), $color_digits % 0x100);
$im_clr_question = @ImageColorAllocate($im, (integer)($color_question % 0x1000000 / 0x10000), (integer)($color_question % 0x10000 / 0x100), $color_question % 0x100);
ImageFill($im, 0, 0, $im_clr_background);

# Рисуем цифры [5 + 3 =]
for ($i = 0, $count = mb_strlen($text); $i < $count; $i++) {
    $isSpace = ($text{$i} == ' ');
    $isDigit = (((int)$text{$i}) > 0);
    $isSign = (!$isSpace && !$isDigit);

    # поворачиваем только цифры
    $angle = ($angle_max && $isDigit ? rand(360 - $angle_max, 360 + $angle_max) : 0);

    if (!$isSpace) {
        # знак "+" рисуем по-больше, поскольку в шрифте duality.ttf он мелкий
        imagettftext($im, ($isSign && $text{$i} == '+' ? 24 : $font_size), $angle, $position_x, $position_y, $im_clr_digits, $font, $text{$i});
    }
    $position_x += ($isSpace ? $font_size_spaces : $font_size);
    if ($i == 6) {
        $position_x -= 7;
    } # двигаем знак "=" влево
}

# Зашумляем изображение пикселами фонового цвета
$nois_color = $color_background;
$nois_percent = 7;
$nois_n_pix = round((($width - 45) * $height * $nois_percent) / 100);
for ($n = 0; $n < $nois_n_pix; $n++) {
    $x = rand(0, $width - 45);
    $y = rand(0, $height);
    ImageSetPixel($im, $x, $y, $nois_color);
}

# Рисуем знак вопроса [?]
imagettftext($im, $font_size, 0, $position_x, $position_y, $color_question, $font, '?');

# Открепляем ресурсы для рисования
ImageColorDeallocate($im, $im_clr_background);
ImageColorDeallocate($im, $im_clr_digits);
ImageColorDeallocate($im, $im_clr_question);

# Отображаем в браузере
header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
header("Expires: " . date("r"));
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Pragma: no-cache");
header('Content-Transfer-Encoding: binary');
header('Content-type: ' . image_type_to_mime_type(IMAGETYPE_PNG));

ImagePNG($im);
ImageDestroy($im);
exit(0);