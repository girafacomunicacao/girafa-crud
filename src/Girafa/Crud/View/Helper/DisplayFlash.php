<?php
namespace Girafa\Crud\View\Helper;

use Zend\View\Helper\AbstractHelper;

class DisplayFlash extends AbstractHelper {

	public function __invoke($flashMessages) {
            if(count($flashMessages)){
                $out  = '<div class="alert">';
                $out .= '<button class="close" data-dismiss="alert"></button>';
                foreach ($flashMessages as $msg){
                    $out .= '<h4>'.$msg.'</h4>';
                }
                $out .= '</div>';
                return $out;
            }
            return null;
	}

}