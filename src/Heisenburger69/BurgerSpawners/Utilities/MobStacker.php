<?php
declare(strict_types=1);

namespace Heisenburger69\BurgerSpawners\Utilities;

use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

//From MobStacker by UnknownOre

class Mobstacker
{

    /* @var Living */
    private $entity;

    /** @var string */
    CONST STACK = 'stack';

    /**
     * Mobstacker constructor.
     * @param Living $entity
     */
    public function __construct(Living $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return int
     */
    public function getStackAmount(): int
    {
        return $this->entity->namedtag->getInt(self::STACK);
    }

    /**
     * @return bool
     */
    public function isStacked(): bool
    {
        return $this->entity->namedtag->hasTag(self::STACK);
    }

    public function stack(): void
    {
        if ($this->isStacked()) {
            $this->updateNameTag();
            return;
        }
        if (($mob = $this->findNearStack()) == null) {
            $nbt = new IntTag(self::STACK, 1);
            $this->entity->namedtag->setTag($nbt);
            $mobstack = $this;
        } else {
            $this->entity->flagForDespawn();
            $mobstack = new Mobstacker($mob);
            $count = $mob->namedtag->getInt(self::STACK);
            $mob->namedtag->setInt(self::STACK, ++$count);
        }
        $mobstack->updateNameTag();
    }

    public function updateNameTag(): void
    {
        $nbt = $this->entity->namedtag;
        $this->entity->setNameTagAlwaysVisible(True);
        $this->entity->setNameTag(C::BOLD.C::AQUA.$nbt->getInt(self::STACK) . 'x ' . C::BOLD . C::GOLD . $this->entity->getName());
    }

    /**
     * @return bool
     */
    public function removeStack(): bool
    {
        $entity = $this->entity;
        $nbt = $entity->namedtag;
        if (!$this->isStacked() or ($c = $this->getStackAmount()) <= 1) {
            return false;
        }
        $nbt->setInt(self::STACK, --$c);
        $event = new EntityDeathEvent($entity, $drops = $entity->getDrops());
        $event->call();
        $this->updateNameTag();
        foreach ($drops as $drop) {
            $entity->getLevel()->dropItem($entity->getPosition(), $drop);
        }
        return true;
    }

    /**
     * @param int $range
     * @return Living|null
     */
    public function findNearStack(int $range = 16): ?Living
    {
        $entity = $this->entity;
        if ($entity->isFlaggedForDespawn() or $entity->isClosed()) return null;
        $boundingbox = $entity->getBoundingBox()->expandedCopy($range, $range, $range);
        foreach ($entity->getLevel()->getNearbyEntities($boundingbox) as $e) {
            if (!$e instanceof Player and $e instanceof Living) {
                if ($e->distance($entity) <= $range and $e->getName() == $entity->getName()) {
                    $ae = new Mobstacker($e);
                    if ($ae->isStacked() and !$this->isStacked()) return $e;
                }
            }
        }
        return null;
    }
}