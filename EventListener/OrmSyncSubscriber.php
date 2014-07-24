<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/22/14
 * Time: 11:10
 */

namespace AlexanderC\Api\MasheryBundle\EventListener;


use AlexanderC\Api\Mashery\InternalObjectInterface;
use AlexanderC\Api\MasheryBundle\EventListener\Exception\OrmRemoveException;
use AlexanderC\Api\MasheryBundle\EventListener\Exception\OrmSyncException;
use AlexanderC\Api\MasheryBundle\EventListener\Exception\OrmValidationException;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use AlexanderC\Api\Mashery\Mashery;

class OrmSyncSubscriber implements EventSubscriber
{
    use ContainerAwareTrait;

    const CREATE = 0x001;
    const UPDATE = 0x002;
    const REMOVE = 0x003;

    const MASHERY_TRAIT = 'AlexanderC\Api\MasheryBundle\Orm\MasheryObjectTrait';

    /**
     * @var array
     */
    protected $usesCache = [];

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            'prePersist',
            'preUpdate',
            'postPersist',
            'postUpdate',
            'postRemove'
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->managePreEvent($args, self::UPDATE);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->managePreEvent($args, self::CREATE);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->managePostEvent($args, self::UPDATE);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->managePostEvent($args, self::CREATE);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $this->managePostEvent($args, self::REMOVE);
    }

    /**
     * @param LifecycleEventArgs $args
     * @param int $eventType
     * @throws Exception\OrmValidationException
     */
    protected function managePreEvent(LifecycleEventArgs $args, $eventType)
    {
        $entity = $args->getEntity();

        switch($eventType) {
            case self::CREATE:
            case self::UPDATE:
                if($this->isMasheryObject($entity)) {
                    $response = $this->getMashery()->validate($entity);

                    // verify for entity validity
                    if($response->isError()) {
                        throw new OrmValidationException($response->getError());
                    }
                }
                break;
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param int $eventType
     * @throws Exception\OrmRemoveException
     * @throws Exception\OrmSyncException
     */
    protected function managePostEvent(LifecycleEventArgs $args, $eventType)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

        switch($eventType) {
            case self::CREATE:
                if($this->isMasheryObject($entity)) {
                    $response = $this->getMashery()->create($entity);

                    if($response->isError()) {
                        throw new OrmSyncException($response->getError());
                    }

                    $entityManager->persist($entity);
                    $entityManager->flush();
                }
                break;
            case self::UPDATE:
                if($this->isMasheryObject($entity)) {
                    $response = $this->getMashery()->update($entity);

                    if($response->isError()) {
                        throw new OrmSyncException($response->getError());
                    }

                    $entityManager->persist($entity);
                    $entityManager->flush();
                }
                break;
            case self::REMOVE:
                if($this->isMasheryObject($entity)) {
                    $response = $this->getMashery()->delete($entity);

                    if($response->isError()) {
                        throw new OrmRemoveException($response->getError());
                    }
                }
                break;
        }
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isMasheryObject($entity)
    {
        $entityClass = get_class($entity);

        if(!isset($this->usesCache[$entityClass])) {
            $this->usesCache[$entityClass] = self::classUsesDeep($entityClass);
        }

        return $entity instanceof InternalObjectInterface
                && in_array(self::MASHERY_TRAIT, $this->usesCache[$entityClass])
                // disable classes that does not need to be synced
                && true === $entity->getMasherySyncState();
    }

    /**
     * @param string $class
     * @return array
     */
    protected static function classUsesDeep($class)
    {
        $traits = [];

        // Get traits of all parent classes
        do {
            $traits = array_merge(class_uses($class, true), $traits);
        } while ($class = get_parent_class($class));

        // Get traits of all parent traits
        $traitsToSearch = $traits;

        while (!empty($traitsToSearch)) {
            $newTraits = class_uses(array_pop($traitsToSearch), true);
            $traits = array_merge($newTraits, $traits);
            $traitsToSearch = array_merge($newTraits, $traitsToSearch);
        }

        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, true), $traits);
        }

        return array_unique($traits);
    }

    /**
     * @return Mashery
     */
    protected function getMashery()
    {
        return $this->container->get('mashery.api');
    }
} 