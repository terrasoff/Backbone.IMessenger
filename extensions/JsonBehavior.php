<?php
class JsonBehavior extends CBehavior{
	
	private $owner;
	private $relations;
	
	public function toJSON(){
		$this->owner = $this->getOwner();
		
		if (is_subclass_of($this->owner,'CActiveRecord')){
			
			$attributes = $this->owner->getAttributes();
			$this->relations = $this->getRelated();
            $jsonDataSource = array_merge($attributes,$this->relations);

			return $jsonDataSource;
		}
		return false;
	}

	private function getRelated()
	{	
		$related = array();
		
		$obj = null;
		$md=$this->owner->getMetaData();
		
		foreach($md->relations as $name=>$relation){
			
			$obj = $this->owner->getRelated($name);
			
			$related[$name] = $obj instanceof CActiveRecord
                ? ($obj->enableBehavior('jsonBehavior'))
                    ? $obj->toJSON()
                    : $obj->getAttributes()
            : $obj;
		}
	    
	    return $related;
	}
}