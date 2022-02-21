<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use leinne\pureentities\entity\Animal;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use leinne\pureentities\animation\EatGrassAnimation;
use leinne\pureentities\sound\ShearSound;
use pocketmine\block\Grass;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\Dye;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Shears;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use pocketmine\world\sound\BlockPlaceSound;
use pocketmine\world\Position;

class Sheep extends Animal{
    use WalkEntityTrait{
        entityBaseTick as baseTick;
    }

    public static function getRandomColor() : DyeColor{
        $int = mt_rand(0, 100);
        if($int < 5){
            return DyeColor::BLACK();
        }
        if($int < 10){
            return DyeColor::GRAY();
        }
        if($int < 15){
            return DyeColor::LIGHT_GRAY();
        }
        if($int < 18){
            return DyeColor::BROWN();
        }
        if(mt_rand(0, 500) === 0){
            return DyeColor::PINK();
        }
        return DyeColor::WHITE();
    }

    private bool $sheared;
    private int $eatDelay = 0;

    private DyeColor $color;

    public static function getNetworkTypeId() : string{
        return EntityIds::SHEEP;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(1.3, 0.9);
    }

    public function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);
        $this->sheared = $nbt->getByte("Sheared", 0) !== 0;
        $color = $nbt->getByte("Color", -1);
        $dyeColor = $color !== -1 ? DyeColorIdMap::getInstance()->fromId($color) : null;
        $this->color = $dyeColor ?? self::getRandomColor();
    }

    public function getDefaultMaxHealth() : int{
        return 8;
    }

    public function getName() : string{
        return "Sheep";
    }

    public function getColor() : DyeColor{
        return $this->color;
    }

    public function setColor(DyeColor $color){
        $this->color = $color;
        $this->networkPropertiesDirty = true;
    }

    public function isSheared() : bool{
        return $this->sheared;
    }

    public function setSheared(bool $sheared) : void{
        $this->sheared = $sheared;
        $this->networkPropertiesDirty = true;
    }

    public function shear() : bool{
        if(!$this->sheared && !$this->baby){
            $this->setSheared(true);
            $this->getWorld()->addSound($this->location, new ShearSound());
            $this->getWorld()->dropItem($this->location->add(0, 1, 0), ItemFactory::getInstance()->get(ItemIds::WOOL, DyeColorIdMap::getInstance()->toId($this->color), mt_rand(1, 3)));
            return true;
        }
        return false;
    }

    public function interact(Player $player, Item $item) : bool{
        if($item instanceof Shears && $this->shear()){
            $item->applyDamage(1);
            return true;
        }
        if($item instanceof Dye){
            $this->setColor($item->getColor());
            $item->pop();
            return true;
        }

        //TODO: Feed
        return false;
    }

    public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
        return false; //TODO: Implement item attraction
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = false;
        if($this->eatDelay > 0){
            $this->eatDelay -= $tickDiff;
            if($this->eatDelay <= 0){
                $hasUpdate = true;
                $dirt = VanillaBlocks::DIRT();
                $this->setSpeed(1.0);
                $this->setSheared(false);
                $this->getWorld()->setBlockAt((int) floor($this->location->x), (int) floor($this->location->y - 1), (int) floor($this->location->z), $dirt);
                $this->getWorld()->addSound($this->location, new BlockPlaceSound($dirt));
            }
        }elseif(
            $this->sheared &&
            !mt_rand(0, 999) &&
            $this->getWorld()->getBlockAt((int) floor($this->location->x), (int) floor($this->location->y - 1), (int) floor($this->location->z)) instanceof Grass
        ){
            $hasUpdate = true;
            $this->setSpeed(0);
            $this->eatDelay = 40;
            $this->broadcastAnimation(new EatGrassAnimation($this));
        }

        return $this->baseTick($tickDiff) || $hasUpdate;
    }

    public function interactTarget(?Entity $target, ?Position $next, int $tickDiff = 1) : bool{
        if(!parent::interactTarget($target, $next, $tickDiff)){
            return false;
        }

        // TODO: Animal AI Features
        return false;
    }

    protected function syncNetworkData(EntityMetadataCollection $properties) : void{
        parent::syncNetworkData($properties);

        $properties->setGenericFlag(EntityMetadataFlags::SHEARED, $this->sheared);
        $properties->setByte(EntityMetadataProperties::COLOR, DyeColorIdMap::getInstance()->toId($this->color));
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();

        $nbt->setByte("Sheared", $this->sheared ? 1 : 0);
        $nbt->setByte("Color", DyeColorIdMap::getInstance()->toId($this->color));
        return $nbt;
    }

    public function getDrops() : array{
        return [
            ItemFactory::getInstance()->get(ItemIds::WOOL, DyeColorIdMap::getInstance()->toId($this->color), 1),
            ItemFactory::getInstance()->get($this->isOnFire() ? ItemIds::COOKED_MUTTON : ItemIds::RAW_MUTTON, 0, mt_rand(1, 2))
        ];
    }

    public function getXpDropAmount() : int{
        return $this->baby ? 0 : mt_rand(1, 3);
    }

}