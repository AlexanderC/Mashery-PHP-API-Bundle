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
                    // verify for entity validity
                    if(!$this->getMashery()->validate($entity, [], $response)) {
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
        return $entity instanceof InternalObjectInterface
                && method_exists($entity, 'getMasheryObjectId') /* it's easier than to check for a trait */
            ;
    }

    /**
     * @return Mashery
     */
    protected function getMashery()
    {
        return $this->container->get('mashery.api');
    }
} 