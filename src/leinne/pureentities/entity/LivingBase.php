<?php

declare(strict_types=1);

namespace leinne\pureentities\entity;

use leinne\pureentities\entity\ai\navigator\EntityNavigator;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\World;

abstract class LivingBase extends Living{

    protected $stepHeight = 0.6;

    protected $jumpVelocity = 0.52;

    private float $speed = 1.0;

    protected int $interactDelay = 0;

    protected bool $breakDoor = false;

    protected bool $fixedTarget = false;

    protected ?EntityNavigator $navigator = null;

    public abstract function getDefaultNavigator() : EntityNavigator;

    public function getNavigator() : EntityNavigator{
        return $this->navigator ?? $this->navigator = $this->getDefaultNavigator();
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setMaxHealth($health = $nbt->getInt("MaxHealth", $this->getDefaultMaxHealth()));
        if($nbt->getTag("HealF") instanceof FloatTag){
            $health = $nbt->getFloat("HealF");
        }elseif($nbt->getTag("Health") instanceof FloatTag or $nbt->getTag("Health") instanceof IntTag){
            $healthTag = $nbt->getTag("Health");
            $health = (float) $healthTag->getValue();
        }

        $this->setHealth($health);
        $this->setImmobile();
    }

    /** Minimum distance for interaction */
    public function getInteractDistance() : float{
        return 0.6;
    }

    /** Check if interaction is possible */
    public function canInteractTarget() : bool{
        $target = $this->getTargetEntity();
        if($target === null){
            return false;
        }

        $width = $this->getInteractDistance() + ($this->size->getWidth() + $target->size->getWidth()) / 2;
        return abs($this->location->x - $target->location->x) <= $width
            && abs($this->location->z - $target->location->z) <= $width
            && abs($this->location->y - $target->location->y) <= min(1, $this->size->getEyeHeight());
    }

    public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
        return $this->fixedTarget || ($target instanceof Living && $distanceSquare <= 10000);
    }

    /** interact with target */
    public function interactTarget(?Entity $target, ?Position $next, int $tickDiff = 1) : bool{
        ++$this->interactDelay;
        if(!$this->canInteractTarget()){
            return false;
        }
        return true;
    }

    public function interact(Player $player, Item $item) : bool{
        return false;
    }

    public function getDefaultMaxHealth() : int{
        return 20;
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setInt("MaxHealth" , $this->getMaxHealth());
        return $nbt;
    }

    public function onUpdate(int $currentTick) : bool{
        if(!$this->canSpawnPeaceful() && $this->getWorld()->getDifficulty() === World::DIFFICULTY_PEACEFUL){
            $this->flagForDespawn();
            return false;
        }

        return parent::onUpdate($currentTick);
    }

    public function isMovable() : bool{
        return true;
    }

    public function canBreakDoors() : bool{
        return $this->breakDoor;
    }

    public function canSpawnPeaceful() : bool{
        return true;
    }

    public function updateMovement(bool $teleport = false) : void{
        $send = false;
        $pos = $this->getLocation();
        $last = $this->lastLocation;
        if(
            $last->x !== $pos->x
            || $last->y !== $pos->y
            || $last->z !== $pos->z
            || $last->yaw !== $pos->yaw
            || $last->pitch !== $pos->pitch
        ){
            $send = true;
            $this->lastLocation = $this->getLocation();
        }

        if(
            $this->lastMotion->x !== $this->motion->x
            || $this->lastMotion->y !== $this->motion->y
            || $this->lastMotion->z !== $this->motion->z
        ){
            $this->lastMotion = clone $this->motion;
        }

        if($send){
            $this->broadcastMovement($teleport);
        }
    }

    public function getSpeed() : float{
        return $this->speed;
    }

    public function setSpeed(float $speed) : void{
        $this->speed = $speed;
    }

    public function setTargetEntity(?Entity $target, bool $fixed = false) : void{
        parent::setTargetEntity($target);
        if($target !== null){
            $this->getNavigator()->setGoal($target->getPosition());
        }
        $this->fixedTarget = $fixed;
    }

    public function setMotion(Vector3 $motion) : bool{
        $return = parent::setMotion($motion);
        $this->getNavigator()->updateGoal();
        return $return;
    }

}
