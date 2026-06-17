<?php

$svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" role="img" aria-label="Cardioprenatal">
  <rect width="32" height="32" rx="7" fill="#7f0c1a"/>
  <path fill="#fff" d="M7.5 10.2c0-1.55 1.25-2.8 2.8-2.8.85 0 1.65.38 2.1.98.45-.6 1.25-.98 2.1-.98 1.55 0 2.8 1.25 2.8 2.8 0 2.05-2.35 3.82-4.9 6.05L10.3 17.8 7.5 15.5C5.15 13.05 7.5 11.35 7.5 10.2z"/>
  <circle cx="22.5" cy="11" r="3.2" fill="#fff"/>
  <path fill="#fff" d="M17.2 24.5c0-3.1 2.4-5.6 5.3-5.6s5.3 2.5 5.3 5.6v1.5H17.2v-1.5z"/>
</svg>
SVG;

$public = __DIR__.'/patches/public';
if (! is_dir($public) && ! mkdir($public, 0755, true) && ! is_dir($public)) {
    fwrite(STDERR, "Não foi possível criar {$public}\n");
    exit(1);
}
file_put_contents($public.'/favicon.svg', $svg);

function drawIcon(int $size)
{
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    $red = imagecolorallocate($img, 127, 12, 26);
    $white = imagecolorallocate($img, 255, 255, 255);

    $radius = (int) round($size * 0.22);
    imagefilledrectangle($img, 0, $radius, $size - 1, $size - $radius - 1, $red);
    imagefilledellipse($img, $radius, $radius, $radius * 2, $radius * 2, $red);
    imagefilledellipse($img, $size - $radius - 1, $radius, $radius * 2, $radius * 2, $red);
    imagefilledellipse($img, $radius, $size - $radius - 1, $radius * 2, $radius * 2, $red);
    imagefilledellipse($img, $size - $radius - 1, $size - $radius - 1, $radius * 2, $radius * 2, $red);
    imagefilledrectangle($img, $radius, 0, $size - $radius - 1, $size - 1, $red);

    $s = $size / 32.0;

    imagefilledellipse($img, (int) (22.5 * $s), (int) (11 * $s), (int) (6.4 * $s), (int) (6.4 * $s), $white);
    imagefilledrectangle($img, (int) (17.2 * $s), (int) (18.9 * $s), (int) (27.8 * $s), (int) (26 * $s), $white);
    imagefilledellipse($img, (int) (10.3 * $s), (int) (13.5 * $s), (int) (8 * $s), (int) (7 * $s), $white);
    imagefilledellipse($img, (int) (8.5 * $s), (int) (12 * $s), (int) (5 * $s), (int) (4.5 * $s), $red);
    imagefilledellipse($img, (int) (12.1 * $s), (int) (12 * $s), (int) (5 * $s), (int) (4.5 * $s), $red);

    return $img;
}

$img32 = drawIcon(32);
imagepng($img32, $public.'/favicon-32.png');

$img180 = drawIcon(180);
imagepng($img180, $public.'/apple-touch-icon.png');

copy($public.'/favicon-32.png', $public.'/favicon.ico');

imagedestroy($img32);
imagedestroy($img180);

echo "Favicons generated in {$public}\n";
