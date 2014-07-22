<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/22/14
 * Time: 11:10
 */

namespace AlexanderC\Api\MasheryBundle\EventListener;


use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OrmSyncSubscriber implements EventSubscriber
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'postUpdate',
            'postRemove'
        ];
    }

    public function postUpdate(LifecycleEventArgs $args)
    {

    }

    public function postPersist(LifecycleEventArgs $args)
    {

    }

    public function postRemove(LifecycleEventArgs $args)
    {

    }
} 