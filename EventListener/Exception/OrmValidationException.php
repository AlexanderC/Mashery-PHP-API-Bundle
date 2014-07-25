<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/22/14
 * Time: 16:58
 */

namespace AlexanderC\Api\MasheryBundle\EventListener\Exception;


use AlexanderC\Api\Mashery\ErrorObject;
use AlexanderC\Api\Mashery\InternalObjectInterface;

class OrmValidationException extends OrmException
{
    /**
     * @param InternalObjectInterface $entity
     * @param ErrorObject $error
     */
    public function __construct(InternalObjectInterface $entity, ErrorObject $error)
    {
        parent::__construct(
            sprintf("Invalid Mashery object: %s", $error->getMessage()),
            $error->getCode()
        );

        $this->setEntity($entity);
        $this->setErrorData($error->getData());
    }
} 