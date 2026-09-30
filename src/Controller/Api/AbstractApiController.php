<?php
namespace App\Controller\Api;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
abstract class AbstractApiController
{
    protected function data(Request $request): array { return json_decode($request->getContent() ?: '{}', true) ?: []; }
    protected function jsonRead(mixed $data, string $group, SerializerInterface $serializer, int $status = 200): JsonResponse { return JsonResponse::fromJsonString($serializer->serialize($data, 'json', ['groups'=>[$group,'utilisateur:summary','vehicule:summary','annonce:summary','service:summary','rendezvous:summary']]), $status); }
    protected function validateDto(object $dto, ValidatorInterface $validator): ?JsonResponse
    {
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $firstMessage = $errors[0]->getMessage();
            $messages = [];
            foreach ($errors as $e) {
                $messages[$e->getPropertyPath() ?: 'global'] = $e->getMessage();
            }
            return new JsonResponse([
                'error' => $firstMessage,
                'errors' => $messages,
            ], 422);
        }
        return null;
    }
}
