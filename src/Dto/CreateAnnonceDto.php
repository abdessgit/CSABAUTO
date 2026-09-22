<?php
namespace App\Dto;
use Symfony\Component\Validator\Constraints as Assert;
class CreateAnnonceDto { #[Assert\NotBlank] #[Assert\Length(max:255)] public ?string $titre=null; #[Assert\NotBlank] public ?string $description=null; #[Assert\NotNull] #[Assert\Positive] public ?string $prix=null; #[Assert\NotNull] #[Assert\Positive] public ?int $vehiculeId=null; public static function fromRequest(array $data): self { $d=new self(); foreach($data as $k=>$v) if(property_exists($d,$k)) $d->$k=$v; return $d; } }