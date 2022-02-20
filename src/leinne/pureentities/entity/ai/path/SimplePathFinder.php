<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\path;

use pocketmine\world\Position;

class SimplePathFinder extends PathFinder{

    public function reset() : void{}

    /**
     * Finding the best path to get results
     *
     * @return Position[]|null
     */
    public function search(): ?array{
        return [$this->navigator->getGoal()];
    }

}