<?php
/**
 * Created by PhpStorm.
 * User: AlexanderC <self@alexanderc.me>
 * Date: 7/21/14
 * Time: 15:05
 */

namespace AlexanderC\Api\MasheryBundle;


use AlexanderC\Api\Mashery\Mashery;

class MasheryFactory
{
    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->arguments = func_get_args();
    }

    /**
     * @return Mashery
     */
    public function get()
    {
        return call_user_func_array('AlexanderC\Api\Mashery\Mashery::create', $this->arguments);
    }
} 