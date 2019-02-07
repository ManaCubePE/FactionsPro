<?php

namespace FactionsPro\tasks;

use FactionsPro\FactionMain;
use pocketmine\Server;
use pocketmine\scheduler\Task;
use pocketmine\Player;

class updateTagTask extends Task{
	
	public $plugin;
	
	public function __construct(FactionMain $plugin){
		$this->plugin = $plugin;
	}
	public function onRun(int $currentTick) : void{
if($this->plugin->prefs->get("tag-type") == "scoretag"){
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			if($player instanceof Player){
			$player->setNameTagVisible();
			$f = $this->plugin->getPlayerFaction($player->getName());
				if($this->plugin->isInFaction($player->getName()) == true) {
				$player->setScoreTag(str_replace(["{player}", "{faction}"], [$player->getName(), $f], $this->plugin->prefs->get("faction-tag")));
					} else {
				if($this->plugin->isInFaction($player->getName()) == false){
					$this->plugin->getScheduler()->cancelTask($this->getTaskId());
} else {
if($this->plugin->prefs->get("tag-type") == "nametag"){
foreach(Server::getInstance()->getOnlinePlayers() as $player){
			if($player instanceof Player){
			$player->setNameTagVisible();
			$f = $this->plugin->getPlayerFaction($player->getName());
				if($this->plugin->isInFaction($player->getName()) == true) {
				$player->setNameTag(str_replace(["{player}", "{faction}"], [$player->getName(), $f], $this->plugin->prefs->get("faction-tag"))); 
					} else {
				if($this->plugin->isInFaction($player->getName()) == false){
					$this->plugin->getScheduler()->cancelTask($this->getTaskId());
				} else {
if($this->plugin->prefs->get("tag-type") == "displaytag"){
foreach(Server::getInstance()->getOnlinePlayers() as $player){
			if($player instanceof Player){
			$player->setNameTagVisible();
			$f = $this->plugin->getPlayerFaction($player->getName());
				if($this->plugin->isInFaction($player->getName()) == true) {
				$player->setDisplayName(str_replace(["{player}", "{faction}"], [$player->getName(), $f], $this->plugin->prefs->get("faction-tag")));
			} else {
				if($this->plugin->isInFaction($player->getName()) == false){
					$this->plugin->getScheduler()->cancelTask($this->getTaskId());
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
