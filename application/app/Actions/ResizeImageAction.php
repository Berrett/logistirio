<?php

declare(strict_types=1);

namespace App\Actions;

use Imagick;

class ResizeImageAction
{
    /**
     * Shrink the image at the given absolute path in place so its width is at most $maxWidth,
     * keeping the aspect ratio. Narrower images are only re-oriented if their EXIF data says so.
     */
    public static function execute(string $absolutePath, int $maxWidth): void
    {
        $image = new Imagick($absolutePath);

        try {
            // Phone photos are often stored sideways with an EXIF rotation flag; bake it in.
            $wasRotated = $image->getImageOrientation() > Imagick::ORIENTATION_TOPLEFT;
            $image->autoOrient();

            if ($image->getImageWidth() <= $maxWidth) {
                if ($wasRotated) {
                    $image->writeImage($absolutePath);
                }

                return;
            }

            $image->thumbnailImage($maxWidth, 0);
            $image->writeImage($absolutePath);
        } finally {
            $image->clear();
        }
    }
}
