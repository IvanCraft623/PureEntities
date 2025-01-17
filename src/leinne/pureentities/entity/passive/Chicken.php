<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use leinne\pureentities\entity\Animal;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\Position;

class Chicken extends Animal{
    use WalkEntityTrait;

    public static function getNetworkTypeId() : string{
        return EntityIds::CHICKEN;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(0.8, 0.6);
    }

    public function getDefaultMaxHealth() : int{
        return 4;
    }

    public function getName() : string{
        return 'Chicken';
    }

    public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
        return false; //TODO: Implement item attraction
    }

    public function interactTarget(?Entity $target, ?Position $next, int $tickDiff = 1) : bool{
        if(!parent::interactTarget($target, $next, $tickDiff)){
            return false;
        }

        // TODO: Animal AI Features
        return false;
    }

    public function getDrops() : array{
        return [
            ItemFactory::getInstance()->get($this->isOnFire() ? ItemIds::COOKED_CHICKEN : ItemIds::RAW_CHICKEN, 0, 1),
            VanillaItems::FEATHER()->setCount(mt_rand(0, 2))
        ];
    }

    public function getXpDropAmount() : int{
        return $this->baby ? 0 : mt_rand(1, 3);
    }

}