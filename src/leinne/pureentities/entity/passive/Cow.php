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

class Cow extends Animal{
    use WalkEntityTrait;

    public static function getNetworkTypeId() : string{
        return EntityIds::COW;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(1.3, 0.9);
    }

    public function getDefaultMaxHealth() : int{
        return 10;
    }

    public function getName() : string{
        return 'Cow';
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
            VanillaItems::LEATHER()->setCount(mt_rand(0, 2)),
            ItemFactory::getInstance()->get($this->isOnFire() ? ItemIds::STEAK : ItemIds::RAW_BEEF, 0, mt_rand(1, 3)),
        ];
    }

    public function getXpDropAmount() : int{
        return $this->baby ? 0 : mt_rand(1, 3);
    }

}