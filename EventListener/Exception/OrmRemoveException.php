<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/22/14
 * Time: 14:26
 */

namespace AlexanderC\Api\MasheryBundle\EventListener\Exception;


use AlexanderC\Api\Mashery\ErrorObject;

class OrmRemoveException extends OrmException
{
    /**
     * @param InternalObjectInterface $entity
     * @param ErrorObject $error
     */
    public function __construct(InternalObjectInterface $entity, ErrorObject $error)
    {
        parent::__construct(
            sprintf("Error while removing Mashery object: %s", $error->getMessage()),
            $error->getCode()
        );

        $this->setEntity($entity);
        $this->setErrorData($error->getData());
    }
} 