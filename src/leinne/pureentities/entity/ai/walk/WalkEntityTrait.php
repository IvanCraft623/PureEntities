<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\walk;

use leinne\pureentities\entity\ai\EntityAI;
use leinne\pureentities\entity\ai\navigator\EntityNavigator;
use leinne\pureentities\entity\ai\navigator\WalkEntityNavigator;
use leinne\pureentities\entity\LivingBase;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\DoorBumpSound;
use pocketmine\world\sound\DoorCrashSound;

/**
 * This trait override most methods in the {@link LivingBase} abstract class.
 */
trait WalkEntityTrait{

    /** Save time until it break the door */
    private int $doorBreakTime = 0;

    /** Decide whether to break the door */
    private int $doorBreakDelay = 0;

    /** Check if the block to go is a door */
    private bool $checkDoorState = false;

    private ?Block $doorBlock = null;

    /**
     * @param int $tickDiff
     *
     * @return bool
     *@see LivingBase::entityBaseTick()
     *
     */
    protected function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if(!$this->isMovable()){
            return $hasUpdate;
        }

        $this->getNavigator()->update();

        /** @var Position $me */
        $me = $this->location;
        /** @var ?Entity $target */
        $target = $this->getTargetEntity();

        $next = $this->getNavigator()->next();
        if($next === null){
            return $hasUpdate;
        }

        $x = $next->x - $me->x;
        $z = $next->z - $me->z;
        $diff = abs($x) + abs($z);
        if(!$this->interactTarget($target, $next, $tickDiff) && $diff != 0){
            $hasUpdate = true;
            $motion = ($this->onGround ? 0.125 : 0.001) * $this->getSpeed() * $tickDiff / $diff;
            $this->motion->x += $x * $motion;
            $this->motion->z += $z * $motion;
        }

        $door = $this->checkDoorState;
        if(!$door && $this->doorBreakDelay > 0){
            $this->doorBreakTime = 0;
            $this->doorBreakDelay = 0;
        }
        $this->checkDoorState = false;
        if($hasUpdate && $this->onGround){
            foreach($this->getWorld()->getCollisionBlocks($this->boundingBox->addCoord($this->motion->x, $this->motion->y, $this->motion->z)) as $_ => $block){
                if($block->getCollisionBoxes()[0]->maxY - $this->boundingBox->minY > 1){
                    continue;
                }

                $pass = EntityAI::checkPassablity($this->location, $block);
                if($pass == EntityAI::BLOCK){
                    $hasUpdate = true;
                    $this->jump();
                    break;
                }elseif($pass == EntityAI::DOOR){
                    if($this->canBreakDoors() && $target !== null){
                        $this->checkDoorState = true;
                        if($this->doorBreakTime <= 0 && ++$this->doorBreakDelay > 20){
                            $this->doorBlock = $block;
                            $this->doorBreakTime = 180;
                        }
                    }
                    break;
                }
            }
        }

        if($door && !$this->checkDoorState && $this->doorBlock !== null){
            $pos = $this->doorBlock->getPosition();
            $pos->world->broadcastPacketToViewers($pos, LevelEventPacket::create(LevelEvent::BLOCK_STOP_BREAK, 0, $pos));
            $this->doorBlock = null;
        }

        $this->setRotation(
            rad2deg(atan2($z, $x)) - 90.0,
            $target === null ? 0.0 : rad2deg(-atan2($target->location->y - $this->location->y, sqrt(($target->location->x - $this->location->x) ** 2 + ($target->location->z - $this->location->z) ** 2)))
        );

        return $hasUpdate;
    }


    /**
     * @param float $dx
     * @param float $dy
     * @param float $dz
     *@see LivingBase::move()
     *
     */
    public function move(float $dx, float $dy, float $dz) : void{
        $before = $this->getPosition();
        parent::move($dx, $dy, $dz);
        $delay = (int) ($this->location->x - $before->x != $dx) + (int) ($this->location->z - $before->z != $dz);
        if($delay > 0 && $this->checkDoorState){
            $delay = -1;
            if($this->doorBlock !== null){
                $pos = $this->doorBlock->getPosition();
                if($this->doorBreakTime === 180){
                    $pos->world->broadcastPacketToViewers($pos, LevelEventPacket::create(LevelEvent::BLOCK_START_BREAK, 364, $pos));
                }

                if($this->doorBreakTime % mt_rand(3, 20) === 0){
                    $pos->world->addSound($pos, new DoorBumpSound());
                }

                if(--$this->doorBreakTime <= 0){
                    $this->doorBlock->onBreak(ItemFactory::air());
                    $pos->world->addSound($pos, new DoorCrashSound());
                    $pos->world->addParticle($pos->add(0.5, 0.5, 0.5), new BlockBreakParticle($this->doorBlock));
                }
            }
        }
        $this->getNavigator()->addStopDelay($delay);
    }

    public function getDefaultNavigator() : EntityNavigator{
        return new WalkEntityNavigator($this);
    }

    public function interactTarget(?Entity $target, ?Position $next, int $tickDiff = 1) : bool{}

}
