<?php namespace WeAreNotMachines\Kanban;

class KanbanBoardSticker implements DataTransformable {

	private $label;
	private $backgroundColor;
	private $color;
	private $icon;
	private $class;
	private $position = array();

	public function __construct($label=null, $position = array(0,0), $class=null, $backgroundColor=null, $icon=null, $color=null) {
		$this->label = $label;
		$this->class = $class;
		$this->backgroundColor = $backgroundColor;
		$this->icon = $icon;
		$this->color = $color;
		$this->setPosition($position);
	}

	public function getAsElement() {
		return "<div class='sticker".($this->class ? (" ".$this->class) : "")."'>".
					"<a href='#'>".
						($this->icon ? "<span class='icon'><img src='".$this->icon."' /></span>" : "").
						($this->label ? "<span class='label'>".$this->label."</span>" : "").
					"</a>".
				"</div>";
	}

	public static function fromArray(Array $stickerData) {
		return new KanbanBoardSticker(
			$label = isset($stickerData['label']) ? $stickerData['label'] : null, 
			$position = isset($stickerData['position']) ? $stickerData['position'] : array(),
			$class = isset($stickerData['class']) ? $stickerData['class'] : null, 
			$backgroundColor = isset($stickerData['backgroundColor']) ? $stickerData['backgroundColor'] : null, 
			$icon = isset($stickerData['icon']) ? $stickerData['icon'] : null, 
			$color = isset($stickerData['color']) ? $stickerData['color'] : null
		);
	}

	public function setPosition($coordinate=null, $secondCoordinate=null) {
		if (empty($coordinate)) {
			$this->position = ['x'=>0, 'y'=>0];
		}
		if (empty($secondCoordinate)) {//treat position as an array in a single param
			if (isset($coordinate['x']) && isset($coordinate['y'])) {
				$this->position = [
									'x' => intval($coordinate['x']),
									'y' => intval($coordinate['y'])
								];
			} else {
				$this->position = [
									'x' => intval($coordinate[0]),
									'y' => intval($coordinate[1])
								];
			}
		} else {
			$this->position = [
				'x' => $coordinate,
				'y' => $coordinate
			];
		}
		return $this;
	}

	public function toArray() {
		return [
			"label" => $this->label,
			"backgroundColor" => $this->backgroundColor,
			"color" => $this->color,
			"icon" => $this->icon,
			"class" => $this->class,
			"position" => $this->position
		];
	}

	public function toJSON() {
		return json_encode($this->toArray(), JSON_PRETTY_PRINT);
	}

}