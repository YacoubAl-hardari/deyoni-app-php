<?php

declare(strict_types=1);

function brandImage(int $size, bool $splash, bool $dark = false): GdImage
{
    $image = imagecreatetruecolor($size, $size);
    $blue = imagecolorallocate($image, 37, 99, 235);
    $white = imagecolorallocate($image, 255, 255, 255);
    $background = imagecolorallocate($image, $dark ? 11 : 248, $dark ? 18 : 250, $dark ? 32 : 252);
    imagefill($image, 0, 0, $splash ? $background : $blue);
    $u = $size / 1024;
    if ($splash) {
        imagefilledrectangle($image, (int) (288 * $u), (int) (288 * $u), (int) (736 * $u), (int) (736 * $u), $blue);
    }
    $scale = $splash ? 0.52 : 1;
    $point = fn (int $v): int => (int) (($v - 512) * $scale * $u + 512 * $u);
    imagefilledrectangle($image, $point(240), $point(290), $point(784), $point(734), $white);
    imagefilledrectangle($image, $point(302), $point(358), $point(724), $point(666), $blue);
    imagefilledrectangle($image, $point(545), $point(450), $point(798), $point(574), $white);
    imagefilledellipse($image, $point(618), $point(512), (int) (36 * $scale * $u), (int) (36 * $scale * $u), $blue);
    foreach ([420, 505, 590] as $y) {
        imagefilledrectangle($image, $point(350), $point($y), $point(470), $point($y + 24), $white);
    }

    return $image;
}

$root = dirname(__DIR__);
foreach (['icon.png' => [1024, false, false], 'splash.png' => [1024, true, false], 'splash-dark.png' => [1024, true, true]] as $name => $options) {
    $image = brandImage(...$options);
    imagepng($image, $root.'/public/'.$name);
    imagedestroy($image);
}
