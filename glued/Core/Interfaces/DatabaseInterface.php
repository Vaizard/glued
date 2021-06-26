<?php
// implement database interface
// extend the db thing we use and add querying the permissions table
// hint on permission to use when inheriting rights or on complex joins
// implement domains like so:
// 
// -> is logged in?
// -> data belongs to x domains
// -> am i a domain member? (neni toto spis atribut?, spis je, protoze i domena muze chtit rict ze nejaka data jsou public)
// ->
// 
// 
// permissions bude mapovat:
// object -> uspořádanou dvojici { domain, role } 
// root bude členem všech domén a všech rolí?
// 
// 
// 
namespace Glued\Core\Interfaces;
interface DatabaseInterface
{
    public function set($key, $value);
    public function get($key);
}