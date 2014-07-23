<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/21/14
 * Time: 12:13
 */

namespace AlexanderC\Api\MasheryBundle;

use AlexanderC\Api\Mashery\Mashery;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MasheryApiBundle extends Bundle
{
    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $client = $container->getParameter('mashery_api_client');

        if($client) {
            /** @var Mashery $mashery */
            $mashery = $container->get('mashery.api');

            $mashery->getClient()->getTransport()->setClient($client);
        }
    }
} 