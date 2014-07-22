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
    public $mashery_object_id;

    /**
     * @param int $mashery_object_id
     */
    public function setMasheryObjectId($mashery_object_id)
    {
        $this->mashery_object_id = $mashery_object_id;
    }

    /**
     * @return int
     */
    public function getMasheryObjectId()
    {
        return $this->mashery_object_id;
    }

    /**
     * Properties that should be synced
     *
     * @return array
     */
    public function getMasherySyncProperties()
    {
        return array_merge($this->__getMasherySyncProperties(), ['mashery_object_id' => 'id']);
    }
} 