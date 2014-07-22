<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/22/14
 * Time: 14:27
 */

namespace AlexanderC\Api\MasheryBundle\EventListener\Exception;


class OrmException extends \RuntimeException
{
    /**
     * @var array
     */
    protected $errorData;

    /**
     * @param array $errorData
     */
    public function setErrorData(array $errorData)
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
} 