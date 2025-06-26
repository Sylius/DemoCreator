<?php

declare(strict_types=1);

namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Dto\ChatConversationDto;
use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Resolver\ChatConversationDtoResolver;
use App\StoreDesigner\Resolver\StoreDetailsDtoResolver;
use App\StoreDesigner\Service\ChatConversationService;
use App\StoreDesigner\Service\FixtureCreator;
use App\StoreDesigner\Service\FixtureParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

final class CreateFixturesController extends AbstractController
{
    public function __construct(private readonly FixtureCreator $fixtureCreator, private readonly FixtureParser $fixtureParser)
    {
    }

    #[Route('/api/create-fixtures', name: 'api_create_fixtures', methods: ['POST'])]
    public function createFixtures(
        #[ValueResolver(StoreDetailsDtoResolver::class)]
        StoreDetailsDto $storeDetailsDto,
    ): JsonResponse {
        $this->fixtureCreator->create($storeDetailsDto);

        return $this->json(
            data: ['message' => 'Fixtures created successfully'],
            status: Response::HTTP_CREATED
        );
    }

    #[Route('/api/parse-fixtures', name: 'api_parse_fixtures', methods: ['POST'])]
    public function parseFixtures(Request $request): JsonResponse
    {
        $fixtures = $request->getContent();
        if (empty($fixtures)) {
            return $this->json(
                data: ['error' => 'No fixtures provided'],
                status: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $parsedFixtures = $this->fixtureParser->parse(json_decode($fixtures, true));
            return $this->json(
                data: ['parsed_fixtures' => $parsedFixtures],
                status: Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->json(
                data: ['error' => 'Failed to parse fixtures: ' . $e->getMessage()],
                status: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
