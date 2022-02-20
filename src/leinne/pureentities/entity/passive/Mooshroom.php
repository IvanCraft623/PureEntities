<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Mooshroom extends Cow{
    //TODO: different color

    public static function getNetworkTypeId() : string{
        return EntityIds::MOOSHROOM;
    }

    public function getName() : string{
        return 'Mooshroom';
    }

}