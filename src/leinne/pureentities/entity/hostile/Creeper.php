<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\hostile;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use leinne\pureentities\sound\FuseSound;
use pocketmine\entity\Entity;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Explosive;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\item\FlintSteel;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use pocketmine\world\Explosion;
use pocketmine\world\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\sound\FlintSteelSound;

class Creeper extends Monster implements Explosive{
    use WalkEntityTrait;

    public const DEFAULT_SPEED = 0.9;
    public const DEFAULT_FUSE = 30;

    private int $explosionRadius = 3;

    private int $fuse = self::DEFAULT_FUSE;

    private bool $powered = false;

    private bool $exploding = false;

    public static function getNetworkTypeId() : string{
        return EntityIds::CREEPER;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(1.8, 0.6);
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->explosionRadius = $nbt->getByte("ExplosionRadius", 3);
        $this->fuse = $nbt->getShort("Fuse", self::DEFAULT_FUSE);
        $this->exploding = $nbt->getByte("ignited", 0) !== 0;
        $this->powered = $nbt->getByte("powered", 0) !== 0;
        $this->setSpeed(self::DEFAULT_SPEED);
    }

    public function getName() : string{
        return 'Creeper';
    }

    public function getInteractDistance() : float{
        return 3;
    }

    public function isPowered() : bool{
        return $this->powered;
    }

    public function setPowered(bool $value) : void{
        $this->powered = $value;
        $this->explosionRadius = $value ? 6 : 3;
        $this->networkPropertiesDirty = true;
    }

    public function isAttackable() : bool{
        return $this->explosionRadius > 0;
    }

    public function getExplosionRadius() : int{
        return $this->explosionRadius;
    }

    public function setExplosionRadius(int $radius) : void{
        $this->explosionRadius = $radius;
    }

    public function getFuse() : int{
        return $this->fuse;
    }

    public function setFuse(int $fuse) : void{
        $this->fuse = $fuse;
        $this->networkPropertiesDirty = true;
    }

    public function setExploding(bool $value = true) : void{
        $this->exploding = $value;
        $this->networkPropertiesDirty = true;
    }

    public function interact(Player $player, Item $item) : bool{
        if($item instanceof FlintSteel && !$this->exploding){
            $this->setExploding();
            $item->applyDamage(1);
            $player->broadcastAnimation(new ArmSwingAnimation($player));
            $this->getWorld()->addSound($this->location, new FlintSteelSound());
            return true;
        }
        return false;
    }

    public function explode() : void{
        $ev = new ExplosionPrimeEvent($this, $this->explosionRadius);
        $ev->call();

        if(!$ev->isCancelled()){
            $explosion = new Explosion($this->getPosition(), $ev->getForce(), $this);
            if($ev->isBlockBreaking()){
                $explosion->explodeA();
            }
            $explosion->explodeB();
        }
    }

    public function interactTarget(?Entity $target, ?Position $next, int $tickDiff = 1) : bool{
        if(!$this->canInteractTarget() && !$this->exploding){
            if($this->fuse < self::DEFAULT_FUSE){
                $this->setFuse($this->fuse + 1);
            }elseif($this->getSpeed() < self::DEFAULT_FUSE){
                $this->setSpeed(self::DEFAULT_SPEED);
            }
            $this->setExploding(false);
            return false;
        }

        $this->setSpeed(0.35);
        if(!$this->exploding){
            $this->getWorld()->addSound($this->location, new FuseSound($this->location));
        }
        $this->setExploding();
        $this->setFuse($this->fuse - 1);
        if($this->fuse < 0){
            $this->flagForDespawn();
            $this->explode();
        }
        return false;
    }

    protected function syncNetworkData(EntityMetadataCollection $properties) : void{
        parent::syncNetworkData($properties);

        $properties->setInt(EntityMetadataProperties::FUSE_LENGTH, $this->fuse);
        $properties->setGenericFlag(EntityMetadataFlags::IGNITED, $this->exploding);
        $properties->setGenericFlag(EntityMetadataFlags::POWERED, $this->powered);
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setByte("ExplosionRadius", $this->explosionRadius);
        $nbt->setShort("Fuse", $this->fuse);
        $nbt->setByte("ignited", $this->exploding ? 1 : 0);
        $nbt->setByte("powered", $this->powered ? 1 : 0);
        return $nbt;
    }

    public function getDrops() : array{
        return [
            VanillaItems::GUNPOWDER()->setCount(mt_rand(0, 2))
        ];
    }

    public function getXpDropAmount() : int{
        return 5;
    }

}