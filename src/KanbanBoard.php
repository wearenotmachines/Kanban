<?php namespace WeAreNotMachines\Kanban;

class KanbanBoard {

	private $datasource;
	private $projects = [];
	private $clients = [];
	private $users = [];
	private $activeProjects = [];
	private $stickers;

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
			$this->activeProjects = $data['activeProjects'];
		}

		if (!empty($data['stickers'])) {
			$this->stickers = $data['stickers'];
		}
		return $this;
	}

	public function setUsers(Array $userData) {
		array_map(function($user) {
			if ($user['active'] && !$user['inactive']) {
				$this->addUser(KanbanBoardUser::fromArray($user));
			}
		}, $userData);
		return $this;
	}

	public function addUser(KanbanBoardUser $user) {
		foreach ($this->users AS $u) {
			if ($u->getUID() && $u->getUID()==$user->getUID()) {
				return $this;
			}
		}
		$this->users[] = $user;
		return $this;
	}

	public function setClients(Array $clients) {
		array_map(function($client) {
			$this->addClient(KanbanBoardClient::fromArray($client));
		}, $clients);	
		return $this;
	}

	public function addClient(KanbanBoardClient $client) {
		foreach ($this->clients AS $c) {
			if ($c->getID()==$client->getID()) {
				return $this;
			}
		}
		$this->clients[] = $client;
		return $this;
	}

	public function addProject(KanbanBoardProject $project) {
		foreach ($this->projects AS $p) {
			if ($p->getID()==$project->getID()) {
				return $this;
			}
		}
		$this->projects[] = $project;
		if ($project->getClientID() && $this->hasClient($project->getClientID())) {
			$project->setClient($this->getClient($project->getClientID()));
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

	public function getActiveProjectIDs() {
		return $this->activeProjects;
	}

	public function getActiveProjects() {
		$projects = array();
		foreach ($this->activeProjects AS $pid) {
			$projects[] = $this->getProject($pid);
		}
		return $projects;
	}

	public function makeProjectActive($project) {
		if ($project instanceof KanbanBoardProject) {
			$project = $project->getID();
		} else {
			$project = $this->getProject($project);
		}
		if (!$this->hasProject($project->getID())) {
			throw new InvalidArgumentException("This board has no project identified by $project");
		}
		$this->activeProjects[] = $project->getID();
		$this->activeProjects = array_unique($this->activeProjects);
		return $this;
	}

	public function makeProjectInactive($project) {
		if ($project instanceof KanbanBoardProject) {
			$project = $project->getID();
		} else {
			$project = $this->getProject($project);
		}
		if (!$this->hasProject($project->getID())) {
			throw new InvalidArgumentException("This board has no project identified by $project");
		}
		if (in_array($project->getID(), $this->activeProjects)) {
			unset($this->activeProjects[array_search($project->getID(), $this->activeProjects)]);
		}
		return $this;
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
		"activeProjects"=>$this->activeProjects,
		"stickers"=>$this->stickers
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
		throw new \InvalidArgumentException("No user identified by $identifier exists on this board");
	}

	public function save() {
		return file_put_contents($this->datasource, $this->toJSON());
	}

	public function addStickerToProject(KanbanBoardSticker $sticker, $project) {
		if ($project instanceof KanbanBoardProject) {
			$projectID = $project->getID();
		} else {
			$projectID = $project;
			$project = $this->getProject($project);
		}
		if (!$this->hasProject($projectID)) {
			throw new InvalidArgumentException("This board has no project identified by $project");
		}
		$project->addSticker($sticker);
		return $this;
	}

	public function removeStickerFromProject(KanbanBoardSticker $sticker, $project) {
		if ($project instanceof KanbanBoardProject) {
			$projectID = $project->getID();
		} else {
			$projectID = $project;
			$project = $this->getProject($project);
		}
		if (!$this->hasProject($projectID)) {
			throw new InvalidArgumentException("This board has no project identified by $project");
		}
		$project->removeSticker($sticker);
		return $this;
	}

	public function updateProject($projectID, KanbanProject $with) {
		if (!$this->hasProject($projectID)) {
			throw InvalidArgumentException("This board has no project identified by $projectID");
		}
		$this->getProject($projectID)->update($with);
		return $this;
	}

	public function isActiveProject($project) {
		if ($project instanceof KanbanBoardProject) {
			$projectID = $project->getID();
		} else {
			$projectID = $project;
		}
		return in_array($projectID, $this->activeProjects);
	}

	public function removeUserFromProjectsExcept($userID, $exceptProjectID=null) {
		foreach ($this->projects AS $project) {
			if ($project->getID()==$exceptProjectID) continue;
			$project->removeUser($userID);
		}
	}
}