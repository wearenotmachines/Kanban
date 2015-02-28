<?php namespace WeAreNotMachines\Kanban;

class KanbanBoard {

	private $datasource;
	private $projects = [];
	private $clients = [];
	private $users = [];
	private $activeProjects = [];

	public function __construct($datasource) {
		$this->datasource = $datasource;
		$this->loadData();
	}

	public function loadData($from=null) {
		if (!empty($from)) {
			$this->datasource = $from;
		}
		$data = file_get_contents($this->datasource);
		if (!$data) {
			throw new \RuntimeException("No data was loaded from ".$this->datasource);
		}
		return $this->parseData($data);
	}

	private function parseData($data) {
		$data = json_decode($data, true);
		if (!$data) {
			throw new \InvalidArgumentException("JSON data could not be decoded: ".json_last_error());
		}
		if (!empty($data['users'])) {
			foreach ($data['users'] AS $user) {
				$this->users[] = KanbanBoardUser::fromArray($user);
			}
		}
		if (!empty($data['clients'])) {
			foreach ($data['clients'] AS $client) {
				$newClient = KanbanBoardClient::fromArray($client);
				$this->clients[$newClient->getID()] = $newClient;
			}
		}
		if (!empty($data['projects'])) {
			foreach ($data['projects'] AS $project) {
				$newProject = KanbanBoardProject::fromArray($project);
				$this->projects[$newProject->getID()] = $newProject;
				if (!empty($newProject->getClient())) {
					if (isset($this->clients[$newProject->getClientID()])) {
						$this->clients[$newProject->getClientID()]->addProject($newProject);
					} else {
						throw new \RuntimeException("No client exists on this KanbanBoard with id ".$newProject->getClientID());
					}
				}	
			}
		}
		if (!empty($data['activeProjects'])) {
			foreach ($data['activeProjects'] AS $project) {
				$activeProject = KanbanBoardProject::fromArray($project);
				if ($this->hasProject($activeProject->getID()) && empty($activeProject->getClient())) { //project has null client - try to reconcile rom last cache lookup
					$refProject = $this->getProject($activeProject->getID());
					$activeProject->setClient($refProject->getClient());
				}
				$this->activeProjects[] = $activeProject;
			}
		}
	}

	public function setClients(Array $clients) {
		array_map(function($client) {
			$this->addClient(KanbanBoardClient::fromArray($client));
		}, $clients);	
		return $this;
	}

	public function addClient(KanbanBoardClient $client) {
		if (!in_array($client, $this->clients, true)) {
			$this->clients[] = $client;
		}
		return $this;
	}

	public function addProject(KanbanBoardProject $project) {
		if (!in_array($project, $this->projects, true)) {
			$this->projects[] = $project;
			if ($project->getClientID() && $this->hasClient($project->getClientID())) {
				$project->setClient($this->getClient($project->getClientID()));
			}
		}
		return $this;
	}

	public function setProjects(Array $projects) {
		array_map(function($project) {
			$this->addProject(KanbanBoardProject::fromArray($project));
		}, $projects);
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

	public function getProject($byID) {
		foreach ($this->projects AS $project) {
			if ($byID==$project->getID()) {
				return $project;
			}
		}
		throw new \InvalidArgumentException("There is no project with identifier $byID on this board");
	}

	public function getUsers() {
		return $this->users;
	}

	public function getClients() {
		return $this->clients;
	}

	public function hasClient($byID) {
		foreach ($this->clients AS $client) {
			if ($byID==$client->getID()) {
				return true;
			}
		}
		return false;
	}

	public function getClient($byID) {
		foreach ($this->clients AS $client) {
			if ($byID==$client->getID()) {
				return $client;
			}
		}
		throw new InvalidArgumentException("This board has no client with id $byID");
	}

	public function getProjects() {
		return $this->projects;
	}

	public function getActiveProjects() {
		return $this->activeProjects;
	}

	public function toArray() {
		return [
			"clients"=>array_reduce($this->clients, function($container, $client) {
				$container[] = $client->toArray();
				return $container;
			}),
			"projects"=>array_reduce($this->projects, function($container, $project) {
				$container[] = $project->toArray();
				return $container;
			}),
			"users"=>array_reduce($this->users, function($container, $user) {
				$container[] = $user->toArray();
				return $container;
			}),
			"activeProjects"=>array_reduce($this->activeProjects, function($container, $project) {
				$container[] = $project->toArray();
				return $container;
			})
		];
	}

	public function toJSON() {
		return json_encode($this->toArray(), JSON_PRETTY_PRINT);
	}

	public function getUserAPIToken($identifier) {
		foreach ($this->users AS $user) {
			if ($identifier==$user->getID() || $identifier==$user->getEmail() || $identifier==$user->getName() || $identifier==$user->getApiToken()) {
				return $user->getAPIToken();
			}
		}
		throw new InvalidArgumentException("No user identified by $identifier exists on this board");
	}

	public function save() {
		return file_put_contents($this->datasource, $this->toJSON());
	}
}