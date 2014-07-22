<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/22/14
 * Time: 16:58
 */

namespace AlexanderC\Api\MasheryBundle\EventListener\Exception;


use AlexanderC\Api\Mashery\ErrorObject;

class OrmValidationException extends OrmException
{
    /**
     * @param ErrorObject $error
     */
    public function __construct(ErrorObject $error)
    {
        parent::__construct(
            sprintf("Invalid Mashery object: %s", $error->getMessage()),
            $error->getCode()
        );

        $this->setErrorData($error->getData());
    }
} 