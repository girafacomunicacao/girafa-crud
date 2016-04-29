<?php
namespace Girafa\Crud\Service;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Girafa\Crud\Service\CrudServiceInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Composite;

class CrudService implements CrudServiceInterface, ServiceLocatorAwareInterface {
    
	/**
	 * @var \Doctrine\ORM\EntityRepository
	 */
    protected $I_entityRepository;
	
	/**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $I_entityManager;	
	
	/**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceLocator;	
	
	/**
	 * @var \Zend\Stdlib\Hydrator\AbstractHydrator
	 */
    protected $hydrator;	
        
    /**
	 * Constructor
	 * 
	 * @param \Doctrine\Common\Persistence\ObjectManager $I_entityManager
	 * @param \Doctrine\ORM\EntityRepository $I_entityRepository
	 * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
	 * @param \Zend\Stdlib\Hydrator\AbstractHydrator $hydrator
	 * @throws \InvalidArgumentException
	 */
    public function __construct(ObjectManager $I_entityManager, EntityRepository $I_entityRepository, ServiceLocatorInterface $serviceLocator, $hydrator = null) {
        $this->I_entityRepository = $I_entityRepository;
        $this->I_entityManager = $I_entityManager;		        
		if($hydrator != null) {
			if(!$hydrator instanceof \Zend\Stdlib\Hydrator\AbstractHydrator) {
				throw new \InvalidArgumentException(
					sprintf(
						'Hydrator must inherit from \Zend\Stdlib\Hydrator\AbstractHydrator, %s given',
						get_class($hydrator)
					)
				);
			}
			
			$this->hydrator = $hydrator;
		} else {
			$this->hydrator = new \DoctrineModule\Stdlib\Hydrator\DoctrineObject($this->I_entityManager,false);
		}	
    }

	public function newEntity() {
		$entityName = $this->getEntityName();
		return new $entityName();
	}
	
	public function getEntityName() {
		return $this->getEntityRepository()->getClassName();
	}
	
    public function getEntity($i_id){
        $I_entity = $this->getEntityRepository()->find($i_id);

        if ($I_entity === null ){
            throw new \Exception('Entity not found');    //@todo throw custom exception type
        }

        return $I_entity;
    }
    
	public function getEntityManager() {
		return $this->I_entityManager;
	}
	
	public function getEntityRepository() {
		return $this->I_entityRepository;
	}
	
    public function getEntityDetailAsArray($I_entity){
        return $this->hydrator->extract($I_entity);
    }

    public function getAllEntities(){
        return $this->getEntityRepository()->findAll();
    }
    
	/**
	 * Cria um QueryBuilder baseado nos criterios de $data para filtro 
	 * e de ordenacao por $order
	 * 
	 * @param array|null $data Definicoes para criar os filtros
	 * @param string|array|null $order Difinicoes para ordenacao
	 * @return QueryBuilder
	 */
	public function createQueryBuilder($data = null, $order = null) {
		$qb = $this->getEntityRepository()->createQueryBuilder('e');
		
		$this->createQueryWhere($data, $qb);
		
		if($order != null) {
			if(is_string($order)) {
				$qb->orderBy('e.' . $order);
			} elseif(is_array($order)) {
				if(!is_array($order[0])) {
					$order = array($order);
				}
				foreach ($order as $singleOrder) {
					if(isset($singleOrder['field'])) {					
						if(isset($singleOrder['order'])) {
							$qb->addOrderBy('e.' . $singleOrder['field'], $singleOrder['order']);
						} else {
							$qb->addOrderBy('e.' . $singleOrder['field']);
						}
					}
				}
			}
		}
		
		return $qb;
	}
	
	/**
	 * Adiciona a clausula WHERE a um QueryBuilder baseado em um array de parametros
	 *
	 * @param array $data Array com parametros para montar os criterios
	 * @param \Doctrine\ORM\QueryBuilder $qb Query builder passado por referencia
	 * @return void
	 */
	protected function createQueryWhere($data, QueryBuilder &$qb) {
		$expr = $qb->expr();
		$andX = $expr->andX();				

		if(is_array($data) && count($data)) {
			foreach ($data as $key => $value) {															
				if($value != null) {
					$andX = $this->defaultWhereFilter($key, $value, $expr, $andX);
				}
			}
		}

		if($andX->count()) {				
			$qb->where($andX);			
		}
	}
	
