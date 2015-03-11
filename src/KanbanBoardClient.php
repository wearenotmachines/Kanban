<?php namespace WeAreNotMachines\Kanban;



class KanbanBoardClient implements DataTransformable {

	private $projects = array();
	private $id;
	private $name;

	public function __construct($id=null, $name=null, $projects=array()) {
		$this->id = $id;
		$this->name = $name;
		$this->projects = $projects;
	}

	public static function fromArray($clientData) {
		return new KanbanBoardClient(
			$id = (isset($clientData['id']) ? $clientData['id'] : null),
			$name = (isset($clientData['name']) ? $clientData['name'] : null)
		);
	}

	/**
	 * Adds a project for this client
	 * @param KanbanBoardProject $project the proecjt to add
	 */
	public function addProject(KanbanBoardProject $project) {
		if (!in_array($project, $this->projects, true)) {
			$this->projects[$project->getID()] = $project;
			if (empty($project->getClient())) {
				$project->setClient($this);
			}	
		}
		return $this;
	}

	public function hasProject($byID) {
		foreach ($this->projects AS $project) {
			if ($byID==$project->getID()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Sets the complete set of projects for this client
	 * @param Array $projects an rray of KanbanProjects
	 */
	public function setProjects(Array $projects) {
		array_map(function($project) {
			$this->addProject($project);
		}, $projects);
		return $this;
	}

	/**
	 * An accessor for a specific project
	 * @param  int|string $projectID An identifier - either an id or a project name
	 * @return KanbanProject            A matching KanbanProject
	 * @throws  InvalidArgumentException If Project does not exist
	 */
	public function getProject($projectID) {
		if (array_key_exists($projectID, $this->projects)) {
			return $this->projects[$projectID];
		} else {
			foreach ($this->projects AS $project) {
				if ($project->getName()==$projectID) {
					return $project;
				}
			}
		}
		throw new \InvalidArgumentException("There is no project with id or name $projectID for client: ".$this->name);
	}

	/**
	 * Accessor for name
	 * @return string The client name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Accessor for ID
	 * @return int client id
	 */
	public function getID() {
		return $this->id;
	}

	public function getProjectsAsArrays() {
		$projectsArray = [];
		foreach ($this->projects AS $project) {
			$projectsArray[] = $project->toArray();
		}
		return $projectsArray;
	}

	public function toArray() {
		return [
			"id"=>$this->id,
			"name"=>$this->name,
			"projects"=>$this->getProjectsAsArrays()
		];
	}

	public function toJSON() {
		return json_encode($this->toArray(), JSON_PRETTY_PRINT);
	}

}