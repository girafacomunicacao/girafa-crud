<?php
namespace Girafa\Crud\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;
use Zend\Json\Json;

class CrudIndexController extends AbstractActionController
{
    protected $s_entityName;
    
    protected $I_service;
    protected $I_form;
	protected $I_filterForm;
    
    // Variables used to customize index page
    protected $s_indexTitle;
    protected $s_indexTemplate;
    
    // Variables used to customize detail entity page
    protected $s_detailTitle;
    protected $s_detailTemplate;

    // Variables used to customize create new entity page
    protected $s_newTitle;
    protected $s_newTemplate;
	protected $s_newRoute;
    
    // Variables used to customize edit entity page
    protected $s_editTitle;
    protected $s_editTemplate;
	protected $showCreated;
	protected $createdMethodGet;
	protected $timestampedDateFormat;
    
    // Variables used to customize process of insert/update form
    protected $s_processErrorTitle;
    protected $s_processErrorTemplate;
    protected $s_processRouteRedirect;
    
    // Variables used to customize delete action
    protected $s_deleteRouteRedirect;
    
    // Variables used to display flash messages to user
    protected $s_flashMessageNew;
    protected $s_flashMessageUpdate;
    protected $s_flashMessageDelete;
    
	protected $paginate;
	protected $itensPerPage;
	protected $datagridColumns;
	protected $bulkActions;
	protected $orderBy;
	
    protected $as_config;
    protected $s_namespace;
    protected $s_module;
    
