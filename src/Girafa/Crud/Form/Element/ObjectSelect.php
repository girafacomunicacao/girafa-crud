<?php
namespace Girafa\Crud\Form\Element;

use Girafa\Crud\Form\Element\Proxy;

/**
 * Extende
 * 
 * @package Girafa
 * @subpackage Crud\Form\Element
 * @author Tiago Vergutz <silver@girafacomunicacao.com.br>
 */
class ObjectSelect extends \DoctrineModule\Form\Element\ObjectSelect
{
	
	/**
     * @return Proxy
     */
    public function getProxy()
    {
        if (null === $this->proxy) {
            $this->proxy = new Proxy();
        }
        return $this->proxy;
    }
	
}
