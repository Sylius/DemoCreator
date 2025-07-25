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

namespace App\StoreDesigner\Generator;

use App\StoreDesigner\Dto\ImageResponseDto;
use App\StoreDesigner\Dto\ProductImageRequestDto;
use App\StoreDesigner\Exception\ImageGenerationException;
use App\StoreDesigner\Util\ImageType;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OpenAiImageGenerator implements ImageGeneratorInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[SensitiveParameter] #[Autowire(env: 'OPENAI_API_KEY')] private string $openaiApiKey,
    ) {
    }

    public function generateAll(array $imageRequests): array
    {
        $results = [];
        $batches = array_chunk($imageRequests, 5);
        $totalBatches = count($batches);

        foreach ($batches as $batchIndex => $batch) {
            $promises = [];
            foreach ($batch as $image) {
                $promises[] = [
                    'filename' => $image->filename,
                    'type' => $image instanceof ProductImageRequestDto ? ImageType::Product : ImageType::Asset,
                    'promise' => $this->httpClient->request('POST', 'https://api.openai.com/v1/images/generations', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->openaiApiKey,
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'model' => $image->model,
                            'prompt' => $image->prompt,
                            'n' => $image->n,
                            'size' => $image->imageResolution->value,
                            'quality' => $image->imageQuality->value,
                        ],
                    ]),
                ];
            }

            foreach ($promises as $item) {
                $data = $item['promise']->toArray(false);
                $b64 = $data['data'][0]['b64_json'] ?? throw new ImageGenerationException('No image data');
                $binary = base64_decode($b64, true) ?? throw new ImageGenerationException('Invalid base64');

                $results[] = new ImageResponseDto(
                    filename: $item['filename'],
                    imageType: $item['type'],
                    binary: $binary,
                );
            }

            if ($batchIndex < $totalBatches - 1) {
                sleep(60);
            }
        }

        return $results;
    }
}
