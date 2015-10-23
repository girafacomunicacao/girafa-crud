<?php
namespace Core\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Constroi um Datagrid utilizando <table>
 * 
 * @category View Helper
 * @package Girafa\Crud
 * @author  Girafa
 */
class Datagrid extends AbstractHelper implements ServiceLocatorAwareInterface
{
	
	/**
     * Set the service locator.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return CustomHelper
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }
    
    
    /**
     * Get the service locator.
     *
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
	
	/**
	 * Funcao recursiva para buscar valores para preencher uma celula do grid
	 * 
	 * @param array $args Definicao de como obter o valor a partir de $row
	 * @param Object|array $row Fonte dos dados
	 * @return mixed Valor obtido para a celula
	 */
	protected function getCellValue($args, $row)
	{
		$key = $args['key'];
		
		// Toma diferente decisoes dependendo do tipo do campo
		switch ($args['type']) {
			// Coluna com Checkbox para ação em massa
			case 'cb': 
				$value = '<input class="cb-row" type="checkbox" name="id_' . $row->id . '" value="1">';
			break;
				
			// Coluna com os botoes de acoes do datagrid (edit/delete/...)
			case 'actions':
				$value = '';
				foreach ($key as $action => $options) {
					$value .= '<a';
					
					// Attributes
					if(is_array($options['attributes']) && count($options['attributes'])) {
						foreach ($options['attributes'] as $attr => $attrValue) {
							$value .= ' ' . $attr . '="' . $attrValue . '"';
						}
					}
					
					// Link
					$link = $options['link'];
					if(is_array($options['link'])) {
						$linkArgs = array();
						foreach ($link['args'] as $argKey => $argValue) {
							if(is_array($argValue))
								$argValue = $this->getCellValue($argValue, $row);
							
							if($argKey == 'controller' && $argValue == '__CONTROLLER__') {
								$currentRouteHelper = $this->getServiceLocator()->get('currentRoute');								
								$argValue = $currentRouteHelper->__invoke('__CONTROLLER__');
							}
							$linkArgs[$argKey] = $argValue;
						}
						$urlHelper = $this->getServiceLocator()->get('url');
						
						if(is_array($link['params']) && count($link['params'])) {
							foreach ($link['params'] as $paramKey => $paramVal) {
								$params .= $paramKey . '=' . $paramVal . '&';
							}
							$params = trim($params, '&');
						}
						
						// Gera o link baseado nos args						
						$link = $urlHelper->__invoke($link['route'], $linkArgs);
						
						// Insere a querystring no link
						if($params != '') {
							// Verifica se já existe uma '?' no link
							$pos = strpos($link, '?');
							if($pos === false) {
								$link .= '?';
							// Verifica se ele esta terminando com um '&'
							} elseif($pos < strlen($link)) {
								$link .= '&';
							}
							$link .= $params;
						}
					}					
					$value .= ' href="' . $link . '"';
					
					// Text
					$value .= '>' . $options['text'];
					
					$value .= '</a>' . PHP_EOL;
				}
			break;
			
			// Obter dados sendo o $row um array
			case 'array':
				$value = $row[$key];
			break;					
		
			// Obter dados utilizando uma propriedade, sendo o $row um Objeto
			case 'property':
			default:				
				$value = $row->$key;
			break;
		
			// Obter dados utilizando um metodo de $row
			case 'method':				
				$reflectionMethod = new \ReflectionMethod(get_class($row), $key);				
				
				if(isset($args['params'])) {					
					//$value = call_user_func_array(array($row, $key), $args['params']);
					$value = $reflectionMethod->invokeArgs($row, $args['params']);
				} else {
					//$value = call_user_func(array($row, $key));
					$value = $reflectionMethod->invoke($row);
				}
			break;
						
			// Obter dados utilizando uma funcao callback, 
			// que recebe como parametro $row e o ServiceLocator
			case 'callback':
				if(is_callable($key)) {
					$value = call_user_func($key, $row, $this->getServiceLocator()->getServiceLocator());
				}
			break;
			
			// Obter dados utilizando uma funcao, podendo ser definidos parametros em $args['params']
			case 'function':
				if(is_callable($key)) {
					if(isset($args['params'])) {
						$value = call_user_func_array($key, $args['params']);
					} else {
						$value = call_user_func($key);
					}
				}
			break;								
		}
		
		// Utiliza de recursividade para permitir uma estrutura encadeada
		if(isset($args['children']))
			$value = $this->getCellValue($args['children'], $value);
			
		return $value;
	}
	
	/**
	 * Funcao para ordenacao das colunas utilizada com uasort
	 * 
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	public function sortColumns($a, $b) {
		if(isset($a['order']) && isset($a['order'])) {
			if($a['order'] == $b['order'])
				return 0;
			return $a['order'] > $b['order'] ? 1 : -1;
		} elseif(isset($a)) {
			return 1;
		} elseif(isset($b)) {
			return -1;			
		} else {
			return 0;
		}
	}
	
	/**
	 * Gera o attributo HTML id das tags <tr> da tabela
	 * 
	 * @param object $row
	 * @return string
	 */
	public function getRowId($row) {
		return 'id_' . $row->id;
	}
	
