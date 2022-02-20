<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai\path;

use leinne\pureentities\entity\ai\navigator\EntityNavigator;
use pocketmine\world\Position;

abstract class PathFinder{

    protected EntityNavigator $navigator;

    public function __construct(EntityNavigator $navigator){
        $this->navigator = $navigator;
    }

    /**
     * Remove previously explored data
     */
    public abstract function reset() : void;

    /**
     * Finding the best path to get results
     *
     * @return Position[]|null
     */
    public abstract function search() : ?array;

}