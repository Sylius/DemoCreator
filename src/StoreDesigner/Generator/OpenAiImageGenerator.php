<?php

declare(strict_types=1);

namespace App\StoreDesigner\Generator;

use App\StoreDesigner\Dto\AssetImageRequestDto;
use App\StoreDesigner\Dto\ImageResponseDto;
use App\StoreDesigner\Dto\ProductImageRequestDto;
use App\StoreDesigner\Exception\ImageGenerationException;
use App\StoreDesigner\Exception\OpenAiApiException;
use App\StoreDesigner\Util\ImageType;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;

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

        // Smart batching: limit total images per batch to 5 (OpenAI rate limit)
        $batches = [];
        $currentBatch = [];
        $currentBatchImageCount = 0;
        $maxImagesPerBatch = 5;

        foreach ($imageRequests as $request) {
            $requestImageCount = $request->n;

            // If adding this request would exceed the limit, start a new batch
            if ($currentBatchImageCount + $requestImageCount > $maxImagesPerBatch && !empty($currentBatch)) {
                $batches[] = $currentBatch;
                $currentBatch = [];
                $currentBatchImageCount = 0;
            }

            $currentBatch[] = $request;
            $currentBatchImageCount += $requestImageCount;
        }

        // Add the last batch if not empty
        if (!empty($currentBatch)) {
            $batches[] = $currentBatch;
        }

        $totalBatches = count($batches);

        foreach ($batches as $batchIndex => $batch) {
            $promises = [];
            foreach ($batch as $image) {
                $type = match (true) {
                    $image instanceof ProductImageRequestDto => ImageType::Product,
                    $image instanceof AssetImageRequestDto => ImageType::Asset,
                    default => ImageType::Custom,
                };

                $promises[] = [
                    'filename' => $image->filename,
                    'type' => $type,
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
                try {
                    $response = $item['promise'];
                    $status = $response->getStatusCode();
                    $body = $response->getContent(false);

                    if ($status !== 200) {
                        throw new OpenAiApiException(message: $body, httpStatus: $status);
                    }

                    $data = $response->toArray(false);

                    if (empty($data['data'])) {
                        throw new ImageGenerationException('No image data', previous: null);
                    }

                    foreach ($data['data'] as $index => $imageData) {
                        $b64 = $imageData['b64_json'] ?? throw new ImageGenerationException('No image data at index ' . $index, previous: null);
                        $binary = base64_decode($b64, true) ?? throw new ImageGenerationException('Invalid base64', previous: null);

                        $filename = count($data['data']) > 1
                            ? $item['filename'] . '_' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)
                            : $item['filename'];

                        $results[] = new ImageResponseDto(
                            filename: $filename,
                            imageType: $item['type'],
                            binary: $binary,
                        );
                    }
                } catch (TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface | RedirectionExceptionInterface | DecodingExceptionInterface $e) {
                    throw new OpenAiApiException('Failed to call OpenAI API: ' . $e->getMessage(), previous: $e);
                }
            }

            if ($batchIndex < $totalBatches - 1) {
                sleep(60);
            }
        }

        return $results;
    }
}
