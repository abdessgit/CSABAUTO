<?php
namespace App\Dto;
use Symfony\Component\Validator\Constraints as Assert;
class CreateMessageDto { #[Assert\NotBlank] public ?string $contenu=null; #[Assert\NotNull] #[Assert\Positive] public ?int $conversationId=null; #[Assert\NotNull] #[Assert\Positive] public ?int $expediteurId=null; public static function fromRequest(array $data): self { $d=new self(); foreach($data as $k=>$v) if(property_exists($d,$k)) $d->$k=$v; return $d; } }