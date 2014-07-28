<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/22/14
 * Time: 11:10
 */

namespace AlexanderC\Api\MasheryBundle\EventListener;


use AlexanderC\Api\Mashery\Helpers\ObjectSyncer;
use AlexanderC\Api\Mashery\InternalObjectInterface;
use AlexanderC\Api\MasheryBundle\EventListener\Exception\OrmRemoveException;
use AlexanderC\Api\MasheryBundle\EventListener\Exception\OrmSyncException;
use AlexanderC\Api\MasheryBundle\EventListener\Exception\OrmValidationException;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\PreUpdateEventArgs;
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
     * @var array
     */
    protected $skipEntityUpdateStack = [];

    /**
     * @var bool
     */
    protected $listen = true;

    /**
     * @return $this
     */
    public function stop()
    {
        $this->listen = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function resume()
    {
        $this->listen = true;

        return $this;
    }

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
        if(false === $this->listen) {
            return;
        }

        $entity = $args->getEntity();

        $isMasheryObject = $this->isMasheryObject($entity);
        $skipUpdateFields = false;
        $skipExecution = false;

        switch($eventType) {
            case self::UPDATE:
                /** @var PreUpdateEventArgs $args */
                $args;

                if($isMasheryObject) {
                    $skipExecution = true;
                    $entityFields = ObjectSyncer::getObjectPropertiesMap($entity, true);

                    foreach($entityFields as $field) {
                        if($args->hasChangedField($field)) {
                            $skipExecution = false;
                            break;
                        }
                    }

                    if(true === $skipExecution) {
                        $this->skipEntityUpdateStack[spl_object_hash($entity)] = $entity;
                    }
                }

                $skipUpdateFields = true;
            case self::CREATE:
                if($isMasheryObject
                    && false === $skipExecution
                    && false === $entity->getSkipValidation()) {

                    $response = $this->getMashery()->validate($entity, $skipUpdateFields);

                    // verify for entity validity
                    if($response->isError()) {
                        throw new OrmValidationException($entity, $response->getError());
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
        if(false === $this->listen) {
            return;
        }

        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

        switch($eventType) {
            case self::CREATE:
                if($this->isMasheryObject($entity)) {
                    $response = $this->getMashery()->create($entity);

                    if($response->isError()) {
                        throw new OrmSyncException($entity, $response->getError());
                    }

                    $this->disableSync($entity);

                    $entityManager->persist($entity);
                    $entityManager->flush();

                    $this->enableSync($entity);
                }
                break;
            case self::UPDATE:
                $entityHash = spl_object_hash($entity);

                if($this->isMasheryObject($entity)
                    && !($unsetEntityNoUpdate = isset($this->skipEntityUpdateStack[$entityHash]))) {

                    $response = $this->getMashery()->update($entity);

                    if($response->isError()) {
                        throw new OrmSyncException($entity, $response->getError());
                    }

                    $this->disableSync($entity);

                    $entityManager->persist($entity);
                    $entityManager->flush();

                    $this->enableSync($entity);
                    break;
                }

                if(true === $unsetEntityNoUpdate) {
                    unset($this->skipEntityUpdateStack[$entityHash]);
                }
                break;
            case self::REMOVE:
                if($this->isMasheryObject($entity)) {
                    $response = $this->getMashery()->delete($entity);

                    if($response->isError()) {
                        throw new OrmRemoveException($entity, $response->getError());
                    }
                }
                break;
        }
    }

    /**
     * @param object $entity
     */
    protected function disableSync($entity)
    {
        $entity->setSkipValidation(true);
        $entity->setMasherySyncState(false);
    }

    /**
     * @param object $entity
     */
    protected function enableSync($entity)
    {
        $entity->setMasherySyncState(true);
        $entity->setSkipValidation(false);
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