<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use leinne\pureentities\entity\Animal;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;

class Pig extends Animal{
    use WalkEntityTrait;

    private bool $saddle = false;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->saddle = $nbt->getByte("Saddle", 0) !== 0;
    }

    public static function getNetworkTypeId() : string{
        return EntityIds::PIG;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(0.9, 0.9);
    }

    public function getDefaultMaxHealth() : int{
        return 10;
    }

    public function getName() : string{
        return 'Pig';
    }

    public function interact(Player $player, Item $item) : bool{
        //TODO: saddle function
        return false;
    }

    public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
        return false; //TODO: Implement item attraction
    }

    public function interactTarget() : bool{
        if(!parent::interactTarget()){
            return false;
        }

        // TODO: Animal AI Features
        return false;
    }

    protected function syncNetworkData(EntityMetadataCollection $properties) : void{
        parent::syncNetworkData($properties);

        $properties->setGenericFlag(EntityMetadataFlags::SADDLED, $this->saddle);
    }

    public function getDrops() : array{
        $drops = [
            ItemFactory::getInstance()->get($this->isOnFire() ? ItemIds::COOKED_PORKCHOP : ItemIds::RAW_PORKCHOP, 0, mt_rand(1, 3))
        ];
        if($this->saddle){
            $drops[] = ItemFactory::getInstance()->get(ItemIds::SADDLE);
        }
        return $drops;
    }

    public function getXpDropAmount() : int{
        return mt_rand(1, 3);
    }

}