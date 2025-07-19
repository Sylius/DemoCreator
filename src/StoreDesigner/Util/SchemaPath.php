<?php

declare(strict_types=1);

namespace App\StoreDesigner\Util;

enum SchemaPath: string
{
    case ManifestSchema = 'resources/schemas/store-preset-manifest.schema.json';
    case StoreDetails = 'resources/schemas/store-details.schema.json';
    case StoreDefinition = 'resources/schemas/store-definition.schema.json';
}
