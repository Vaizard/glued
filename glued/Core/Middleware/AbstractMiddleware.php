<?php

namespace Glued\Core\Middleware;
use Psr\Container\ContainerInterface;


class AbstractMiddleware
{
    protected $c;

    public function __construct(ContainerInterface $c)
    {
        $this->c = $c;
    }

    /**
     * __get is a magic method that allows us to always get the correct property out of the 
     * container, allowing to write $this->db->method() instead of $this->c->db->method()
     * @param  string $property Container property
     */
    public function __get($property)
    {
        if ($this->c->get($property)) {
            return $this->c->get($property);
        }
    }

}