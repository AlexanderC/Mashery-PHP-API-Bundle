<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/22/14
 * Time: 14:17
 */

namespace AlexanderC\Api\MasheryBundle\EventListener\Exception;


use AlexanderC\Api\Mashery\ErrorObject;

class OrmSyncException extends OrmException
{
    /**
     * @param ErrorObject $error
     */
    public function __construct(ErrorObject $error)
    {
        parent::__construct(
            sprintf("Error while syncing Mashery object: %s", $error->getMessage()),
            $error->getCode()
        );
    }

} 