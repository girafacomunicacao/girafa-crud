<?php
namespace Girafa\Crud\Form\Element;

/**
 * Adiciona a classe \DoctrineModule\Form\Element\Proxy a possibilidade
 * de inserir um valor "em branco" no inicio do elemento
 * 
 * @package Girafa
 * @subpackage Crud\Form\Element
 * @author Tiago Vergutz <silver@girafacomunicacao.com.br>
 */
class Proxy extends \DoctrineModule\Form\Element\Proxy
{
    
	/**
	 * @var array $firstBlank Optional blank value for prepend to element
	 */
	protected $blankValue = array();
	
	public function setOptions($options)
    {
        parent::setOptions($options);

        if (isset($options['blank_value'])) {
            $this->blankValue = $options['blank_value'];
        }
    }
	
	/**
     * Load value options with optional blank value at first option
     *
	 * @throws \RuntimeException
     * @return void
     */
    protected function loadValueOptions()
    {
		parent::loadValueOptions();

		if(is_array($this->blankValue) && count($this->blankValue)) {
			$this->valueOptions = array_merge($this->blankValue, $this->valueOptions);
		}
    }
}
