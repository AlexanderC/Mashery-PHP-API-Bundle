<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/22/14
 * Time: 14:27
 */

namespace AlexanderC\Api\MasheryBundle\EventListener\Exception;


use AlexanderC\Api\Mashery\InternalObjectInterface;

class OrmException extends \RuntimeException
{
    /**
     * @var array|null
     */
    protected $errorData;

    /**
     * @var InternalObjectInterface
     */
    protected $entity;

    /**
     * @param array $errorData
     */
    public function setErrorData($errorData)
    {
        $this->errorData = $errorData;
    }

    /**
     * @return array
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

    /**
     * @param \AlexanderC\Api\Mashery\InternalObjectInterface $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return \AlexanderC\Api\Mashery\InternalObjectInterface
     */
    public function getEntity()
    {
        return $this->entity;
    }
} 