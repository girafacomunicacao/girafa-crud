<?php
namespace Girafa\Crud\Validator\CadastroDePessoas;

use Girafa\Crud\Validator\CadastroDePessoas\AbstractValidator;

/**
 * Validador para Cadastro de Pessoa Física
 *
 * @category Validator
 * @package  Girafa\Crud\Validator
 * @subpackage CadastroDePessoas
 * @author   Wanderson Henrique Camargo Rosa
 * @see http://www.wanderson.camargo.nom.br/2011/07/validador-de-cpf-e-cnpj-para-zend-framework/
 */
class Cpf extends AbstractValidator
{
    /**
     * Tamanho do Campo
     * @var int
     */
    protected $size = 11;
 
    /**
     * Modificadores de Dígitos
     * @var array
     */
    protected $modifiers = array(
        array(10,9,8,7,6,5,4,3,2),
        array(11,10,9,8,7,6,5,4,3,2)
    );
}