    public function __construct($s_entityName, $I_service, $I_form, $I_filterForm = null, $datagridColumns = array(), $as_config, $bulkActions = array()) {		
        $this->s_entityName = $s_entityName;
        $this->I_service = $I_service;
        $this->I_form = $I_form;
		$this->I_filterForm = $I_filterForm;
				
        $this->as_config = $as_config;
        
        $as_namespace = explode('\\',get_class($this));
        $this->s_namespace = $as_namespace[0];
        $this->s_module = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $this->s_namespace));
        
        // IndexAction
        $this->s_indexTitle = $this->getDefaultValue('s_indexTitle',$this->s_namespace);
        $this->s_indexTemplate = $this->getDefaultValue('s_indexTemplate',$this->s_namespace);        
		// Pagination
		$this->paginate = (bool) $this->getDefaultValue('paginate', $this->s_namespace);		
		$this->itensPerPage = (int) $this->getDefaultValue('itensPerPage', $this->s_namespace);		
		// Datagrid
		$datagridColumnsDefaults = (array) $this->getDefaultValue('datagridColumns', $this->s_namespace);	
		$this->datagridColumns = array_replace($datagridColumnsDefaults, $datagridColumns);
		// Bulk actions
		$bulkActionsDefaults = (array) $this->getDefaultValue('bulkActions', $this->s_namespace);	
		$this->bulkActions = array_replace($bulkActionsDefaults, $bulkActions);
		// Ordenation
		$this->orderBy = $this->getDefaultValue('orderBy', $this->s_namespace);		
		
        // NewAction
        $this->s_newTitle = $this->getDefaultValue('s_newTitle',$this->s_namespace);
        $this->s_newTemplate = $this->getDefaultValue('s_newTemplate',$this->s_namespace);
		$this->s_newRoute = $this->getDefaultValue('s_newRoute',$this->s_namespace);
        
        // EditAction
        $this->s_editTitle = $this->getDefaultValue('s_editTitle',$this->s_namespace);
        $this->s_editTemplate = $this->getDefaultValue('s_editTemplate',$this->s_namespace);
        $this->showCreated = $this->getDefaultValue('showCreated',$this->s_namespace);
        $this->createdMethodGet = $this->getDefaultValue('createdMethodGet',$this->s_namespace);
        $this->timestampedDateFormat = $this->getDefaultValue('timestampedDateFormat',$this->s_namespace);
		
        // DetailAction
        $this->s_detailTitle = $this->getDefaultValue('s_detailTitle',$this->s_namespace);
        $this->s_detailTemplate = $this->getDefaultValue('s_detailTemplate',$this->s_namespace);
        
        // Flash messages
        $this->s_flashMessageNew = $this->getDefaultValue('s_flashMessageNew',$this->s_namespace);
        $this->s_flashMessageUpdate = $this->getDefaultValue('s_flashMessageUpdate',$this->s_namespace);
        $this->s_flashMessageDelete = $this->getDefaultValue('s_flashMessageDelete',$this->s_namespace);
        $this->s_flashMessageDeleteError = $this->getDefaultValue('s_flashMessageDeleteError',$this->s_namespace);
        
		// Ajax messages
		$this->s_ajaxMessageNew = $this->getDefaultValue('s_ajaxMessageNew',$this->s_namespace);
        $this->s_ajaxMessageUpdate = $this->getDefaultValue('s_ajaxMessageUpdate',$this->s_namespace);
        $this->s_ajaxMessageDelete = $this->getDefaultValue('s_ajaxMessageDelete',$this->s_namespace);
		$this->s_ajaxMessagePersistError = $this->getDefaultValue('s_ajaxMessagePersistError',$this->s_namespace);
		
		$this->s_messageFormNotValid = $this->getDefaultValue('s_messageFormNotValid',$this->s_namespace);
		$this->s_messageNoResults = $this->getDefaultValue('s_messageNoResults',$this->s_namespace);
		
		
        // DeleteAction
        // @todo Alert on delete true/false
        // @todo Confirm on delete true/false
            // Confirm title
            // Confirm template

        // Process Delete Action
            // Redirect on delete true false
                $this->s_deleteRouteRedirect    = $this->getDefaultValue('s_deleteRouteRedirect',$this->s_namespace);
            // @todo Success page
            // @todo Success template
        
        // Process New, Edit Action
            // Error page
            $this->s_processErrorTitle = $this->getDefaultValue('s_processErrorTitle',$this->s_namespace);
            $this->s_processErrorTemplate  = $this->getDefaultValue('s_processErrorTemplate',$this->s_namespace);
        
            // Success page or redirect route
            // @todo Redirect on success true/false
                $this->s_processRouteRedirect   = $this->getDefaultValue('s_processRouteRedirect',$this->s_namespace);
            // @todo Success title
            // @todo Success template

    }
    
    public function indexAction(){
		if($filters = $this->getFilters())
			$this->I_filterForm->setData($filters);
		
		$qb = $this->I_service->createQueryBuilder($filters, $this->orderBy);		
		
		if($this->paginate)
			$entities = $this->getPaginator($qb);
		else
			$entities = $qb->getQuery()->execute();
		
		$I_view = new ViewModel(array(
            'module' => $this->s_module,
			'controller' => $this->params()->fromRoute('__CONTROLLER__'),
			'newRoute' => $this->s_newRoute,
            'title' => $this->s_indexTitle,			
            'entities' => $entities,
            'messages' => $this->flashMessenger()->setNamespace($this->s_entityName)->getMessages(),
			'filterForm' => $this->I_filterForm,
			'paginate' => $this->paginate,
            'filters' => ($filters ? http_build_query($filters, '', '&') : ''),
			'datagridColumns' => $this->datagridColumns,
			'bulkActions' => $this->bulkActions,
			'messageNoResults' => $this->s_messageNoResults,
        ));
		
        $I_view->setTemplate($this->s_indexTemplate);
		
        return $I_view;
    }
    
    public function newAction(){
		if($this->request->isXmlHttpRequest())
			$this->I_form->setAttribute('class', $this->I_form->getAttribute('class') . ' ' . 'ajax');
        $I_view = new ViewModel(array('form' => $this->I_form, 'title' => $this->s_newTitle));
        $I_view->setTemplate($this->s_newTemplate);
        return $I_view;
    }
    
    public function editAction($I_entity=null){
        if ( null == $I_entity ){
            $I_entity = $this->getEntityFromQuerystring();
        }
                
		if($this->request->isXmlHttpRequest())
			$this->I_form->setAttribute('class', $this->I_form->getAttribute('class') . ' ' . 'ajax');
		
        // bind entity values to form
        $this->I_form->bind($I_entity);
        
        $I_view = new ViewModel(array(
			'form' => $this->I_form, 
			'title' => $this->s_editTitle,			
		));
		
		if($this->showCreated && !empty($this->createdMethodGet) && in_array($this->createdMethodGet, get_class_methods(get_class($I_entity)))) {
			$created = $I_entity->{$this->createdMethodGet}();
			if($created instanceof \DateTime) {
				if($this->timestampedDateFormat) {
					$created = $created->format($this->timestampedDateFormat);
				}
			}
			$I_view->setVariable('created', $created);
		}
		
        $I_view->setTemplate($this->s_editTemplate);
        return $I_view;
    }
    
    public function deleteAction(){
		try {
			$I_entity = $this->getEntityFromQuerystring();		
			$this->I_service->deleteEntity($I_entity);
			$this->flashMessenger()->addSuccessMessage($this->s_flashMessageDelete);
		} catch(\Exception $e) {
			$this->flashMessenger()->addErrorMessage($this->s_flashMessageDeleteError);
		}
		
        return $this->crudRedirect('delete');
    }
    
    public function detailAction(){
        $I_entity = $this->getEntityFromQuerystring();
        $I_view = new ViewModel();
		
		$format = $this->params('format', 'html');
		switch ($format) {
			case 'json':
				$I_view->setTerminal(true);
				
				if($I_entity) {				
					$json = array(
						'type' => 'success',
						'data' => $this->I_service->getEntityDetailAsArray($I_entity)
					);
				} else {
					$json = array(
						'type' => 'error',
						'message' => 'Não foi possível encontrar'
					);
				}
				$response = $this->getResponse();				
				$response->setContent(Json::encode($json));
				
				return $response;
			break;

			case 'html':
			default:
				$I_view->setVariable('entity', $I_entity)
					   ->setVariable('title', $this->s_detailTitle)
					   ->setTemplate($this->s_detailTemplate);
				return $I_view;
			break;
		}
    }
    
    public function processAction($custom_data=null){
		$I_view = new ViewModel();
		$json = array();
		$response = $this->getResponse();
		
		$xhr = $this->request->isXmlHttpRequest();		
		$I_view->setTerminal($xhr);
		
		if($xhr)
			$this->I_form->setAttribute('class', $this->I_form->getAttribute('class') . ' ' . 'ajax');
		
        if ($this->request->isPost()) {
            // get post data
            $as_post = $this->request->getPost()->toArray();
            
            // Add custom data from children classes
            if ($custom_data != null ){
                $as_post = array_replace($as_post,$custom_data);
            }
            // fill form
            $this->I_form->setData($as_post);
            
            // check if form is valid
            if(!$this->I_form->isValid()) {
				return $xhr ? $this->processAjaxFormNotValid() : $this->processFormNotValid();
            }
            
            // @fixme Spostare quanto segue in un metodo a se che possa essere richiamato
            // dalle classi figlie che fanno la validazione per conto proprio in modo
            // da non doverla fare due volte
    			
			try {
				// use service to save data
				$I_entity = $this->I_service->upsertEntityFromArray($as_post);

				$update = $as_post['id'] > 0;
				// AJAX
				if($xhr) {					
					$json['type']	 = 'success';
					$json['message'] = $update ? $this->s_ajaxMessageUpdate : $this->s_ajaxMessageNew;
					$json['data']	 = $this->I_service->getEntityDetailAsArray($I_entity);
					$json['combo']	 = $this->I_service->getEntityToComboAjax($I_entity);
				} else {
					if($update)
						$this->flashMessenger()->setNamespace($this->s_entityName)->addMessage($this->s_flashMessageUpdate);
					else
						$this->flashMessenger()->setNamespace($this->s_entityName)->addMessage($this->s_flashMessageNew);
				}
			} catch(\Exception $e) {
				// AJAX
				if($xhr) {
					$json['type']	 = 'error';
					$json['message'] = sprintf($this->s_ajaxMessagePersistError, $e->getMessage(), $e->getCode(), $e->getTraceAsString());										
					$response->setContent(Json::encode($json));					
					
					return $response;
				} else {					
					$I_view->setVariable('form', $this->I_form);
					$I_view->setVariable('title', $this->s_processErrorTitle);
					$I_view->setVariable('error', sprintf($this->s_messagePersistError, $e->getMessage(), $e->getCode(), $e->getTraceAsString()));
					$I_view->setTemplate($this->s_processErrorTemplate);
					$response->setStatusCode(500);
					
					return $I_view;
				}
			}
            
			// AJAX
			if($xhr)
				return $response->setContent(Json::encode($json));
			
            return $this->crudRedirect('process');
        } else {
			// AJAX
			if($xhr)
				$json = array('type' => 'error', 'message' => 'Requisição inválida');
			return Json::encode($json);
		}
        //$this->getResponse()->setStatusCode(404);
        //return;
		
		return $this->crudRedirect('process');
    }
    
	/**
	 * Utilizada como uma forma padrao para requisitar dados para
	 * popular um combobox com uma requisicao AJAX
	 * 
	 * @return ViewModel
	 */
	public function populateComboAjaxAction() {
		$request = $this->getRequest();
        $response = $this->getResponse();
         
        $json = array();
        if ($request->isPost() && $request->isXmlHttpRequest()) {
			$params = $request->getPost()->toArray();			
			if($params) {
				$qb = $this->I_service->createQueryBuilder($params, $this->orderBy);		
				$entities = $entities = $qb->getQuery()->execute();								
								
				$data = $this->I_service->getArrayToComboAjax($entities);				
				$json = array('type' => 'success', 'registers' => $data);
			} else {
				$json = array('type' => 'error', 'message' => 'Parametro incorreto');
			}
			
            if (!empty($json))
                $response->setContent(Json::encode($json));
        }
         
        return $response;
	}
	
	/**
	 * Process form that not passed in validate process,
	 * reorganize error messages and generate a JSON
	 * 
	 * @param Form $form Form to get messages OPTIONAL If not passed uses the $this->I_form
	 * @return string JSON with error messages
	 */
	protected function processFormNotValid($form = null) {
		if($form == null)
			$form = $this->I_form;
		
		// prepare view
		$I_view = new ViewModel(array('form'  => $form,
									  'title' => $this->s_processErrorTitle));
		$I_view->setTemplate($this->s_processErrorTemplate);
		
		return $I_view;
	}
	
	/**
	 * Process Ajax form that not passed in validate process,
	 * reorganize error messages and generate a JSON
	 * 
	 * @param Form $form Form to get messages OPTIONAL If not passed uses the $this->I_form
	 * @return string JSON with error messages
	 */
	protected function processAjaxFormNotValid($form = null) {
		if($form == null)
			$form = $this->I_form;
		
		$messages = array();
		$errors = $form->getMessages();
		foreach($errors as $key => $value) {			
			if(!empty($value) && $key != 'submit') {
				$error = array(
					'field' => $key,
					'errors' => array()
				);
				foreach($value as $keyError => $valueError) {
					//save error(s) per-element that
					//needed by Javascript
					$error['errors'][] = $valueError;   					
				}
				$messages[] = $error;
			}
		}		
		
		// prepare array to encode for JSON format
		$json = array('type' => 'error', 'message' => $this->s_messageFormNotValid, 'errorFields' => $messages);
		$response = $this->getResponse();
		
		return $response->setContent(Json::encode($json));
	}
    	
    /*
     * Private methods
     */
    protected function getEntityFromQuerystring() {
        $i_id = (int)$this->params('id');
        
        if (empty($i_id) || $i_id <= 0){
            $this->getResponse()->setStatusCode(404);    //@todo there is a better way?
                                                         // Probably triggering Not Found Event SM
                                                         // Zend\Mvc\Application: dispatch.error 
            return;
        }
        $I_entity = $this->I_service->getEntity($i_id);
        return $I_entity;
    }

    protected function getEntity($i_id) {
        $I_entity = $this->I_service->getEntity($i_id);
        return $I_entity;
    }
    
	protected function getPaginator($qb) {
		$ormPaginator = new ORMPaginator($qb);
		$adapter = new DoctrineAdapter($ormPaginator);
		$paginator = new Paginator($adapter);

		$paginator->setDefaultItemCountPerPage($this->itensPerPage);

		if($page = (int) $this->params()->fromRoute('page', 1))			
			$paginator->setCurrentPageNumber($page);
		
		return $paginator;
	}

	protected function getFilters() {
		$filters = null;
		if($this->I_filterForm) {
			$formElements = array_keys($this->I_filterForm->getElements());			
			$params = $this->params()->fromQuery();
			foreach ($params as $key => $value) {
				if(!in_array($key, $formElements)) {
					unset($params[$key]);
				}
			}				
			$filters = $params;			
		}
		return $filters;
	}
	
    protected function crudRedirect($s_action){
        $as_routeParams =  $this->getEvent()->getRouteMatch()->getParams();
        $s_module = $as_routeParams['module'];
		$s_controller = $as_routeParams['__CONTROLLER__'];
        
		switch ($s_action){
            case 'process':
                if ($this->s_processRouteRedirect != 'crud') {
                    return $this->redirect()->toRoute($this->s_processRouteRedirect, array('module' => $s_module, 'controller' => $s_controller));
                }
                else {
                    return $this->redirect()->toRoute($s_module, array('module' => $s_module, 'controller' => $s_controller));
                }
                break;
            case 'delete':
                if ($this->s_deleteRouteRedirect != 'crud') {
                    return $this->redirect()->toRoute($this->s_deleteRouteRedirect, array('module' => $s_module, 'controller' => $s_controller));
                }
                else {
                    return $this->redirect()->toRoute($s_module,array('module' => $s_module, 'controller' => $s_controller));
                }
                break;
            default:
                throw new \Exception('Invalid redirect action');
                
        }
    }
    
    /**
     * 
     * Legge il valore di default dei parametri di configurazione del modulo.
     * I valori possono essere definiti con la chiave 'Crud' e valgono globalmente
     * oppure con la sottochiave __NAMESPACE__ e valgono per le entità di quel namespace
     * Nel module.config.php posso definire
     * 'Crud' => array() // configurazione globale
     * oppure
     * 'Crud' => array( __NAMESPACE__ => array()) // configurazione locale al namespace del modulo
     * oppure
     * definire il valore delle variabili direttamente a livello di controller nel modulo concreto che estende questo
     * dopo aver chiamato il costruttore
     * 
     * @param string $s_variableName
     * @param string $s_namespace
     * @return type
     */
    private function getDefaultValue($s_variableName,$s_namespace){
        if (isset($this->as_config[$s_namespace][$s_variableName]))
            return $this->as_config[$s_namespace][$s_variableName];
        elseif (isset($this->as_config[$s_variableName]))
            return $this->as_config[$s_variableName];
			
		return null;
    }
    
    
}
