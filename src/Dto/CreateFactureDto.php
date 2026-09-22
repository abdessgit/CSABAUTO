<?php
namespace App\Dto;
use Symfony\Component\Validator\Constraints as Assert;
class CreateFactureDto { #[Assert\NotNull] #[Assert\Positive] public ?int $interventionId=null; public static function fromRequest(array $data): self { $d=new self(); $d->interventionId=$data['intervention_id'] ?? $data['interventionId'] ?? null; return $d; } }