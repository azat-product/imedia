<?php

/**
 * Отрисовка цифровой капчи
 * @version  0.6
 * @modified 18.aug.2017
 */

# Устанавливаем необходимый часовой пояс
if (!class_exists('config')) {
    $config = require(PATH_BASE . 'config/sys.php');
    if (!empty($config['date.timezone'])) {
        date_default_timezone_set($config['date.timezone']);
    }
}

# Настройки:
$width = 104; # Ширина изображения, по умолчанию: 100
$height = 28; # Высота изображения, по умолчанию: 28
$num_n = mt_rand(4, 5); # Кол-во цифр, 4 или 5
$font = PATH_CORE . 'fonts/duality.ttf'; # Шрифт
$font_min_size = 12; # Минимальный размер шрифта, по умолчанию: 12
$lines_n_max = mt_rand(3, 5); # Максимальное число шумовых линий, по умолчанию: 3
$noise_percent = 2; # Зашумленность цветами фона и текста, в процентах, по умолчанию: 2
$angle_max = 27; # Максимальный угол отклонения от горизонтали по часовой стрелке и против, по умолчанию: 20
# Цвет:
$backColor = 0xFFFFFF; # фон. по умолчанию - 0xFFFFFF(белый)
$foreColor1 = 0x2040A0; # текст 1. по умолчанию - 0x2040A0(синий)
$foreColor2 = 0x5E5E5E; # текст 2. по умолчанию - 0x2040A0(синий)
$noisColor = 0x2040A0; # зашумляющие точки и линии. по умолчанию - 0x2040A0(синий)

if (isset($_GET['bg']) && strlen($_GET['bg']) == 6) {
    $color_background_custom = hexdec($_GET['bg']);
    if (!empty($color_background_custom)) {
        $backColor = $color_background_custom;
    }
}

$im = @ImageCreateTrueColor($width, $height);
if ($im === false) {
    die("Cannot Initialize new GD image stream");
}

# Создаем необходимые цвета
$text_color1 = ImageColorAllocate($im, (int)($foreColor1 % 0x1000000 / 0x10000), (int)($foreColor1 % 0x10000 / 0x100), $foreColor1 % 0x100);
$text_color2 = ImageColorAllocate($im, (int)($foreColor2 % 0x1000000 / 0x10000), (int)($foreColor2 % 0x10000 / 0x100), $foreColor2 % 0x100);
$noise_color = ImageColorAllocate($im, (int)($noisColor % 0x1000000 / 0x10000), (int)($noisColor % 0x10000 / 0x100), $noisColor % 0x100);
$img_color = ImageColorAllocate($im, (int)($backColor % 0x1000000 / 0x10000), (int)($backColor % 0x10000 / 0x100), $backColor % 0x100);

# Заливаем изображение фоновым цветом
ImageFill($im, 0, 0, $img_color);
# В переменной $number будет храниться число, показанное на изображении
$number = '';

for ($n = 0; $n < $num_n; $n++) {
    $num = rand(0, 9);
    $number .= $num;
    $font_size = rand($font_min_size, $height / 2);
    $angle = rand(360 - $angle_max, 360 + $angle_max);

    # вычисление координат для каждой цифры, формулы обеспечивают нормальное расположние
    # при любых значениях размеров цифры и изображения
    $y = rand(($height - $font_size) / 4 + $font_size, ($height - $font_size) / 2 + $font_size);
    $x = rand(($width / $num_n - $font_size) / 2, $width / $num_n - $font_size) + $n * $width / $num_n;

    imagettftext($im, $font_size, $angle, $x, $y, (rand(0, 1) ? $text_color1 : $text_color2), $font, $num);
}
# Вычисляем число "зашумленных" пикселов
$noise_n_pix = round($width * $height * $noise_percent / 100);
# Зашумляем изображение пикселами цвета текста
for ($n = 0; $n < $noise_n_pix; $n++) {
    $x = rand(0, $width);
    $y = rand(0, $height);
    ImageSetPixel($im, $x, $y, $noise_color);
}
# Зашумляем изображение пикселами фонового цвета
for ($n = 0; $n < $noise_n_pix; $n++) {
    $x = rand(0, $width);
    $y = rand(0, $height);
    ImageSetPixel($im, $x, $y, $img_color);
}

# Проводим "зашумляющие" линии цвета текста
$lines_n = rand(0, $lines_n_max);
for ($n = 0; $n < $lines_n; $n++) {
    $x1 = mt_rand(0, $width);
    $y1 = mt_rand(0, $height);
    $x2 = mt_rand(0, $width);
    $y2 = mt_rand(0, $height);
    ImageLine($im, $x1, $y1, $x2, $y2, $noise_color);
}

# Сохраняем число в куки: ключ 'c1'
if (!class_exists('CCaptchaProtection')) {
    require PATH_CORE . 'captcha' . DIRECTORY_SEPARATOR . 'captcha.protection.php';
}
$oProtection = new CCaptchaProtection();
setcookie('c1', $oProtection->generate_hash($number, date('j')), time() + (1 * 60 * 10), '/'); # 10 минут

# Очищаем ресурсы для рисования
ImageColorDeallocate($im, $text_color1);
ImageColorDeallocate($im, $text_color2);
ImageColorDeallocate($im, $noise_color);
ImageColorDeallocate($im, $img_color);

# Отображаем в браузере
header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
header("Expires: " . date("r"));
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Pragma: no-cache");
header('Content-Transfer-Encoding: binary');
header('Content-type: ' . image_type_to_mime_type(IMAGETYPE_JPEG));

imagejpeg($im, NULL, 85);
imagedestroy($im);