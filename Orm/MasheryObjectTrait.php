<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/22/14
 * Time: 11:49
 */

namespace AlexanderC\Api\MasheryBundle\Orm;


trait MasheryObjectTrait
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer", unique=true, nullable=true, options={"comment"="Id of current object stored in mashery."})
     */
    protected $mashery_object_id;

    /**
     * @param int $mashery_object_id
     */
    final public function setMasheryObjectId($mashery_object_id)
    {
        $this->mashery_object_id = $mashery_object_id;
    }

    /**
     * @return int
     */
    final public function getMasheryObjectId()
    {
        return $this->mashery_object_id;
    }

    /**
     * Properties that should be synced
     *
     * @return array
     * @throws \RuntimeException
     */
    final public function getMasherySyncProperties()
    {
        if(!method_exists($this, '__getMasherySyncProperties')) {
            throw new \RuntimeException(
                "You must implement __getMasherySyncProperties() method".
                " instead of getMasherySyncProperties(), because it is used internally"
            );
        }

        return array_merge($this->__getMasherySyncProperties(), ['mashery_object_id' => 'id']);
    }

    /**
     * Allow only a normal code!
     *
     * @return bool
     */
    final public function masheryUseSettersAndGetters()
    {
        return true;
    }
} 