	/**
	 * Renderiza o form das Bulk Actions
	 * 
	 * @param array $bulkActions Lista de Ações
	 * @return string
	 */
	protected function renderBulkActions($bulkActions) {
		$return = '<script>' . PHP_EOL
				. '$(document).ready(function() {' . PHP_EOL
				. '	document.getElementById("bulk-actions-submit").onclick = function() {' . PHP_EOL
				. '		url = document.getElementById("bulk-action").selectedOptions[0].value;' . PHP_EOL
				. '		form = document.getElementById("bulk-actions-form");' . PHP_EOL
				. '		form.action = url;' . PHP_EOL
				. '		cbs = document.getElementsByClassName("cb-row");' . PHP_EOL
				. '		ids = "";' . PHP_EOL				
				. '		for(i in cbs) {' . PHP_EOL
				. '			if(cbs[i].checked)' . PHP_EOL
				. '				ids += cbs[i].name.substr(3) + ";";' . PHP_EOL
				. '		} console.log(ids);' . PHP_EOL
				. '		document.getElementById("bulk-action-ids").value = ids;' . PHP_EOL
				. '		form.submit();' . PHP_EOL
				. '	}' . PHP_EOL
				. '});' . PHP_EOL
				. '</script>' . PHP_EOL
				. '<form id="bulk-actions-form" method="post" action="">' . PHP_EOL
				. "\t<input id=\"bulk-action-ids\" name=\"bulk-action-ids\" type=\"hidden\" value=\"\">" . PHP_EOL
				. "\t<div class=\"block-bulk-actions control-group\">" . PHP_EOL
				. "\t\t<label class=\"control-label\">Ações em massa:</label>" . PHP_EOL
				. "\t\t<div class=\"controls\">" . PHP_EOL
				. "\t\t\t<select id=\"bulk-action\">" . PHP_EOL;

	   foreach ($bulkActions as $action) {
		   $return .= "\t\t<option class=\"{$action['action']}\" value=\"{$action['url']}\">{$action['label']}</option>";
	   }

	   $return .= "\t\t\t</select>" . PHP_EOL
				. "\t\t\t<a id=\"bulk-actions-submit\" class=\"btn\">Aplicar</a>" . PHP_EOL
				. "\t\t</div>" . PHP_EOL
				. "\t</div>" . PHP_EOL;
				//. '</form>' . PHP_EOL;
	   
	   return $return;
	}
	
	/**
	 * Constroi o datagrid com as definicoes das colunas especificadas em $columns
	 *  as linhas com os valores obtidos apartir de $rows
	 * 
	 * @param array $columns		  Definicoes para construir as colunas
	 * @param array $rows			  Dados para preencher o datagrid
	 * @param array|null $bulkActions Ações em massa 
	 * @return string Datagrid completo
	 */
	public function __invoke($columns, $rows = array(), $bulkActions = null)
    {
		// Ordena  as colunas a partir do valor contido na key 'order' 
		// do array de configuracoes das colunas
		uasort($columns, array($this, 'sortColumns'));
		
		$return = '';
		
		// Exibe o select com as ações em massa
		if(is_array($bulkActions) && count($bulkActions)) {
			$return .= $this->renderBulkActions($bulkActions);
		}
		
		$return .= '<table class="table table-striped">' . PHP_EOL
				 . "\t<thead>" . PHP_EOL
				 . "\t\t<tr>" . PHP_EOL;
		
		// Monta os headers
		$cb = false;
		foreach ($columns as $key => $value) {
			if($value['value']['type'] == 'cb') {
				$cb = true;
				if(!isset($value['header']))
					$value['header'] = '<input id="cb-all" type="checkbox">';
			}
			
			$style = !empty($value['style']) ? ' style="' . $value['style'] . '"' : '';
			$return .= "\t\t\t<th" . $style . ">" . $value['header'] . "</th>" . PHP_EOL;
		}
		
		$return .= "\t\t</tr>" . PHP_EOL
				 . "\t</thead>" . PHP_EOL;
		
		// Table body
		$return .= "\t<tbody>" . PHP_EOL;
		
		if(count($rows)) {
			foreach ($rows as $key => $row) {
				$return .= "\t\t<tr id=\"" . $this->getRowId($row) . "\">" . PHP_EOL;

				foreach ($columns as $key => $value) {
					$return .= "\t\t\t<td data-col-key=\"{$key}\">" . $this->getCellValue($value['value'], $row) . "</td>" . PHP_EOL;
				}

				$return .= "\t\t</tr>" . PHP_EOL;
			}
		}
		
		$return .= "\t</tbody>" . PHP_EOL
				 . "</table>" . PHP_EOL;
		
		// Fecha o form de Ações em massa
		if(is_array($bulkActions) && count($bulkActions)) {
			$return .= '</form>' . PHP_EOL;
		}
		
		// Adiciona script para selecionar todos os checkboxes do datagrid ao clicar no cb do header
		if($cb) {
			$return .= '<script>' . PHP_EOL
					 . '$(document).ready(function() {' . PHP_EOL
					 . '	document.getElementById("cb-all").onchange = function() {' . PHP_EOL
					 . '		cbs = document.getElementsByClassName("cb-row");' . PHP_EOL				
					 . '		ids = "";' . PHP_EOL				
					 . '		for(i in cbs) {' . PHP_EOL
					 . '			cbs[i].checked = this.checked;' . PHP_EOL
					 . '		}' . PHP_EOL
					 . '	}' . PHP_EOL
					 . '});' . PHP_EOL
					 . '</script>' . PHP_EOL;			
		}
					
		return $return;
    }
}