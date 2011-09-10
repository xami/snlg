<?php
/**
 * Created by JetBrains PhpStorm.
 * User: xami
 * Date: 11-9-10
 * Time: 下午7:40
 * To change this template use File | Settings | File Templates.
 */
 
class In2Wp extends CApplicationComponent
{
    protected $_config=array();

    public function run($config=array()) {
        if(is_array($config) && count($config) > 0) {
            foreach($config as $key => $value) {
                $this->setOption($key, $value);
            }
        }
    }

    public function setOption($key, $value) {
		$this->_config[$key] = $value;
		return $this;
	}

    
    
}