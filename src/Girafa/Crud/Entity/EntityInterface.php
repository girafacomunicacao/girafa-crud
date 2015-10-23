<?php
namespace Girafa\Crud\Entity;

interface EntityInterface {

    /**
     * Retrieve the entity ID
     *
     * @return string or integer
     */
    public function getId();
    
    /**
     * Retrieve the entity name
     *
     * @return string
     */
    public function getName();

}