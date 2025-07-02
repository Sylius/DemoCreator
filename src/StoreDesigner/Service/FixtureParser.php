<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FixtureParser
{
    private const IMAGE_SAVE_DIR = 'fixtures/images';

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function parse(array $jsonData): array
    {
        return $this->buildSyliusFixtures($jsonData, false);
    }

    public function generateFixturesFromArray(array $jsonData, string $yamlPath)
    {
    }

    public function generateFixtures(string $input, string $output, bool $withImages): void
    {
        $inputData = $this->readJson($input);
        $fixturesArray = $this->buildSyliusFixtures($inputData, $withImages);
        $yaml = Yaml::dump($fixturesArray, 10, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_NULL_AS_TILDE);
        $configPath = __DIR__ . '/../../config/packages/' . $output;
        file_put_contents($configPath, $yaml);
    }

    private function readJson(string $file): array
    {
        if (!is_file($file)) {
            throw new \RuntimeException("File not found: {$file}");
        }
        return json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    }

    private function generateImageFromPrompt(string $prompt, string $slug): string
    {
        $apiKey = getenv('OPENAI_API_KEY');
        if (!$apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY is not set.');
        }

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/images/generations', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => getenv('OPENAI_IMAGE_MODEL') ?: 'dall-e-3',
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024',
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode === 401) {
            throw new \RuntimeException('Invalid API key.');
        } elseif ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException("API returned status {$statusCode}.");
        }

        $payload = $response->toArray(false);
        $url = $payload['data'][0]['url'] ?? throw new \RuntimeException('Missing image URL.');
        $imageData = $this->httpClient->request('GET', $url)->getContent();

        if (!is_dir(self::IMAGE_SAVE_DIR) && !mkdir(self::IMAGE_SAVE_DIR, 0775, true) && !is_dir(self::IMAGE_SAVE_DIR)) {
            throw new \RuntimeException('Cannot create image directory.');
        }

        $safeSlug = preg_replace('/[^A-Za-z0-9_\-]/', '_', $slug);
        $filePath = rtrim(self::IMAGE_SAVE_DIR, '/') . '/' . $safeSlug . '.png';
        file_put_contents($filePath, $imageData);

        return $filePath;
    }

    private function buildSyliusFixtures(array $inputData, bool $withImages): array
    {
        // domyślna nazwa suite
        $suite = $inputData['storePresetName'] ?? 'moj_sklep';

        // --- LISTENERY
        $listeners = $inputData['listeners'] ?? ['orm_purger', 'images_purger', 'logger'];

        // --- FIXTURES
        $fixtures = [];

        $fixtures['admin_user'] = [
            'name'    => 'admin_user',
            'options' => [
                'custom' => [
                    [
                        'email'       => 'sylius@example.com',
                        'username'    => 'sylius',
                        'password'    => 'sylius',
                        'enabled'     => true,
                        'locale_code' => '%locale%',
                        'first_name'  => 'John',
                        'last_name'   => 'Doe',
                        'avatar'      => '@SyliusCoreBundle/Resources/fixtures/adminAvatars/john.webp',
                    ],
                    [
                        'email'       => 'api@example.com',
                        'username'    => 'api',
                        'password'    => 'sylius-api',
                        'enabled'     => true,
                        'locale_code' => '%locale%',
                        'first_name'  => 'Luke',
                        'last_name'   => 'Brushwood',
                        'api'         => true,
                        'avatar'      => '@SyliusCoreBundle/Resources/fixtures/adminAvatars/luke.webp',
                    ],
                ],
            ],
        ];

        if (!empty($inputData['locales'])) {
            $fixtures += $this->buildLocaleFixture($inputData['locales']);
        }

        if (!empty($inputData['currencies'])) {
            $fixtures += $this->buildCurrencyFixture($inputData['currencies']);
        }

        if (!empty($inputData['countries'])) {
            $fixtures += $this->buildGeographicalFixture($inputData['countries']);
        }

        if (!empty($inputData['menuTaxon'])) {
            $fixtures += $this->buildMenuTaxonFixture($inputData['menuTaxon'], $inputData['locales']);
        }

        if (!empty($inputData['channel'])) {
            $fixtures += $this->buildChannelFixture($inputData['channel']);
        }

        if (!empty($inputData['paymentMethods'])) {
            $fixtures += $this->buildSimpleCollectionFixture('payment_method', 'custom', $inputData['paymentMethods']);
        }
        if (!empty($inputData['shippingMethods'])) {
            $fixtures += $this->buildSimpleCollectionFixture('shipping_method', 'custom', $inputData['shippingMethods']);
        }

        if (!empty($inputData['taxCategories'])) {
            $fixtures += $this->buildSimpleCollectionFixture('tax_category', 'custom', $inputData['taxCategories']);
        }

        if (!empty($inputData['taxRates'])) {
            $fixtures += $this->buildTaxRateFixture($inputData['taxRates']);
        }
        $fixtures += $this->buildTaxons($inputData['categories'], $inputData['locales']);

        if (!empty($inputData['products'])) {
            $fixtures += $this->buildProductFixtures($inputData['products'], 'tshirt', $withImages);
        }

        // --- GOTOWA KOŃCÓWKA
        return [
            'sylius_fixtures' => [
                'suites' => [
                    $suite => [
                        'listeners' => $this->mapNull($listeners),
                        'fixtures' => $fixtures,
                    ],
                ],
            ],
        ];
    }

    private function mapNull(array $items): array
    {
        return array_fill_keys($items, null);
    }

    private function buildLocaleFixture(array $locales): array
    {
        return [
            'locale' => [
                'options' => ['locales' => array_values($locales)],
            ],
        ];
    }

    private function buildCurrencyFixture(array $currencies): array
    {
        return [
            'currency' => [
                'options' => ['currencies' => array_values($currencies)],
            ],
        ];
    }

    private function buildGeographicalFixture(array $countryCodes): array
    {
        //countries ma miec  - PL, - DE a w $countryCodes jest       "code": "PL", "name": "Poland" trzeba przemapowac
        // na PL, DE, a nie PL, DE, PL, DE

        $zones = [
            'WORLD' => [
                'name' => 'World zone',
                'countries' => array_values($countryCodes),
            ],
        ];

        return [
            'geographical' => [
                'options' => [
                    'countries' => array_values($countryCodes),
                    'zones' => $zones,
                ],
            ],
        ];
    }

    private function buildMenuTaxonFixture(array $menuTaxon, array $locales): array
    {
        return [
            'menu_taxon' => [
                'name' => 'taxon',
                'options' => [
                    'custom' => [
                        'category' => [
                            'code' => $menuTaxon['code'],
                            'name' => $menuTaxon['name'],
                            'translations' => $this->mapTranslations($menuTaxon['translations'], $locales),
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildChannelFixture(array $channel): array
    {
        return [
            'channel' => [
                'options' => [
                    'custom' => [
                        $channel['code'] => [
                            'name' => $channel['name'],
                            'code' => $channel['code'],
                            'locales' => $channel['locales'],
                            'currencies' => $channel['currencies'],
                            'enabled' => $channel['enabled'] ?? true,
                            'hostname' => $channel['hostname'] ?? '%env(resolve:SYLIUS_FIXTURES_HOSTNAME)%',
                            'theme_name' => $channel['theme_name'] ?? '%env(resolve:SYLIUS_FIXTURES_THEME_NAME)%',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildSimpleCollectionFixture(string $fixtureKey, string $innerKey, array $entries): array
    {
        // Używane dla payment_method, shipping_method, customer_group, tax_category …
        $custom = [];
        foreach ($entries as $row) {
            $code = $row['code'];
            unset($row['code']);

            $custom[$code] = array_merge(['code' => $code], $row);
        }

        return [
            $fixtureKey => [
                'options' => [
                    'custom' => $custom,
                ],
            ],
        ];
    }

    private function buildTaxRateFixture(array $rates): array
    {
        $custom = [];
        foreach ($rates as $rate) {
            $code = $rate['code'];
            unset($rate['code']);
            $custom[$code] = $rate;
        }
        return [
            'tax_rate' => [
                'options' => ['custom' => $custom],
            ],
        ];
    }

    function buildTaxons(array $categories, array $locales): array
    {
        $result = [];

        foreach ($categories as $category) {
            $categoryName = 'taxon_' . $category['slug'];
            $result[$categoryName]['name'] = 'taxon';
            $result[$categoryName]['options']['custom']['slug'] = $category;
            $result[$categoryName]['options']['custom']['translations'] = $this->mapTranslations($category['translations'] ?? [], $locales);
        }

        return $result;
    }

    private function buildProductFixtures(array $inputData, string $prefix = 'product', bool $withImages = false): array
    {
        if ($inputData === []) {
            return [];
        }

        $formattedProducts = [];

        foreach ($inputData as $productEntry) {
            unset($productEntry['price']);
            unset($productEntry['translations']);
            unset($productEntry['img_prompt']);

            if ($withImages && !empty($productEntry['img_prompt'])) {
                $productEntry['images'][] = [
                    'path' => sprintf('fixtures/images/%s_main.png', preg_replace('/[^A-Za-z0-9_\-]/', '_', $productEntry['name'])),
                    'type' => 'main',
                ];
            }

            $formattedProducts[] = $productEntry;
        }

        $attributes = [];

        foreach ($formattedProducts as $productWithAttributes) {
            if (!isset($productWithAttributes['product_attributes']) || !is_array($productWithAttributes['product_attributes'])) {
                continue;
            }
            foreach ($productWithAttributes['product_attributes'] as $attributeKey => $attributeValue) {
                $attributes[$attributeKey] = [
                    'code' => $attributeKey,
                    'name' => ucfirst($attributeKey),
                    'type' => 'text'
                ];
            }
        }

        $attributes = array_values($attributes);

        $fixtures = [
            "{$prefix}_product" => [
                'name' => 'product',
                'options' => ['custom' => $formattedProducts],
            ],
        ];

        if (!empty($attributes)) {
            $fixtures['product_attribute'] = [
                'name' => 'product_attribute',
                'options' => ['custom' => $attributes],
            ];
        }

        return $fixtures;
    }

    private function mapTranslations(mixed $translations, array $locales): array
    {
        $mappedTranslations = [];
        foreach ($translations as $key => $value) {
            $mappedTranslations[$locales[$key]] = ['name' => $value];
        }

        return $mappedTranslations;
    }
}
