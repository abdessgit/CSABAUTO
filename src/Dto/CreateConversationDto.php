<?php
namespace App\Dto;
use Symfony\Component\Validator\Constraints as Assert;
class CreateConversationDto { #[Assert\NotNull] #[Assert\Positive] public ?int $clientId=null; #[Assert\Positive] public ?int $moderateurId=null; #[Assert\Positive] public ?int $annonceId=null; public static function fromRequest(array $data): self { $d=new self(); foreach($data as $k=>$v) if(property_exists($d,$k)) $d->$k=$v; return $d; } }