	/**
	 * Comportamento padrao para adiciona criterios a uma clausula WHERE
	 * dependendo do tipo do campo
	 * 
	 * @param string $field
	 * @param mixed $value
	 * @param \Doctrine\ORM\Query\Expr $expr
	 * @param \Doctrine\ORM\Query\Expr\Composite $composite
	 * @return \Doctrine\ORM\Query\Expr\Composite
	 */
	protected function defaultWhereFilter($field, $value, Expr $expr, Composite $composite) {
		if(is_numeric($value)) {
			$value = (int) $value;
		}
		if(is_string($value)) {
			$value = str_replace(' ', '%', $value);
			$composite->add($expr->like('e.' . $field, $expr->literal('%' . $value . '%')));
		} elseif(is_array($value)) {
			$composite->add($expr->in('e.' . $field, $value));
		} else {
			$composite->add($expr->eq('e.' . $field, $value));
		}
		
		return $composite;
	}
	
    public function upsertEntityFromArray(array $am_formData){

        $entity = null;
		$isUpdate = false;
		
        foreach ($am_formData as $key => $value) {
        	// Remove caracteres de controle das strings
        	if(is_string($value)) {
        		$am_formData[$key] = preg_replace('/[^\P{C}\n\t\r]+/u', '', $value);
        	}

        	// Define se é Update 
        	if($key == 'id' && is_numeric($value) && $value > 0){
				$isUpdate = true;			
	            $entity = $this->getEntity($value);
	        }
        }

        if(!$isUpdate)
            $entity = $this->newEntity();

        $this->hydrator->hydrate($am_formData,$entity);
        //@fixme sostituire con gli hydrator di doctrine
        //$entity->exchangeArray($am_formData);
        
		$entity = $this->saveEntity($entity);
    
        return $entity;
    }
    
    public function saveEntity($I_entity){
        $this->I_entityManager->persist($I_entity);
        $this->I_entityManager->flush();
        return $I_entity;
    }
    
    public function deleteEntity( $I_entity ) {
        $this->I_entityManager->remove($I_entity);
        $this->I_entityManager->flush();
    }
    
    public function find($i_id){
        return $this->getEntityRepository()->find($i_id);
    }
    
	public function getCollectionToTokenInput($collection, $key = 'id', $value = 'nome', $toJson = true) {
		$json = array();
		if(count($collection)) {
			foreach ($collection as $entity) {								
				$json[] = $this->getEntityToTokenInput($entity, $key, $value, false);
			}
		}
		if(!$toJson)
			return $json;		
		
		return \Zend\Json\Json::encode($json);
	}
	
	public function getEntityToTokenInput($I_entity, $key = 'id', $value = 'nome', $toJson = false) {
		$obj = new \stdClass();
		$obj->id = $I_entity->$key;
		$obj->name = $I_entity->$value;		
		if(!$toJson)
			return $obj;
		return \Zend\Json\Json::encode($obj);
	}
	
	public function getEntityToComboAjax($I_entity, $key = 'id', $value = 'nome') {
		return array(
			'id'   => $I_entity->$key,
			'name' => $I_entity->$value
		);
	}
	
	public function getArrayToComboAjax($entities = null, $key = 'id', $value = 'nome') {
        $return = array();
		if($entities == null)
			$entities = $this->getAllEntities();
		if(is_array($entities) && count($entities)) {
			foreach ($entities as $entity) {
				$return[] = $this->getEntityToComboAjax($entity, $key, $value);
			}
		}
        return $return;
    }
	
    /**
     * Return an array $key => $value of $entities 
	 *  to use in a combo box (select) form element
	 * 
	 * @param ArrayCollection $entities Collection of entities to generate Array
	 * @param string $key   The name of the propertie of Entity to set as the KEY of the array
	 * @param string $value The name of the propertie of Entity to set as the VALUE of the array
     * @return array
     */
    public function getArrayToCombo($entities = null, $key = 'id', $value = 'nome') {
        $lista = array();
		if($entities == null)
			$entities = $this->getAllEntities();		
        foreach ($entities as $entity) {
            $lista[$entity->$key] = $entity->$value;
        }
        return $lista;
    }
	
	/**
	 * Set serviceLocatorInterface instance
     * @param ServiceLocatorInterface $serviceManager
	 * @return CrudService
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    /**
     * Retrieve serviceLocatorInterface instance
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

	/**
	 * Retrieve a service, calling with method get() of ServiceLocator injected
	 * 
	 * @return mixed
	 */
    public function getService($serviceName) 
    {
    	return $this->getServiceLocator()->get($serviceName);
    }
}
