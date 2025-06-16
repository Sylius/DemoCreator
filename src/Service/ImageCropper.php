<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;

final class ImageCropper
{
    private const SHOP_LOGO_HEIGHT_FACTOR = 70;

    public function crop()
    {
        $this->io->info('Require intervention/image for image processing');
        $this->runCommand(['composer', 'require', 'intervention/image:^3.1', '--no-interaction']);

        $destLogoInAssets = __DIR__ . '/../../assets/images/logo.png';

        $manager = new ImageManager(new Driver());
        $image = $manager->read($destLogoInAssets);
        $size = $image->size();
        $aspectRatio = $size->width() / $size->height();
        $image->resize(
            width: (int)round(self::SHOP_LOGO_HEIGHT_FACTOR * $aspectRatio),
            height: self::SHOP_LOGO_HEIGHT_FACTOR
        );
        $image->save();
    }
}
