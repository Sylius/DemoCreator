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

namespace App\StoreDesigner\Dto;

use App\StoreDesigner\Exception\InvalidSchemaDataException;
use App\StoreDesigner\Schema\JsonSchema;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

class StoreDetailsDto implements \JsonSerializable
{
    public function __construct(
        public string $industry,
        public array $locales,
        public array $currencies,
        public array $countries,
        public array $categories,
        public int $productsPerCat,
        public string $descriptionStyle,
        public string $imageStyle,
        public ?array $themePreferences = null,
    ) {
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    public static function fromArray(array $data, JsonSchema $schema): self
    {
        $validator = new Validator();
        $result = $validator->validate(json_decode(json_encode($data)), $schema->toObject());

        if (!$result->isValid()) {
            $formatter = new ErrorFormatter();
            $errors = $formatter->format($result->error());
            throw new InvalidSchemaDataException('Invalid data', $errors);
        }

        return new self(
            $data['industry'],
            $data['locales'],
            $data['currencies'],
            $data['countries'],
            $data['categories'],
            $data['productsPerCat'],
            $data['descriptionStyle'],
            $data['imageStyle'],
            $data['themePreferences'],
        );
    }

    /** @throws \JsonException */
    public function toJson(): string
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'industry' => $this->industry,
            'locales' => $this->locales,
            'currencies' => $this->currencies,
            'countries' => $this->countries,
            'categories' => $this->categories,
            'productsPerCat' => $this->productsPerCat,
            'descriptionStyle' => $this->descriptionStyle,
            'imageStyle' => $this->imageStyle,
            'themePreferences' => $this->themePreferences,
        ];
    }
}
