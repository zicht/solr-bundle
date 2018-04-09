<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\MenuDebugBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Zicht\Bundle\MenuBundle\Entity\MenuItem;

/**
 * Class LoadClassMetadataListener
 *
 * @package Zicht\Bundle\MenuDebugBundle\EventListener
 */
class LoadClassMetadataListener
{
    /**
     * @param LoadClassMetadataEventArgs $args
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $cmd */
        $cmd = $args->getClassMetadata();

        if ($cmd->isRootEntity() && MenuItem::class === $cmd->getName()) {
            $cmd->addEntityListener(Events::preRemove, MenuItemEntityListener::class, 'preRemove');
            $cmd->addEntityListener(Events::prePersist, MenuItemEntityListener::class, 'prePersist');
            $cmd->addEntityListener(Events::preUpdate, MenuItemEntityListener::class, 'preUpdate');
        }
    }
}