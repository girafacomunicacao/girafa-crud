<?php
namespace Girafa\Crud\Service;

use Girafa\Crud\Service\CrudService;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Crud Service for entities that have created/update date fields
 * 
 * @author Girafa Comunicacao
 * @package Girafa\Crud
 * @category Service
 */
class TimeStampedCrud extends CrudService {
		
	/**
	 * Created Date property name
	 * @var string
	 */
	protected $createdProperty;
	
	/**
	 * Updated Date property name
	 * @var string
	 */
	protected $updatedProperty;
	
	/**
	 * PHP Date format for created/updated dates
	 * @var string
	 */
	protected $dateFormat;
	
	public function __construct(ObjectManager $I_entityManager, EntityRepository $I_entityRepository, ServiceLocatorInterface $serviceLocator, $hydrator = null) {
		parent::__construct($I_entityManager, $I_entityRepository, $serviceLocator, $hydrator);
		
		$this->createdProperty = 'dataCadastro';		
		$this->updatedProperty = 'dataAlteracao';
	}
	
	public function upsertEntityFromArray(array $formData) {
		$isUpdate = false;
        $entity = null;
		
        if(isset($formData['id']) && 
		  is_numeric($formData['id']) && 
          $formData['id'] > 0) {
			$isUpdate = true;
			
            $entity = $this->getEntity($formData['id']);
        }

        if(!$isUpdate)
            $entity = $this->newEntity();
		
		// Populating entity with form data
		$this->hydrator->hydrate($formData,$entity);
				
		$propertyType = $isUpdate ? 'updated' : 'created';
		$propertyType .= 'Property';				
		
		// Set the created or updated date to 'now' based on dateFormat
		if(!empty($this->$propertyType)) {
			$property = $this->$propertyType;
			if(property_exists($entity, $property)) {							
				$entity->$property = new \DateTime();		
			}
		}	        
        
		$conn = $this->I_entityManager->getConnection();
		$attempt = 1;
		while($attempt <= 3) {
			try {
	    		$conn->beginTransaction();    		
	    		$this->I_entityManager->persist($entity);
       			$this->I_entityManager->flush();
	        	$conn->commit();        	
	    	} catch(\Exception $e) {
	    		$conn->rollBack();
	    		$attempt++;
	    		if($attempt > 3) {
	    			throw new \Exception($e->getMessage(), $e->getCode()); 
	    		}		    		   		
	    	}
		}		
    
        return $entity;
    }    
	
}