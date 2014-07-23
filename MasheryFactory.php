<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/23/14
 * Time: 12:43
 */

namespace AlexanderC\Api\MasheryBundle;


use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use AlexanderC\Api\Mashery\Mashery;

class MasheryFactory
{
    /**
     * @return Mashery
     */
    public static function create()
    {
        $arguments = func_get_args();
        $client = array_shift($arguments);

        /** @var Mashery $mashery */
        $mashery = call_user_func_array('AlexanderC\Api\Mashery\Mashery::createInstance', $arguments);

        if($client) {
            $mashery->getClient()->getTransport()->setClient($client);
        }

        return $mashery;
    }
} 