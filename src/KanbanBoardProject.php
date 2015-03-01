<?php namespace WeAreNotMachines\Kanban;



class KanbanBoardProject implements DataTransformable {

	private $client;
	private $id;
	private $name;
	private $hidden = false;
	private $stickers = array();

	public function __construct($id=null, $name=null, $hidden=false) {
		$this->id = $id;
		$this->name = $name;
		$this->hidden = $hidden;
	}

	public static function fromArray(Array $projectData) {
		$project = new KanbanBoardProject(
			$id = (isset($projectData['id']) ? $projectData['id'] : null),
			$name = (isset($projectData['name']) ? $projectData['name'] : null),
			$hidden = (isset($projectData['hidden']) ? $projectData['hidden'] : null)
		);
		if (!empty($projectData['clientID'])) {
			$project->setClient(new KanbanBoardClient($projectData['clientID'], (empty($projectData['clientName']) ? null : $projectData['clientName'])));
		} else if (!empty($projectData['cid'])) {
			$project->setClient(new KanbanBoardClient($projectData['cid']));
		}
		if (!empty($projectData['stickers'])) {
			foreach ($projectData['stickers'] AS $sticker) {
				$project->addSticker(KanbanBoardSticker::fromArray($sticker));
			}
		}
		return $project;
	}

	/**
	 * Mutator for client
	 * @param KanbanBoardClient $client The client to associate
	 */
	public function setClient(KanbanBoardClient $client) {
		$this->client = $client;
		if (!$client->hasProject($this->id)) {
			$client->addProject($this);
		}
		return $this;
	}

	public function getClient() {
		return $this->client;
	}

	/**
	 * Accessor for client name
	 * @return string The name of this project's client
	 */
	public function getClientName() {
		return empty($this->client) ? null : $this->client->getName();
	}

	/**
	 * Accessor for client id
	 * @return int the client's id
	 */
	public function getClientID() {
		return empty($this->client) ? null : $this->client->getID();
	}

	/**
	 * Accessor for name
	 * @return string The name of the project
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Accessor for ID
	 * @return int Project id
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * Sets project hidden to false
	 */
	public function show() {
		$this->hidden = false;
		return $this;
	}

	/**
	 * Sets project hidden to true
	 */
	public function hide() {
		$this->hidden = true;
		return $this;
	}

	/**
	 * Tests whether the project is hidden or shown
	 * @return boolean whether or not the project is hidden
	 */
	public function isHidden() {
		return $this->hidden;
	}

	public function toArray() {
		$stickers = array();
		foreach ($this->stickers AS $sticker) {
			$stickers[] = $sticker->toArray();
		}
		return [
			"id"=>$this->id,
			"name"=>$this->name,
			"clientID"=>$this->getClientID(),
			"clientName"=>$this->getClientName(),
			"hidden"=>(boolean)$this->hidden,
			"stickers" => $stickers
		];
	}

	public function toJSON() {
		return json_encode($this->toArray(), JSON_PRETTY_PRINT);
	}

	public function hasSticker(KanbanBoardSticker $sticker) {
		return in_array($sticker, $this->stickers);
	}

	public function addSticker(KanbanBoardSticker $sticker) {
		if (!in_array($sticker, $this->stickers)) {
			$this->stickers[] = $sticker;
		}	
		return $this;
	}

	public function removeSticker(KanbanBoardSticker $sticker) {
		if ($this->hasSticker($sticker)) {
			unset($this->stickers[array_search($sticker, $this->stickers)]);
		}
		return $this;
	}

}