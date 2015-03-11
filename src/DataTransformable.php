<?php namespace WeAreNotMachines\Kanban;

interface DataTransformable {
	
	public function toArray();
	public function toJSON();
	
}