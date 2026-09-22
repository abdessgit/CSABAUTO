<?php
namespace App\Dto;
use Symfony\Component\Validator\Constraints as Assert;
class CreateInterventionDto { #[Assert\NotBlank] #[Assert\Date] public ?string $dateIntervention=null; public ?string $description=null; #[Assert\PositiveOrZero] public ?int $kilometrageReleve=null; #[Assert\NotNull] #[Assert\Positive] public ?int $vehiculeId=null; #[Assert\Positive] public ?int $rendezVousId=null; #[Assert\Type('array')] public array $services=[]; public static function fromRequest(array $data): self { $d=new self(); foreach($data as $k=>$v) if(property_exists($d,$k)) $d->$k=$v; return $d; } }