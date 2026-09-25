<?php

namespace App\Controller\Api;

use App\Repository\ServiceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/services')]
class ServiceController extends AbstractApiController
{
    #[Route('', methods: ['GET'])]
    public function list(ServiceRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        return $this->jsonRead($repo->findAll(), 'service:read', $serializer);
    }
}
