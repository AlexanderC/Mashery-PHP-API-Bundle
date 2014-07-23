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
     * @var object
     */
    protected static $container;

    /**
     * @param object $container
     */
    public function setContainer($container)
    {
        self::$container = $container;
    }

    /**
     * @return Mashery
     */
    public static function create()
    {
        $mashery = call_user_func_array('AlexanderC\Api\Mashery\Mashery::createInstance', func_get_args());

        $client = self::$container->getParameter('mashery_api_client');

        if($client) {
            /** @var Mashery $mashery */
            $mashery = self::$container->get('mashery.api');

            $mashery->getClient()->getTransport()->setClient($client);
        }

        return $mashery;
    }
} 