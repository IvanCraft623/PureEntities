<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\block\Block;
use pocketmine\block\Door;
use pocketmine\block\Lava;
use pocketmine\block\Trapdoor;
use pocketmine\block\WoodenDoor;
use pocketmine\math\Facing;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class EntityAI{

    const WALL = 0;
    const PASS = 1;
    const BLOCK = 2;
    const SLAB = 3;
    const UP_SLAB = 4;
    const DOOR = 5;

    public static function getHash(Vector3 $pos) : string{
        $pos = self::getFloorPos($pos);
        return "{$pos->x}:{$pos->y}:{$pos->z}";
    }

    public static function getFloorPos(Vector3 $pos) : Position{
        $newPos = new Position(Math::floorFloat($pos->x), $pos->getFloorY(), Math::floorFloat($pos->z), null);
        if($pos instanceof Position){
            $newPos->world = $pos->world;
        }
        return $newPos;
    }

    /**
     * A method that checks the state of a specific block
     *
     * @param Block|Position $data
     *
     * @return int
     */
    public static function checkBlockState($data) : int{
        if($data instanceof Position){
            $floor = self::getFloorPos($data);
            $block = $data->world->getBlockAt($floor->x, $floor->y, $floor->z);
        }elseif($data instanceof Block){
            $block = $data;
        }else{
            throw new \RuntimeException("$data is not Block|Position class");
        }

        $value = EntityAI::BLOCK;
        if($block instanceof Door && count($block->getAffectedBlocks()) > 1){ //when the door
            $value = $block instanceof WoodenDoor ? EntityAI::DOOR : EntityAI::WALL; //Determine if it is an iron gate
        }else{
            $min = 256;
            $max = -1;
            foreach($block->getCollisionBoxes() as $_ => $bb){
                $min = min($min, $bb->minY);
                $max = max($max, $bb->maxY);
            }
            $blockBox = $block->getCollisionBoxes()[0] ?? null;
            $boxDiff = $blockBox === null ? 0 : $max - $min;
            if($boxDiff <= 0){
                if($block instanceof Lava){ //Exception handling among passable blocks
                    $value = EntityAI::WALL;
                }else{
                    $value = EntityAI::PASS;
                }
            }elseif($boxDiff > 1){ //if the fence
                $value = EntityAI::WALL;
            }elseif($boxDiff <= 0.5){ //Half block/carpet/trap door, etc.
                $value = $blockBox->minY == (int) $blockBox->minY ? EntityAI::SLAB : EntityAI::UP_SLAB;
            }
        }
        return $block instanceof Trapdoor ? EntityAI::PASS : $value; //TODO: trapdoors, carpets, etc.
    }

    /**
     * Method to determine whether a block is traversable
     *
     * @param Position $pos
     * @param Block|null $block
     *
     * @return int
     */
    public static function checkPassablity(Position $pos, ?Block $block = null) : int{
        if($block === null){
            $floor = self::getFloorPos($pos);
            $block = $pos->world->getBlockAt($floor->x, $floor->y, $floor->z);
        }else{
            $floor = $block->getPosition();
        }
        $state = self::checkBlockState($block); //The block state at the current location is
        switch($state){
            case EntityAI::WALL:
            case EntityAI::DOOR: //If it's a wall or a door, no more checking
                return $state;
            case EntityAI::PASS: //when it is possible to pass
                //If the upper block can also pass, it will pass, otherwise it will be a wall.
                return self::checkBlockState($floor->getSide(Facing::UP)) === EntityAI::PASS ? EntityAI::PASS : EntityAI::WALL;
            case EntityAI::BLOCK:
            case EntityAI::UP_SLAB: //In case of a block or a half block installed above
                $up = self::checkBlockState($upBlock = $block->getSide(Facing::UP)); //The block at y+1 is
                if($up === EntityAI::SLAB){ //half a block
                    $up2 = self::checkBlockState($floor->getSide(Facing::UP, 2));
                    //If it is possible to pass above it, and the difference between the highest point of the block and its position is less than or equal to the block, it is judged as a block
                    return $up2 === EntityAI::PASS && $upBlock->getCollisionBoxes()[0]->maxY - $pos->y <= 1 ? EntityAI::BLOCK : EntityAI::WALL;
                }elseif($up === EntityAI::PASS){ //when it is possible to pass
                    //If y+ 2 is also passable
                    return self::checkBlockState($floor->getSide(Facing::UP, 2)) === EntityAI::PASS ?
                        //If the difference between the highest point of the block and its position is less than half a block, it is judged as a half block. Otherwise, it is judged as a block.
                        ($block->getCollisionBoxes()[0]->maxY - $pos->y <= 0.5 ? EntityAI::SLAB : EntityAI::BLOCK) : EntityAI::WALL;
                }
                return EntityAI::WALL;
            case EntityAI::SLAB:
                return (
                    self::checkBlockState($floor->getSide(Facing::UP)) === EntityAI::PASS //y + 1 is passable
                    && (($up = self::checkBlockState($floor->getSide(Facing::UP, 2))) === EntityAI::PASS || $up === EntityAI::UP_SLAB) //If it is possible to pass y + 2 (including half blocks),
                ) ? EntityAI::SLAB : EntityAI::WALL;
        }
        return EntityAI::WALL;
    }

    /**
     * Calculate the final y-coordinate that will be reached from the current position
     *
     * @param Position $pos
     *
     * @return float
     */
    public static function calculateYOffset(Position $pos) : float{
        $newY = (int) $pos->y;
        switch(EntityAI::checkBlockState($pos)){
            case EntityAI::BLOCK:
                $newY += 1;
                break;
            case EntityAI::SLAB:
                $newY += 0.5;
                break;
            case EntityAI::PASS:
                $newPos = self::getFloorPos($pos);
                $newPos->y -= 1;
                for(; $newPos->y >= 0; $newPos->y -= 1){
                    $block = $pos->world->getBlockAt($newPos->x, $newPos->y, $newPos->z);
                    $state = EntityAI::checkBlockState($block);
                    if($state === EntityAI::UP_SLAB || $state === EntityAI::BLOCK || $state === EntityAI::SLAB){
                        foreach($block->getCollisionBoxes() as $_ => $bb){
                            if($newPos->y < $bb->maxY){
                                $newPos->y = $bb->maxY;
                            }
                        }
                        break;
                    }
                }
                $newY = $newPos->y;
                break;
        }
        return $newY;
    }

}
