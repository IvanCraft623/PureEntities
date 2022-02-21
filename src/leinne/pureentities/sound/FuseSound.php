<?php

declare(strict_types=1);

namespace leinne\pureentities\sound;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\world\sound\Sound;

class FuseSound implements Sound{

    public function encode(Vector3 $pos) : array{
        return [LevelSoundEventPacket::nonActorSound(LevelSoundEvent::FUSE, $pos, false)];
    }
}