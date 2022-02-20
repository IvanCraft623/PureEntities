<?php

declare(strict_types=1);

namespace leinne\pureentities\animation;

use pocketmine\entity\animation\Animation;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;

class EatGrassAnimation implements Animation{
    private Entity $entity;

    public function __construct(Entity $entity){
        $this->entity = $entity;
    }

    public function encode() : array{
        return [
            ActorEventPacket::create($this->entity->getId(), ActorEvent::EAT_GRASS_ANIMATION, 0)
        ];
    }
}