<?php

declare(strict_types=1);

namespace App\StoreDesigner\Parser;

final class FixtureParser
{
    public function parse(array $storeDefinition): array
    {
        return $this->buildSyliusFixtures($storeDefinition);
    }

    private function buildSyliusFixtures(array $inputData): array
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
            $fixtures += $this->buildProductFixtures($inputData['products'], 'tshirt');
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
                            'code' => 'MENU_CATEGORY',
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
                            'menu_taxon' => 'MENU_CATEGORY',
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
            $rate['zone'] = 'WORLD';
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
            $result[$categoryName]['options']['custom']['slug']['translations'] = $this->mapTranslations($category['translations'] ?? [], $locales);
        }

        return $result;
    }

    private function buildProductFixtures(array $inputData, string $prefix = 'product'): array
    {
        if ($inputData === []) {
            return [];
        }

        $formattedProducts = [];

        foreach ($inputData as $productEntry) {
            unset($productEntry['price']);
            unset($productEntry['translations']);
            unset($productEntry['imgPrompt']);
            $imageNames = $productEntry['images'] ?? [];
            unset($productEntry['images']);
            foreach ($imageNames as $name) {
                $productEntry['images'][] = [
                    'path' => '%kernel.project_dir%/store-preset/fixtures/' . $name . '.png',
                    'type' => 'main',
                ];
            }

            $formattedProducts[] = $productEntry;
        }

        $attributes = [];

        foreach ($formattedProducts as $productWithAttributes) {
            if (!isset($productWithAttributes['productAttributes']) || !is_array($productWithAttributes['productAttributes'])) {
                continue;
            }
            foreach ($productWithAttributes['productAttributes'] as $attributeKey => $attributeValue) {
                $attributes[$attributeKey] = [
                    'code' => $attributeKey,
                    'name' => ucfirst($attributeKey),
                    'type' => 'text'
                ];
            }
        }

        $attributes = array_values($attributes);

        $result = [];
        if (!empty($attributes)) {
            $result['product_attributes'] = [
                'name' => 'product_attribute',
                'options' => ['custom' => $attributes],
            ];
        }

        $formattedProducts = $this->keysToSnake($formattedProducts);

        $result["{$prefix}_product"] = [
                'name' => 'product',
                'options' => ['custom' => $formattedProducts],
        ];

        return $result;
    }

    private function mapTranslations(mixed $translations, array $locales): array
    {
        $mappedTranslations = [];
        foreach ($translations as $key => $value) {
            $mappedTranslations[$locales[$key]] = ['name' => $value];
        }

        return $mappedTranslations;
    }

    function keysToSnake(array $arr): array
    {
        $out = [];
        foreach ($arr as $key => $value) {
            // jeśli klucz jest stringiem, konwertujemy go do snake_case
            if (is_string($key)) {
                // np. "shortDescription" -> "short_description"
                $newKey = strtolower(
                    preg_replace('/(?<!^)[A-Z]/', '_$0', $key)
                );
            } else {
                // indeksy numeryczne czy inne pozostają bez zmian
                $newKey = $key;
            }

            // rekurencyjnie przerabiamy też wartości‑tablice
            if (is_array($value)) {
                $value = $this->keysToSnake($value);
            }

            $out[$newKey] = $value;
        }
        return $out;
    }
}
