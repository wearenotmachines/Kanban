<?php namespace WeAreNotMachines\Kanban;

class KanbanBoardUser implements DataTransformable {

	private $id;
	private $name;
	private $email;
	private $apiToken;

	public function __construct($id=null, $name=null, $email=null, $apiToken=null) {
		$this->id = $id;
		$this->name = $name;
		$this->email = $email;
		$this->apiToken = $apiToken;
	}

	/**
	 * Static generator function
	 * @param  Array  $userData A dictionary of user data to construct the new user instance
	 * @return KanbanBoardUser           A KanbanBoardUser
	 */
	public static function fromArray(Array $userData) {
		return new KanbanBoardUser(
			$id = (isset($userData['id']) ? $userData['id'] : null),
			$name = (isset($userData['name']) ? $userData['name'] : null),
			$email = (isset($userData['email']) ? $userData['email'] : null),
			$apiToken = (isset($userData['apiToken']) ? $userData['apiToken'] : null)
		);
	}

	/**
	 * Accessor for ID
	 * @return int An id
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * An accessor for name
	 * @return string user name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Accessor for email
	 * @return string an email address
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * Accessor for apiToken
	 * @return string an api token string
	 */
	public function getApiToken() {
		return $this->apiToken;
	}

	public function toArray() {
		return [
			"id"=>$this->id,
			"name"=>$this->name,
			"email"=>$this->email,
			"apiToken"=>$this->apiToken
		];
	}

	public function toJSON() {
		return json_encode($this->toArray(), JSON_PRETTY_PRINT);
	}

}