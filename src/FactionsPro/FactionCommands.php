<?php

namespace FactionsPro;

use pocketmine\command\{Command, CommandSender};
use pocketmine\{Server, Player};
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\level\{Level, Position};

class FactionCommands {
	
    public $plugin;
    
    // ASCII Map
	CONST MAP_WIDTH = 50;
	CONST MAP_HEIGHT = 11;
	CONST MAP_HEIGHT_FULL = 17;
	CONST MAP_KEY_CHARS = "\\/#?ç¬£$%=&^ABCDEFGHJKLMNOPQRSTUVWXYZÄÖÜÆØÅ1234567890abcdeghjmnopqrsuvwxyÿzäöüæøåâêîûô";
	CONST MAP_KEY_WILDERNESS = TextFormat::GRAY . "-"; /*Del*/
	CONST MAP_KEY_SEPARATOR = TextFormat::AQUA . "*"; /*Del*/
	CONST MAP_KEY_OVERFLOW = TextFormat::WHITE . "-" . TextFormat::WHITE; # ::MAGIC?
	CONST MAP_OVERFLOW_MESSAGE = self::MAP_KEY_OVERFLOW . ": Too Many Factions (>" . 107 . ") on this Map.";
        
    public function __construct(FactionMain $pg) {
        $this->plugin = $pg;
    }
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if ($sender instanceof Player) {
            $playerName = $sender->getPlayer()->getName();
            if (strtolower($command->getName()) === "f") {
                if (empty($args)) {
                    $sender->sendMessage($this->plugin->formatMessage("§bPlease use §3/f help §6for a list of commands"));
                    return true;
                }
                    ///////////////////////////////// WAR /////////////////////////////////
                    if(strtolower($args[0]) == "war" or strtolower($args[0]) == "wr"){
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§5Please use: §d/f $args[0] <faction name:tp>\n§aDescription: §dRequest a war with a faction."));
                            return true;
                        }
                        if (strtolower($args[1]) == "tp" or strtolower($args[1]) == "teleport") {
                            foreach ($this->plugin->wars as $r => $f) {
                                $fac = $this->plugin->getPlayerFaction($playerName);
                                if ($r == $fac) {
                                    $x = mt_rand(0, $this->plugin->getNumberOfPlayers($fac) - 1);
                                    $tper = $this->plugin->war_players[$f][$x];
                                    $sender->teleport($this->plugin->getServer()->getPlayerByName($tper));
                                    return true;
                                }
                                if ($f == $fac) {
                                    $x = mt_rand(0, $this->plugin->getNumberOfPlayers($fac) - 1);
                                    $tper = $this->plugin->war_players[$r][$x];
                                    $sender->teleport($this->plugin->getServer()->getPlayer($tper));
                                    return true;
                                }
                            }
                            $sender->sendMessage("§cYou must be in a war to do that");
                            return true;
                        }
                        if (!($this->alphanum($args[1]))) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou may only use letters and numbers"));
                            return true;
                        }
                        if (!$this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe Faction named §4$args[1] §cdoes not exist"));
                            return true;
                        }
                        if (!$this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cOnly your faction leader may start wars"));
                            return true;
                        }
                        if (!$this->plugin->areEnemies($this->plugin->getPlayerFaction($playerName), $args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction is not an enemy of §4$args[1]"));
                            return true;
                        } else {
                            $factionName = $args[1];
                            $sFaction = $this->plugin->getPlayerFaction($playerName);
                            foreach ($this->plugin->war_req as $r => $f) {
                                if ($r == $args[1] && $f == $sFaction) {
                                    foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                                        $task = new FactionWar($this->plugin, $r);
                                        $handler = $this->plugin->getServer()->getScheduler()->scheduleDelayedTask($task, 20 * 60 * 2);
                                        $task->setHandler($handler);
                                        $p->sendMessage("§bThe war against §a$factionName §band §a$sFaction §bhas started!");
                                        if ($this->plugin->getPlayerFaction($p->getName()) == $sFaction) {
                                            $this->plugin->war_players[$sFaction][] = $p->getName();
                                        }
                                        if ($this->plugin->getPlayerFaction($p->getName()) == $factionName) {
                                            $this->plugin->war_players[$factionName][] = $p->getName();
                                        }
                                    }
                                    $this->plugin->wars[$factionName] = $sFaction;
                                    unset($this->plugin->war_req[strtolower($args[1])]);
                                    return true;
                                }
                            }
                            $this->plugin->war_req[$sFaction] = $factionName;
                            foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                                if ($this->plugin->getPlayerFaction($p->getName()) == $factionName) {
                                    if ($this->plugin->getLeader($factionName) == $p->getName()) {
                                        $p->sendMessage("§3$sFaction §bwants to start a war. Please use: §3'/f $args[0] $sFaction' §bto commence the war!");
                                        $sender->sendMessage("§aThe Faction war has been requested. §bPlease wait for their response.");
                                        return true;
                                    }
                                }
                            }
                            $sender->sendMessage("§cFaction leader is not online.");
                            return true;
                        }
                    }
                    /////////////////////////////// CREATE ///////////////////////////////
                    if(strtolower($args[0]) == "create" or strtolower($args[0]) == "make"){
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f $args[0] <faction name>"));
			    $sender->sendMessage($this->plugin->formatMessage("§b§aDescription: §dCreates a faction."));
                            return true;
                        }
                        if (!($this->alphanum($args[1]))) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou may only use letters and numbers"));
                            return true;
                        }
                        if ($this->plugin->isNameBanned($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe name §4$args[1] §cis not allowed"));
                            return true;
                        }
                        if ($this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe Faction named §4$args[1] §calready exists"));
                            return true;
                        }
                        if (strlen($args[1]) > $this->plugin->prefs->get("MaxFactionNameLength")) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThat name is too long, please try again"));
                            return true;
                        }
                        if ($this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must leave the faction first"));
                            return true;
                        } else {
                            $factionName = $args[1];
                            $rank = "Leader";
                            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                            $stmt->bindValue(":player", $playerName);
                            $stmt->bindValue(":faction", $factionName);
                            $stmt->bindValue(":rank", $rank);
                            $result = $stmt->execute();
                            $this->plugin->updateAllies($factionName);
                            $this->plugin->setFactionPower($factionName, $this->plugin->prefs->get("TheDefaultPowerEveryFactionStartsWith"));
			    $this->plugin->setBalance($factionName, $this->plugin->prefs->get("defaultFactionBalance"));
                            if($this->plugin->prefs->get("BroadcastFactionCreationMessage")){
		                $sender->getServer()->broadcastMessage(str_replace([
			            "%PLAYER%",
		                    "%FACTION%"
				    ], [
				    $sender->getName(),
				    $factionName
			        ], $this->plugin->prefs->get("FactionCreationBroadcastMessage")));
			   }
                            $sender->sendMessage($this->plugin->formatMessage("§bThe Faction named §a$factionName §bhas been created", true));
			    var_dump($this->plugin->db->query("SELECT * FROM balance;")->fetchArray(SQLITE3_ASSOC));
                            return true;
                        }
                    }
                    /////////////////////////////// INVITE ///////////////////////////////
                    if(strtolower($args[0]) == "invite" or strtolower($args[0]) == "inv"){
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f $args[0] <player>\n§aDescription: §dInvites a player to your faction."));
                            return true;
                        }
                        if ($this->plugin->isFactionFull($this->plugin->getPlayerFaction($playerName))) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThis faction is full, please kick players to make room"));
                            return true;
                        }
                        $invited = $this->plugin->getServer()->getPlayer($args[1]);
                        if (!($invited instanceof Player)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe player named §4$args[1] §cis currently not online"));
                            return true;
                        }
                        if ($this->plugin->isInFaction($invited->getName()) == true) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe player named §4$args[1] §cis already in a faction"));
                            return true;
                        }
                        if ($this->plugin->prefs->get("OnlyLeadersAndOfficersCanInvite")) {
                            if (!($this->plugin->isOfficer($playerName) || $this->plugin->isLeader($playerName))) {
                                $sender->sendMessage($this->plugin->formatMessage("§cOnly your faction leader/officers can invite"));
                                return true;
                            }
                        }
                        if ($invited->getName() == $playerName) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou can't invite yourself to your own faction"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($playerName);
                        $invitedName = $invited->getName();
                        $rank = "Member";
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO confirm (player, faction, invitedby, timestamp) VALUES (:player, :faction, :invitedby, :timestamp);");
                        $stmt->bindValue(":player", $invitedName);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":invitedby", $sender->getName());
                        $stmt->bindValue(":timestamp", time());
                        $result = $stmt->execute();
                        $sender->sendMessage($this->plugin->formatMessage("§a$invitedName §bhas been invited succesfully", true));
                        $invited->sendMessage($this->plugin->formatMessage("§bYou have been invited to §a$factionName. §bType §3'/f accept / yes' or '/f deny / no' §binto chat to accept or deny!", true));
                    }
                    /////////////////////////////// LEADER ///////////////////////////////
                    if ($args[0] == "leader"){
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f leader <player>\n§aDescription: §dMake someone else leader of the faction."));
                            return true;
                        }
                        if (!$this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to use this"));
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be leader to use this"));
                            return true;
                        }
                        if ($this->plugin->getPlayerFaction($playerName) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou need to add the player: §4$playerName §cto faction first"));
                            return true;
                        }
                        if (!($this->plugin->getServer()->getPlayer($args[1]) instanceof Player)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe player named §4$playerName §cis currently not online"));
                            return true;
                        }
                        if ($args[1] == $sender->getName()) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou can't transfer the leadership to yourself"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($playerName);
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $playerName);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Member");
                        $result = $stmt->execute();
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $args[1]);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Leader");
                        $result = $stmt->execute();
                        $sender->sendMessage($this->plugin->formatMessage("§aYou are no longer leader. §bYou made §a$args[1] §bThe leader of this faction", true));
                        $this->plugin->getServer()->getPlayer($args[1])->sendMessage($this->plugin->formatMessage("§aYou are now leader \nof §3$factionName!", true));
                    }
                    /////////////////////////////// PROMOTE ///////////////////////////////
                    if ($args[0] == "promote") {
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f promote <player>\n§aDescription: §dPromote a player from your faction."));
                            return true;
                        }
                        if (!$this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to use this"));
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be leader to use this"));
                            return true;
                        }
                        if ($this->plugin->getPlayerFaction($playerName) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe player named: §4$args[1] §cis not in this faction"));
                            return true;
                        }
                        if ($args[1] == $sender->getName()) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou can't promote yourself"));
                            return true;
                        }
                        if ($this->plugin->isOfficer($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe player named §4$args[1] §cis already an Officer of this faction"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($playerName);
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $args[1]);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Officer");
                        $result = $stmt->execute();
                        $promotee = $this->plugin->getServer()->getPlayer($args[1]);
                        $sender->sendMessage($this->plugin->formatMessage("§a$promotee §bhas been promoted to Officer", true));
                        if ($promotee instanceof Player) {
                            $promotee->sendMessage($this->plugin->formatMessage("§bYou were promoted to officer of §a$factionName!", true));
                            return true;
                        }
                    }
                    /////////////////////////////// DEMOTE ///////////////////////////////
                    if ($args[0] == "demote") {
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f demote <player>\n§aDescription: §dDemote a player from your faction"));
                            return true;
                        }
                        if ($this->plugin->isInFaction($sender->getName()) == false) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to use this"));
                            return true;
                        }
                        if ($this->plugin->isLeader($playerName) == false) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be leader to use this"));
                            return true;
                        }
                        if ($this->plugin->getPlayerFaction($playerName) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe player named: §4$playerName §cis not in this faction"));
                            return true;
                        }
                        if ($args[1] == $sender->getName()) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou can't demote yourself"));
                            return true;
                        }
                        if (!$this->plugin->isOfficer($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe player named §4$args[1] §cis already a Member of this faction"));
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($playerName);
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $args[1]);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Member");
                        $result = $stmt->execute();
                        $demotee = $this->plugin->getServer()->getPlayer($args[1]);
                        $sender->sendMessage($this->plugin->formatMessage("§5$demotee §2has been demoted to Member by §5$playerName", true));
                        if ($demotee instanceof Player) {
                            $demotee->sendMessage($this->plugin->formatMessage("§2You were demoted to member of §5$factionName!", true));
                            return true;
                        }
                    }
                    /////////////////////////////// KICK ///////////////////////////////
                    if(strtolower($args[0]) == "kick" or strtolower($args[0]) == "k"){
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f $args[0] <player>\n§aDescription: §dKicks a player from a faction."));
                            return true;
                        }
                        if ($this->plugin->isInFaction($sender->getName()) == false) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to use this"));
                            return true;
                        }
                        if ($this->plugin->isLeader($playerName) == false) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be leader to use this"));
                            return true;
                        }
                        if ($this->plugin->getPlayerFaction($playerName) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe Player named §4$playerName §cis not in this faction"));
                            return true;
                        }
                        if ($args[1] == $sender->getName()) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou can't kick yourself"));
                            return true;
                        }
                        $kicked = $this->plugin->getServer()->getPlayer($args[1]);
                        $factionName = $this->plugin->getPlayerFaction($playerName);
                        $this->plugin->db->query("DELETE FROM master WHERE player='$args[1]';");
                        $sender->sendMessage($this->plugin->formatMessage("§aYou successfully kicked §2$args[1]", true));
                        $this->plugin->subtractFactionPower($factionName, $this->plugin->prefs->get("PowerGainedPerPlayerInFaction"));
			$this->plugin->takeFromBalance($factionName, $this->plugin->prefs->get("MoneyGainedPerPlayerInFaction"));
                        if ($kicked instanceof Player) {
                            $kicked->sendMessage($this->plugin->formatMessage("§bYou have been kicked from \n §a$factionName", true));
                            return true;
                        }
                    }
                    /////////////////////////////// CLAIM ///////////////////////////////
                    if(strtolower($args[0]) == "claim" or strtolower($args[0]) == "cl"){
				if($this->plugin->prefs->get("ClaimingEnabled") == false){
					$sender->sendMessage($this->plugin->formatMessage("§cPlots are not enabled on this server."));
					return true;
			}
			if(!$this->plugin->isInFaction($playerName)){
			   $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction."));
			   return true;
			}
                        if (!in_array($sender->getPlayer()->getLevel()->getName(), $this->plugin->prefs->get("ClaimWorlds"))) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou can only claim in Faction Worlds: " . implode(" ", $this->plugin->prefs->get("ClaimWorlds"))));
                            return true;
                        }
                        if ($this->plugin->inOwnPlot($sender)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction has already claimed this area."));
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($sender->getPlayer()->getName());
                        if ($this->plugin->getNumberOfPlayers($faction) < $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot")) {
                            $needed_players = $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot") -
                                    $this->plugin->getNumberOfPlayers($faction);
                            $sender->sendMessage($this->plugin->formatMessage("§cYou need §4$needed_players §cmore players in your faction to claim a faction plot"));
                            return true;
                        }
                        if ($this->plugin->getFactionPower($faction) < $this->plugin->prefs->get("PowerNeededToClaimAPlot")) {
                            $needed_power = $this->plugin->prefs->get("PowerNeededToClaimAPlot");
                            $faction_power = $this->plugin->getFactionPower($faction);
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction doesn't have enough STR to claim a land."));
                            $sender->sendMessage($this->plugin->formatMessage("§4$needed_power §cSTR is required but your faction has only §4$faction_power §cSTR."));
                            return true;
			}
                        if ($this->plugin->getBalance($faction) < $this->plugin->prefs->get("MoneyNeededToClaimAPlot")) {
                            $needed_money = $this->plugin->prefs->get("MoneyNeededToClaimAPlot");
                            $balance = $this->plugin->getBalance($faction);
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction doesn't have enough Money to claim a land."));
                            $sender->sendMessage($this->plugin->formatMessage("§4$needed_money §cMoney is required but your faction has only §4$balance §cMoney."));
                            return true;
                        }
                        $x = floor($sender->getX());
			$y = floor($sender->getY());
			$z = floor($sender->getZ());
			$faction = $this->plugin->getPlayerFaction($sender->getPlayer()->getName());
			if(!$this->plugin->drawPlot($sender, $faction, $x, $y, $z, $sender->getPlayer()->getLevel(), $this->plugin->prefs->get("PlotSize"))){
				return true;
                        }
			$plot_size = $this->plugin->prefs->get("PlotSize");
                        $faction_power = $this->plugin->getFactionPower($faction);
                        $balance = $this->plugin->getBalance($faction);
			$this->plugin->subtractFactionPower($faction, $this->plugin->prefs->get("PowerNeededToClaimAPlot"));
                        $this->plugin->takeFromBalance($faction, $this->plugin->prefs->get("MoneyNeededToClaimAPlot"));
                        $sender->sendMessage($this->plugin->formatMessage("§bYour Faction plot has been claimed.", true));
		    }
                    if(strtolower($args[0]) == "plotinfo" or strtolower($args[0]) == "pinfo"){
                        $x = floor($sender->getX());
			$y = floor($sender->getY());
                        $z = floor($sender->getZ());
                        if (!$this->plugin->isInPlot($sender)) {
                            $sender->sendMessage($this->plugin->formatMessage("§5This plot is not claimed by anyone. §dYou can claim it by typing §5/f claim\n§dAlias Command: §5/f cl", true));
			    return true;
			}
                        $fac = $this->plugin->factionFromPoint($x, $z);
                        $power = $this->plugin->getFactionPower($fac);
                        $balance = $this->plugin->getBalance($fac);
                        $sender->sendMessage($this->plugin->formatMessage("§bThis plot is claimed by §a$fac §bwith §a$power §aSTR, §band §a$balance §bMoney"));
			return true;
                    }
                    if(strtolower($args[0]) == "forcedelete" or strtolower($args[0]) == "fdisband"){
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f $args[0] <faction>\n§aDescription: §dForce deletes a faction. For Operators only."));
                            return true;
                        }
                        if (!$this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoesn't exist."));
                            return true;
                        }
                        if (!($sender->isOp())) {
                            $sender->sendMessage($this->plugin->formatMessage("§4§lYou must be OP to do this."));
                            return true;
                        }
                        $this->plugin->db->query("DELETE FROM master WHERE faction='$args[1]';");
                        $this->plugin->db->query("DELETE FROM plots WHERE faction='$args[1]';");
                        $this->plugin->db->query("DELETE FROM allies WHERE faction1='$args[1]';");
                        $this->plugin->db->query("DELETE FROM allies WHERE faction2='$args[1]';");
                        $this->plugin->db->query("DELETE FROM strength WHERE faction='$args[1]';");
                        $this->plugin->db->query("DELETE FROM motd WHERE faction='$args[1]';");
                        $this->plugin->db->query("DELETE FROM home WHERE faction='$args[1]';");
		        $this->plugin->db->query("DELETE FROM balance WHERE faction='$args[1]';");
                        $sender->sendMessage($this->plugin->formatMessage("§aUnwanted faction was successfully deleted and their faction plot was unclaimed!", true));
                    }
                    if (strtolower($args[0]) == 'addstrto') {
                        if (!isset($args[1]) or ! isset($args[2])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f addstrto <faction> <STR>\n§aDescription: §dAdds STR to a faction."));
                            return true;
                        }
                        if (!$this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoesn't exist."));
                            return true;
                        }
                        if (!($sender->isOp())) {
                            $sender->sendMessage($this->plugin->formatMessage("§4§lYou must be OP to do this."));
                            return true;
                        }
                        $this->plugin->addFactionPower($args[1], $args[2]);
                        $sender->sendMessage($this->plugin->formatMessage("§bSuccessfully added §a$args[2] §bSTR to §a$args[1]", true));
                    }
                    if (strtolower($args[0]) == 'addbalto') {
                        if (!isset($args[1]) or ! isset($args[2])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f addbalto <faction> <money>\n§aDescription: §dAdds Money to a faction."));
                            return true;
                        }
                        if (!$this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoesn't exist."));
                            return true;
                        }
                        if (!($sender->isOp())) {
                            $sender->sendMessage($this->plugin->formatMessage("§4§lYou must be OP to do this."));
                            return true;
                        }
                        $this->plugin->addToBalance($args[1], $args[2]);
                        $sender->sendMessage($this->plugin->formatMessage("§bSuccessfully added §a$args[2] §bBalance to §a$args[1]", true));
                    }
                    if(strtolower($args[0]) == "playerfaction" or strtolower($args[0]) == "pf"){
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f $args[0] <player>\n§aDescription: §dCheck to see what faction a player's in."));
                            return true;
                        }
                        if (!$this->plugin->isInFaction($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe player named §4$args[1] §cis not in a faction or doesn't exist."));
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($args[1]);
                        $sender->sendMessage($this->plugin->formatMessage("§a-$args[1] §bis in the faction: §a$faction-", true));
                    }
                    
                    if (strtolower($args[0]) == "overclaim" or strtolower($args[0]) == "oc"){
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction."));
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be leader to use this."));
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($playerName);
                        if ($this->plugin->getNumberOfPlayers($faction) < $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot")) {
                            $needed_players = $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot") -
                                    $this->plugin->getNumberOfPlayers($faction);
                            $sender->sendMessage($this->plugin->formatMessage("§cYou need §4$needed_players §cmore players in your faction to overclaim a faction plot"));
                            return true;
                        }
                        if ($this->plugin->getFactionPower($faction) < $this->plugin->prefs->get("PowerNeededToClaimAPlot")) {
                            $needed_power = $this->plugin->prefs->get("PowerNeededToClaimAPlot");
                            $faction_power = $this->plugin->getFactionPower($faction);
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction doesn't have enough STR to claim a land."));
                            $sender->sendMessage($this->plugin->formatMessage("§4$needed_power §cSTR is required but your faction has only §4$faction_power §cSTR."));
                            return true;
                        }
                        $sender->sendMessage($this->plugin->formatMessage("§5Getting your coordinates...", true));
                        $x = floor($sender->getX());
                        $y = floor($sender->getY());
                        $z = floor($sender->getZ());
                        if ($this->plugin->prefs->get("EnableOverClaim")) {
                            if ($this->plugin->isInPlot($sender)) {
                                $faction_victim = $this->plugin->factionFromPoint($x, $z);
                                $faction_victim_power = $this->plugin->getFactionPower($faction_victim);
                                $faction_ours = $this->plugin->getPlayerFaction($playerName);
                                $faction_ours_power = $this->plugin->getFactionPower($faction_ours);
                                if ($this->plugin->inOwnPlot($sender)) {
                                    $sender->sendMessage($this->plugin->formatMessage("§cYou can't overclaim your own plot. It's already claimed."));
                                    return true;
                                } else {
                                    if ($faction_ours_power < $faction_victim_power) {
                                        $sender->sendMessage($this->plugin->formatMessage("§cYou can't overclaim the plot of §4$faction_victim §cbecause your STR is lower than theirs."));
                                        return true;
                                    } else {
                                        $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction_ours';");
                                        $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction_victim';");
                                        $arm = (($this->plugin->prefs->get("PlotSize")) - 1) / 2;
                                        $this->plugin->newPlot($faction_ours, $x + $arm, $z + $arm, $x - $arm, $z - $arm);
                                        $sender->sendMessage($this->plugin->formatMessage("§bThe faction plot of §3$faction_victim §bhas been over claimed! It is now yours.", true));
                                        return true;
                                    }
                                }
                            } else {
                                $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction plot."));
                                return true;
                            }
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("§cOverclaiming is disabled."));
                            return true;
                        }
                    }
                    /////////////////////////////// UNCLAIM ///////////////////////////////
                    if(strtolower($args[0]) == "unclaim" or strtolower($args[0]) == "uncl"){
				  if($this->plugin->prefs->get("ClaimingEnabled") == false){
					$sender->sendMessage($this->plugin->formatMessage("§cFaction Plots are not enabled on this server."));
					return true;
                        }
			if(!$this->plugin->isInFaction($playerName)){
			   $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction."));
			   return true;
			}
                        if (!$this->plugin->isLeader($sender->getName())) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be leader to use this"));
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                        $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction';");
                        $sender->sendMessage($this->plugin->formatMessage("§bYour land has been unclaimed! It is no longer yours.", true));
                    }
                    /////////////////////////////// DESCRIPTION ///////////////////////////////
                    if(strtolower($args[0]) == "desc" or strtolower($args[0]) == "motd"){
                        if ($this->plugin->isInFaction($sender->getName()) == false) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to use this!"));
                            return true;
                        }
                        if ($this->plugin->isLeader($playerName) == false) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be leader to use this"));
                            return true;
                        }
                        $sender->sendMessage($this->plugin->formatMessage("§dType your message in chat. It will not be visible to other players", true));
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO motdrcv (player, timestamp) VALUES (:player, :timestamp);");
                        $stmt->bindValue(":player", $sender->getName());
                        $stmt->bindValue(":timestamp", time());
                        $result = $stmt->execute();
		    }
		    /////////////////////////////// TOP, also by @PrimusLV //////////////////////////
					if(strtolower($args[0]) == "top" or strtolower($args[0]) == "lb"){
					          if(!isset($args[1])){
					          $sender->sendMessage($this->plugin->formatMessage("§aPlease use: §a/f $args[0] money §d- To check top 10 Richest Factions on the server\n§aPlease use: §b/f $args[0] str §d- To check Top 10 BEST Factions (Highest STR)"));
                            		          return true;
			      		          }
						    
					          if(isset($args[1]) && $args[1] == "money"){
                              $this->plugin->sendListOfTop10RichestFactionsTo($sender);
					          }else{
					          if(isset($args[1]) && $args[1] == "str"){
                              $this->plugin->sendListOfTop10FactionsTo($sender);
						           //$this->plugin->sendListOfTop10RichestFactionsTo($sender);
			                          }
			                          return true;
		                          }
                                        }
                    /////////////////////////////// ACCEPT ///////////////////////////////
                    if(strtolower($args[0]) == "accept" or strtolower($args[0]) == "yes"){
                        $lowercaseName = strtolower($playerName);
                        $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if (empty($array) == true) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou have not been invited to any factions"));
                            return true;
                        }
                        $invitedTime = $array["timestamp"];
                        $currentTime = time();
                        if (($currentTime - $invitedTime) <= 60) { //This should be configurable
                            $faction = $array["faction"];
                            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                            $stmt->bindValue(":player", ($playerName));
                            $stmt->bindValue(":faction", $faction);
                            $stmt->bindValue(":rank", "Member");
                            $result = $stmt->execute();
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                            $sender->sendMessage($this->plugin->formatMessage("§aYou successfully joined §2$faction", true));
                            $this->plugin->addFactionPower($faction, $this->plugin->prefs->get("PowerGainedPerPlayerInFaction"));
			    $this->plugin->addToBalance($faction, $this->plugin->prefs->get("MoneyGainedPerPlayerInFaction"));
                            $this->plugin->getServer()->getPlayer($array["invitedby"])->sendMessage($this->plugin->formatMessage("§2$playerName §ajoined the faction", true));
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("§cInvite has timed out"));
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$playerName';");
                        }
                    }
                    /////////////////////////////// DENY ///////////////////////////////
                    if(strtolower($args[0]) == "deny" or strtolower($args[0]) == "no"){
                        $lowercaseName = strtolower($playerName);
                        $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if (empty($array) == true) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou have not been invited to any factions"));
                            return true;
                        }
                        $invitedTime = $array["timestamp"];
                        $currentTime = time();
                        if (($currentTime - $invitedTime) <= 60) { //This should be configurable
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                            $sender->sendMessage($this->plugin->formatMessage("§cInvite declined", true));
                            $this->plugin->getServer()->getPlayer($array["invitedby"])->sendMessage($this->plugin->formatMessage("§4$playerName §cdeclined the invitation"));
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("§cInvite has timed out"));
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                        }
                    }
                    /////////////////////////////// DELETE ///////////////////////////////
                    if(strtolower($args[0]) == "del" or strtolower($args[0]) == "disband"){
                        if ($this->plugin->isInFaction($playerName) == true) {
                            if ($this->plugin->isLeader($playerName)) {
                                $faction = $this->plugin->getPlayerFaction($playerName);
                                $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM master WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM allies WHERE faction1='$faction';");
                                $this->plugin->db->query("DELETE FROM allies WHERE faction2='$faction';");
                                $this->plugin->db->query("DELETE FROM strength WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM motd WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM home WHERE faction='$faction';");
			        $this->plugin->db->query("DELETE FROM balance WHERE faction='$faction';");
                                $sender->sendMessage($this->plugin->formatMessage("§bThe Faction named: §a$faction §bhas been successfully disbanded and the faction plot, and Overclaims are unclaimed.", true));
                            } else {
                                $sender->sendMessage($this->plugin->formatMessage("§cYou are not leader!"));
				return true;
                            }
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou are not in a faction!"));
			    return true;
                        }
                    }
                    /////////////////////////////// LEAVE ///////////////////////////////
                    if(strtolower($args[0]) == "leave" or strtolower($args[0]) == "lv"){
                        if ($this->plugin->isLeader($playerName) == false) {
                            $faction = $this->plugin->getPlayerFaction($playerName);
                            $name = $sender->getName();
                            $this->plugin->db->query("DELETE FROM master WHERE player='$name';");
                            $sender->sendMessage($this->plugin->formatMessage("§bYou successfully left §a$faction", true));
                            $this->plugin->subtractFactionPower($faction, $this->plugin->prefs->get("PowerGainedPerPlayerInFaction"));
			    $this->plugin->takeFromBalance($faction, $this->plugin->prefs->get("MoneyGainedPerPlayerInFaction"));
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must delete the faction or give\nleadership to someone else first"));
			    return true;
                        }
                    }
                    /////////////////////////////// SETHOME ///////////////////////////////
                    if(strtolower($args[0]) == "sethome" or strtolower($args[0]) == "shome"){
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be leader to set home"));
                            return true;
                        }
			$faction_power = $this->plugin->getFactionPower($this->plugin->getPlayerFaction($playerName));
                        $needed_power = $this->plugin->prefs->get("PowerNeededToSetOrUpdateAHome");
                        if($faction_power < $needed_power){
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction doesn't have enough power to set a home. Get power by killing players!"));
                            $sender->sendMessage($this->plugin->formatMessage("§4$needed_power §cpower is required to set a home. Your faction has §4$faction_power §cpower."));
			    return true;
			}
                        $factionName = $this->plugin->getPlayerFaction($sender->getName());
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO home (faction, x, y, z, world) VALUES (:faction, :x, :y, :z, :world);");
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":x", $sender->getX());
                        $stmt->bindValue(":y", $sender->getY());
                        $stmt->bindValue(":z", $sender->getZ());
			$stmt->bindValue(":world", $sender->getLevel()->getName());
                        $result = $stmt->execute();
                        $sender->sendMessage($this->plugin->formatMessage("§bHome set succesfully for §a$factionName. §bNow, you can use: §3/f home", true));
                    }
                    /////////////////////////////// UNSETHOME ///////////////////////////////
                    if(strtolower($args[0]) == "unsethome" or strtolower($args[0]) == "delhome"){
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be leader to unset home"));
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                        $this->plugin->db->query("DELETE FROM home WHERE faction = '$faction';");
                        $sender->sendMessage($this->plugin->formatMessage("§bFaction Home was unset succesfully for §a$faction §3/f home §bwas removed from your faction.", true));
                    }
                    /////////////////////////////// HOME ///////////////////////////////
                    if (strtolower($args[0] == "home")) {
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        			
		        }
                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                        $result = $this->plugin->db->query("SELECT * FROM home WHERE faction = '$faction';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if (!empty($array)) {
			        if ($array['world'] === null || $array['world'] === ""){
				                                $sender->sendMessage($this->plugin->formatMessage("§cHome is missing world name, please delete and make it again"));
				       			        return true;
			       				}
			       				if(Server::getInstance()->loadLevel($array['world']) === false){
+								$sender->sendMessage($this->plugin->formatMessage("The world '" . $array['world'] .  "'' could not be found"));
				       				return true;
			      				 }
                              				 $level = Server::getInstance()->getLevelByName($array['world']);
+                           $sender->getPlayer()->teleport(new Position($array['x'], $array['y'], $array['z'], $level));
                            $sender->sendMessage($this->plugin->formatMessage("§bTeleported to your faction home succesfully!", true));
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("§cFaction Home is not set. You can set it with: §4/f sethome"));
                        }
                    }
		    /////////////////////////////// F WARP ///////////////////////////////
		    /*if (strtolower($args[0] == "setwarp")) {
			    if(!isset($args[1])){
                            $sender->sendMessage($this->plugin->formatMessage("§aPlease use: §b/f setwarp <warp_name>"));
                            return true;
			    }
			    
			    if (!$this->plugin->isInFaction($playerName)) {
				    $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to use this command"));
				    return true;
			    }
			    if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be leader to set warp"));
                            return true;
			    }
			$stmt->faction_cords = array('x' => (int) $sender->getX(),'y' => (int) $sender->getY(),'z' => (int) $sender->getZ());
                        $stmt->world = $sender->getLevel()->getName();
                        $stmt->faction_warp = $args[1];
			$stm->faction = $factionName;
                        $stmt->prepare = $this->plugin->db->prepare("SELECT faction,title,x,y,z,world FROM faction warp WHERE title = :title");
                        $stmt->prepare->bindValue(":title", $this->faction_warp, SQLITE3_TEXT);
                        $result = $stm->execute();
                        $sql          = $stm->fetchall();
                        if( count($sql) > 1 )
			$stmt->prepare = $this->plugin->db->prepare("UPDATE warps SET faction = :faction, world = :world, title = :title, x = :x, y = :y, z = :z WHERE title = :title");
                        $stmt->bindValue(":faction", $factionName);
			$stmt->bindValue(":world", $sender->getLevel()->getName());
			$stm->bindValue(":title", $args[1]);
                        $stmt->bindValue(":x", $sender->getX());
                        $stmt->bindValue(":y", $sender->getY());
                        $stmt->bindValue(":z", $sender->getZ());
                        $result = $stmt->execute();
                        $sender->sendMessage($this->plugin->formatMessage("§aFaction Warp set succesfully as $args[1]. §bNow, you can use: §3/f warp $args[1]", true));
	    	    }TODO*/
		    /////////////////////////////// F TITLES ///////////////////////////////
		    /*TODO LIST*/
		    
		    /////////////////////////////// F Titles upon entering / leaving a claim ///////////////////////////////
		    /*TODO LIST*/
		    
		    /////////////////////////////// F RENAME ///////////////////////////////
		    /*TODO LIST*/
		    
		    /////////////////////////////// POWER ///////////////////////////////
                    if(strtolower($args[0]) == "power" or strtolower($args[0]) == "pw"){
                        if(!$this->plugin->isInFaction($playerName)) {
							$sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
			}
                        $faction_power = $this->plugin->getFactionPower($this->plugin->getPlayerFaction($sender->getName()));
                        
                        $sender->sendMessage($this->plugin->formatMessage("§bYour faction has§a $faction_power §bpower",true));
                    }
                    if(strtolower($args[0]) == "seepower" or strtolower($args[0]) == "sp"){
                        if(!isset($args[1])){
                            $sender->sendMessage($this->plugin->formatMessage("§aPlease use: §b/f $args[0] <faction>\n§aDescription: §bAllows you to see A faction's power."));
                            return true;
                        }
                        if(!$this->plugin->factionExists($args[1])) {
							$sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoes not exist"));
                            return true;
			}
                        $faction_power = $this->plugin->getFactionPower($args[1]);
                        $sender->sendMessage($this->plugin->formatMessage("§a$args[1] §bhas §a$faction_power §bpower.",true));
                    }
                    /////////////////////////////// MEMBERS/OFFICERS/LEADER AND THEIR STATUSES ///////////////////////////////
                    if (strtolower($args[0] == "ourmembers")) {
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $this->plugin->getPlayerFaction($playerName), "Member");
                    }
                    if (strtolower($args[0] == "listmembers")) {
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f listmembers <faction>\n§aDescription: §dGet's a list of faction members in a faction."));
                            return true;
                        }
                        if (!$this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoesn't exist"));
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $args[1], "Member");
                    }
                    if (strtolower($args[0] == "ourofficers")) {
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $this->plugin->getPlayerFaction($playerName), "Officer");
                    }
                    if (strtolower($args[0] == "listofficers")) {
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f listofficers <faction>\n§aDescription: §dGet's a list of officers in a faction."));
                            return true;
                        }
                        if (!$this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoesn't exist"));
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $args[1], "Officer");
                    }
                    if (strtolower($args[0] == "ourleader")) {
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $this->plugin->getPlayerFaction($playerName), "Leader");
                    }
                    if (strtolower($args[0] == "listleader")) {
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f listleader <faction>\n§aDescription: §dGet's the name of the leader of a faction."));
                            return true;
                        }
                        if (!$this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoesn't exist"));
                            return true;
                        }
                        $this->plugin->getPlayersInFactionByRank($sender, $args[1], "Leader");
                    }
                    if(strtolower($args[0]) == "say" or strtolower($args[0]) == "bc"){
                        if (!$this->plugin->prefs->get("AllowChat")) {
              	    $sender->sendMessage($this->plugin->formatMessage("§6/f $args[0] is disabled"));
         			    return true;
				}
			        if (!isset($args[1])) {
                            	    $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f $args[0] <message>\n§aDescription: §dBroadcasts a message to the whole faction."));
                                    return true;
              	}
         			if (!($this->plugin->isInFaction($playerName))) {
         			    $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to send faction messages"));
           		    return true;
         			}
         			$r = count($args);
         			$row = array();
         			$rank = "";
         			$faction = $this->plugin->getPlayerFaction($playerName);
         			if ($this->plugin->isOfficer($playerName)) {
         			    $rank = "*";
         			} else if ($this->plugin->isLeader($playerName)) {
         			    $rank = "**";
         			}
         			$message = "-> ";
         			for ($i = 0; $i < $r - 1; $i = $i + 1) {
         			    $message = $message . $args[$i + 1] . " ";
         			}
              	$result = $this->plugin->db->query("SELECT * FROM master WHERE faction='$faction';");
         			for ($i = 0; $resultArr = $result->fetchArray(SQLITE3_ASSOC); $i = $i + 1) {
          			    $row[$i]['player'] = $resultArr['player'];
         			    $p = $this->plugin->getServer()->getPlayer($row[$i]['player']);
         			    if ($p instanceof Player) {
         				$p->sendMessage(TextFormat::ITALIC . TextFormat::RED . "§r§8[" . TextFormat::AQUA . "§3$rank$f§8] " . TextFormat::GREEN . "§b$playerName" . ": " . TextFormat::RESET);
         				$p->sendMessage(TextFormat::ITALIC . TextFormat::DARK_PURPLE . $message . TextFormat::RESET);
                            }
                        }
                    }
                    ////////////////////////////// ALLY SYSTEM ////////////////////////////////
                    if (strtolower($args[0] == "enemy")) {
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f enemy <faction>\n§aDescription: §dEnemy a faction."));
                            return true;
                        }
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be the leader to do this"));
                            return true;
                        }
                        if (!$this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoesn't exist"));
                            return true;
                        }
                        if ($this->plugin->getPlayerFaction($playerName) == $args[1]) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction can not enemy with itself"));
                            return true;
                        }
                        if ($this->plugin->areAllies($this->plugin->getPlayerFaction($playerName), $args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction is already enemied with §4$args[1]"));
                            return true;
                        }
                        $fac = $this->plugin->getPlayerFaction($playerName);
                        $leader = $this->plugin->getServer()->getPlayer($this->plugin->getLeader($args[1]));
                        if (!($leader instanceof Player)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe leader of the faction named §4$args[1] §cis not online"));
                            return true;
                        }
                        $this->plugin->setEnemies($fac, $args[1]);
                        $sender->sendMessage($this->plugin->formatMessage("§bYou are now enemies with §a$args[1]!", true));
                        $leader->sendMessage($this->plugin->formatMessage("§bThe leader of §a$fac §bhas declared your faction as an enemy", true));
                    }
                    if(strtolower($args[0]) == "ally" or strtolower($args[0]) == "a"){
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f $args[0] <faction>\n§aDescription: §dAlly with a faction."));
                            return true;
                        }
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be the leader to do this"));
                            return true;
                        }
                        if (!$this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoesn't exist"));
                            return true;
                        }
                        if ($this->plugin->getPlayerFaction($playerName) == $args[1]) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction can not ally with itself"));
                            return true;
                        }
                        if ($this->plugin->areAllies($this->plugin->getPlayerFaction($playerName), $args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction is already allied with §4$args[1]"));
                            return true;
                        }
                        $fac = $this->plugin->getPlayerFaction($playerName);
                        $leader = $this->plugin->getServer()->getPlayer($this->plugin->getLeader($args[1]));
                        $this->plugin->updateAllies($fac);
                        $this->plugin->updateAllies($args[1]);
                        if (!($leader instanceof Player)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe leader of the faction named §4$args[1] §cis not online"));
                            return true;
                        }
                        if ($this->plugin->getAlliesCount($args[1]) >= $this->plugin->getAlliesLimit()) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §chas the maximum amount of allies", true));
             
                        }
                        if ($this->plugin->getAlliesCount($fac) >= $this->plugin->getAlliesLimit()) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction has the maximum amount of allies", true));
                        }
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO alliance (player, faction, requestedby, timestamp) VALUES (:player, :faction, :requestedby, :timestamp);");
                        $stmt->bindValue(":player", $leader->getName());
                        $stmt->bindValue(":faction", $args[1]);
                        $stmt->bindValue(":requestedby", $sender->getName());
                        $stmt->bindValue(":timestamp", time());
                        $result = $stmt->execute();
                        $sender->sendMessage($this->plugin->formatMessage("§bYou requested to ally with §a$args[1]!\n§bWait for the leader's response...", true));
                        $leader->sendMessage($this->plugin->formatMessage("§bThe leader of §a$fac §brequested an alliance.\nType §3/f allyok §bto accept or §3/f allyno §bto deny.", true));
                    }
                    if(strtolower($args[0]) == "unally" or strtolower($args[0]) == "una"){
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f $args[0] <faction>\n§aDescription: §dUn allies a faction."));
                            return true;
                        }
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be the leader to do this"));
                            return true;
                        }
                        if (!$this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoesn't exist"));
                            return true;
                        }
                        if ($this->plugin->getPlayerFaction($playerName) == $args[1]) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction can not break alliance with itself"));
                            return true;
                        }
                        if (!$this->plugin->areAllies($this->plugin->getPlayerFaction($playerName), $args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction is already allied with §4$args[1]"));
                            return true;
                        }
                        $fac = $this->plugin->getPlayerFaction($playerName);
                        $leader = $this->plugin->getServer()->getPlayer($this->plugin->getLeader($args[1]));
                        $this->plugin->deleteAllies($fac, $args[1]);
                        $this->plugin->deleteAllies($args[1], $fac);
                        $this->plugin->subtractFactionPower($fac, $this->plugin->prefs->get("PowerGainedPerAlly"));
                        $this->plugin->subtractFactionPower($args[1], $this->plugin->prefs->get("PowerGainedPerAlly"));
			$this->plugin->takeFromBalance($fac, $this->plugin->prefs->get("MoneyGainedPerAlly"));
                        $this->plugin->updateAllies($fac);
                        $this->plugin->updateAllies($args[1]);
                        $sender->sendMessage($this->plugin->formatMessage("§bYour faction §a$fac §bis no longer allied with §a$args[1]", true));
                        if ($leader instanceof Player) {
                            $leader->sendMessage($this->plugin->formatMessage("§bThe leader of §a$fac §bbroke the alliance with your faction §a$args[1]", false));
                        }
                    }
                    if(strtolower($args[0]) == "forceunclaim" or strtolower($args[0]) == "func"){
                        if (!isset($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f $args[0] <faction>\n§aDescription: §dForce Unclaims a land. - Operators only."));
                            return true;
                        }
                        if (!$this->plugin->factionExists($args[1])) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoesn't exist"));
                            return true;
                        }
                        if (!($sender->isOp())) {
                            $sender->sendMessage($this->plugin->formatMessage("§4§lYou must be OP to do this."));
                            return true;
                        }
                        $sender->sendMessage($this->plugin->formatMessage("§bSuccessfully unclaimed the unwanted plot of §a$args[1]"));
                        $this->plugin->db->query("DELETE FROM plots WHERE faction='$args[1]';");
                    }
                    if (strtolower($args[0] == "allies")) {
                        if (!isset($args[1])) {
                            if (!$this->plugin->isInFaction($playerName)) {
                                $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                                return true;
                            }
                            $this->plugin->updateAllies($this->plugin->getPlayerFaction($playerName));
                            $this->plugin->getAllAllies($sender, $this->plugin->getPlayerFaction($playerName));
                        } else {
                            if (!$this->plugin->factionExists($args[1])) {
                                $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoesn't exist"));
                                return true;
                            }
                            $this->plugin->updateAllies($args[1]);
                            $this->plugin->getAllAllies($sender, $args[1]);
                        }
                    }
                    if(strtolower($args[0]) == "allyok" or strtolower($args[0]) == "allyaccept"){
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be a leader to do this"));
                            return true;
                        }
                        $lowercaseName = strtolower($playerName);
                        $result = $this->plugin->db->query("SELECT * FROM alliance WHERE player='$lowercaseName';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if (empty($array) == true) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction has not been requested to ally with any factions"));
                            return true;
                        }
                        $allyTime = $array["timestamp"];
                        $currentTime = time();
                        if (($currentTime - $allyTime) <= 60) { //This should be configurable
                            $requested_fac = $this->plugin->getPlayerFaction($array["requestedby"]);
                            $sender_fac = $this->plugin->getPlayerFaction($playerName);
                            $this->plugin->setAllies($requested_fac, $sender_fac);
                            $this->plugin->setAllies($sender_fac, $requested_fac);
                            $this->plugin->addFactionPower($sender_fac, $this->plugin->prefs->get("PowerGainedPerAlly"));
                            $this->plugin->addFactionPower($requested_fac, $this->plugin->prefs->get("PowerGainedPerAlly"));
			    $this->plugin->addToBalance($sender_fac, $this->plugin->prefs->get("MoneyGainedPerAlly"));
			    $this->plugin->addToBalance($requested_fac, $this->plugin->prefs->get("MoneyGainedPerAlly"));
                            $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
                            $this->plugin->updateAllies($requested_fac);
                            $this->plugin->updateAllies($sender_fac);
                            $sender->sendMessage($this->plugin->formatMessage("§bYour faction has successfully allied with §a$requested_fac", true));
                            $this->plugin->getServer()->getPlayer($array["requestedby"])->sendMessage($this->plugin->formatMessage("§a$playerName §bfrom §a$sender_fac §bhas accepted the alliance!", true));
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("§cRequest has timed out"));
                            $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
                        }
                    }
                    if(strtolower($args[0]) == "allyno" or strtolower($args[0]) == "allydeny"){
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to do this"));
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be a leader to do this"));
                            return true;
                        }
                        $lowercaseName = strtolower($playerName);
                        $result = $this->plugin->db->query("SELECT * FROM alliance WHERE player='$lowercaseName';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if (empty($array) == true) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYour faction has not been requested to ally with any factions"));
                            return true;
                        }
                        $allyTime = $array["timestamp"];
                        $currentTime = time();
                        if (($currentTime - $allyTime) <= 60) { //This should be configurable
                            $requested_fac = $this->plugin->getPlayerFaction($array["requestedby"]);
                            $sender_fac = $this->plugin->getPlayerFaction($playerName);
                            $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
                            $sender->sendMessage($this->plugin->formatMessage("§bYour faction has successfully declined the alliance request.", true));
                            $this->plugin->getServer()->getPlayer($array["requestedby"])->sendMessage($this->plugin->formatMessage("§a$playerName §bfrom §a$sender_fac §bhas declined the alliance!"));
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("§cRequest has timed out"));
                            $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
                        }
                    }
                    /////////////////////////////// ABOUT ///////////////////////////////
                    if(strtolower($args[0]) == "about" or strtolower($args[0]) == "info"){
                        $sender->sendMessage(TextFormat::GREEN . "[ORIGINAL] §aFactionsPro §cv2.0.0 by " . TextFormat::BOLD . "§5VMPE Development Team");
                        $sender->sendMessage(TextFormat::GOLD . "§7[§2MODDED§7] §3This version is by §6Void§bFactions§cPE\n§b[EDITED] §cThis plugin was edited by " . TextFormat::BOLD . "§5Zeao");
                    }
                    ////////////////////////////// CHAT ////////////////////////////////
		    
                    if (strtolower($args[0]) == "chat" or strtolower($args[0]) == "c") {
                        if (!$this->plugin->prefs->get("AllowChat")){
                            $sender->sendMessage($this->plugin->formatMessage("§6All Faction chat is disabled", false));
                        }
                        
                        if ($this->plugin->isInFaction($playerName)) {
                            if (isset($this->plugin->factionChatActive[$playerName])) {
                                unset($this->plugin->factionChatActive[$playerName]);
                                $sender->sendMessage($this->plugin->formatMessage("§6Faction chat disabled", true));
                            } else {
                                $this->plugin->factionChatActive[$playerName] = 1;
                                $sender->sendMessage($this->plugin->formatMessage("§aFaction chat enabled", true));
                            }
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou are not in a faction"));
                            return true;
                        }
                    }
                    if (strtolower($args[0]) == "allychat" or strtolower($args[0]) == "ac") {
                        if (!$this->plugin->prefs->get("AllowChat")){
                            $sender->sendMessage($this->plugin->formatMessage("§cAll Faction chat is disabled", false));
                        }
                        
                        if ($this->plugin->isInFaction($playerName)) {
                            if (isset($this->plugin->allyChatActive[$playerName])) {
                                unset($this->plugin->allyChatActive[$playerName]);
                                $sender->sendMessage($this->plugin->formatMessage("§6Ally chat disabled", true));
                            } else {
                                $this->plugin->allyChatActive[$playerName] = 1;
                                $sender->sendMessage($this->plugin->formatMessage("§aAlly chat enabled", true));
                            }
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou are not in a faction"));
                            return true;
                        }
                    }
		////////////////////////////// BALANCE, by primus ;) ///////////////////////////////////////
					if(strtolower($args[0]) == "bal" or strtolower($args[0]) == "balance"){
						if(!$this->plugin->isInFaction($playerName)){
							$sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to check balance!", false));
							return true;
						}
						$faction = $this->plugin->getPlayerFaction($playerName);
						$balance = $this->plugin->getBalance($faction);
						$sender->sendMessage($this->plugin->formatMessage("§6Faction balance: " . TextFormat::GREEN . "$".$balance));
						return true;
					}
		    			if(strtolower($args[0]) == "seebalance" or strtolower($args[0]) == "sb"){
                        		   if(!isset($args[1])){
                            		        $sender->sendMessage($this->plugin->formatMessage("§aPlease use: §b/f $args[0] <faction>\n§aDescription: §bAllows you to see A faction's balance."));
                           			return true;
                        		   }
                        		   if(!$this->plugin->factionExists($args[1])) {
									   $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoes not exist"));
                            		       return true;
					   }
                       			   $balance = $this->plugin->getBalance($args[1]);
                       			   $sender->sendMessage($this->plugin->formatMessage("§bThe faction §a $args[1] §bhas §a$balance §bMoney", true));
                    			}
					if(strtolower($args[0]) == "withdraw" or strtolower($args[0]) == "wd"){
					   if(!isset($args[1])){
							$sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f $args[0] <amount>\n§aDescription: §dWithdraw money from your faction bank."));
							return true;
                                                }
                        if(($e = $this->plugin->getEconomy()) == null){
						}
						if(!is_numeric($args[1])){
							$sender->sendMessage($this->plugin->formatMessage("§cAmount must be numeric value. You put §4$args[1]", false));
							return true;
						}
						if(!$this->plugin->isInFaction($playerName)){
							$sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to check balance!", false));
							return true;
						}
						if(!$this->plugin->isLeader($playerName)){
							$sender->sendMessage($this->plugin->formatMessage("§cOnly leader can withdraw from faction bank account!", false));
							return true;
						}
						$faction = $this->plugin->getPlayerFaction($sender->getName());
						if( (($fM = $this->plugin->getBalance($faction)) - ($args[1]) ) < 0 ){
							$sender->sendMessage($this->plugin->formatMessage("§cYour faction doesn't have enough money! It has: §4$fM", false));
							return true;
						}
						$this->plugin->takeFromBalance($faction, $args[1]);
						$e->addMoney($sender, $args[1], false, "faction bank account");
						$sender->sendMessage($this->plugin->formatMessage("§a$".$args[1]." §bgranted from faction", true));
						return true;
					}
					if(strtolower($args[0]) == "donate" or strtolower($args[0]) == "pay"){
					   if(!isset($args[1])){
						       $sender->sendMessage($this->plugin->formatMessage("§bPlease use: §3/f $args[0] <amount>\n§aDescription: §dDonate money to your/the faction you're in."));
						       return true;
                                                }
                        if(($e = $this->plugin->getEconomy()) === null){
						}
						if(!is_numeric($args[1])){
							$sender->sendMessage($this->plugin->formatMessage("§cAmount must be numeric value. You put: §4$args[1]", false));
							return true;
						}
						if(!$this->plugin->isInFaction($playerName)){
							$sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to donate", false));
							return true;
						}
						if( ( ($e->myMoney($sender)) - ($args[1]) ) < 0 ){
							$sender->sendMessage($this->plugin->formatMessage("§cYou dont have enough money!", false));
							return true;
						}
						$faction = $this->plugin->getPlayerFaction($sender->getName());
						if($e->reduceMoney($sender, $args[1], false, "faction bank account") === \onebone\economyapi\EconomyAPI::RET_SUCCESS){
							$this->plugin->addToBalance($faction, $args[1]);
							$sender->sendMessage($this->plugin->formatMessage("§a$".$args[1]." §bdonated to your faction"));
							return true;
						}
					}
                /////////////////////////////// MAP, map by Primus (no compass) ////////////////////////////////
					// Coupon for compass: G1wEmEde0mp455
					if(strtolower($args[0] == "map")) {
                        if(!isset($args[1])) {
					    $size = 1;
						$map = $this->getMap($sender, self::MAP_WIDTH, self::MAP_HEIGHT, $sender->getYaw(), $size);
						foreach($map as $line) {
				        $sender->sendMessage($line);
                          
						}
						return true;
					    }
                    }
               
                /////////////////////////////// WHO ///////////////////////////////
                if (strtolower($args[0]) == 'who') {
                    if (isset($args[1])) {
                        if (!(ctype_alnum($args[1])) or !($this->plugin->factionExists($args[1]))) {
                            $sender->sendMessage($this->plugin->formatMessage("§cThe faction named §4$args[1] §cdoes not exist"));
                            return true;
                        }
                        $faction = $args[1];
                        $result = $this->plugin->db->query("SELECT * FROM motd WHERE faction='$faction';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        $power = $this->plugin->getFactionPower($faction);
                        $message = $array["message"];
                        $leader = $this->plugin->getLeader($faction);
                        $numPlayers = $this->plugin->getNumberOfPlayers($faction);
			$maxPlayers = $this->plugin->prefs->get("MaxPlayersPerFaction");
			$balance = $this->plugin->getBalance($faction);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§3_____§2[§5§l$faction Information§r§2]§3_____" . TextFormat::RESET);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§cLeader Name: " . TextFormat::YELLOW . "§5$leader" . TextFormat::RESET);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§dPlayers: " . TextFormat::LIGHT_PURPLE . "§5$numPlayers/$maxPlayers" . TextFormat::RESET);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§eStrength " . TextFormat::RED . "§d$power" . " §5STR" . TextFormat::RESET);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§aDescription: " . TextFormat::AQUA . TextFormat::UNDERLINE . "§5$message" . TextFormat::RESET);
			$sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§bFaction Balance: " . TextFormat::AQUA . "§5$" . TextFormat::DARK_PURPLE . "$balance" . TextFormat::RESET);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§3_____§2[§5§lFaction Information§2]§3_____§r" . TextFormat::RESET);
		    } else {
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("§cYou must be in a faction to use this!"));
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction(($sender->getName()));
                        $result = $this->plugin->db->query("SELECT * FROM motd WHERE faction='$faction';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        $power = $this->plugin->getFactionPower($faction);
                        $message = $array["message"];
                        $leader = $this->plugin->getLeader($faction);
                        $numPlayers = $this->plugin->getNumberOfPlayers($faction);
			$maxPlayers = $this->plugin->prefs->get("MaxPlayersPerFaction");
			$balance = $this->plugin->getBalance($faction);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§3_____§2[§5§lYour Faction Information§r§2]§3_____" . TextFormat::RESET);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§bFaction Name: " . TextFormat::GREEN . "§5$faction" . TextFormat::RESET);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§cLeader Name: " . TextFormat::YELLOW . "§5$leader" . TextFormat::RESET);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§dPlayers: " . TextFormat::LIGHT_PURPLE . "§5$numPlayers/$maxPlayers" . TextFormat::RESET);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§eStrength: " . TextFormat::RED . "§d$power" . " §5STR" . TextFormat::RESET);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§aDescription: " . TextFormat::AQUA . TextFormat::UNDERLINE . "§b$message" . TextFormat::RESET);
			$sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§bFaction Balance: " . TextFormat::AQUA . "§5$" . TextFormat::DARK_PURPLE . "$balance" . TextFormat::RESET);
                        $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "§3_____§2[§5§lYour Faction Information§r§2]§3_____" . TextFormat::RESET);
                    }
                    return true;
                }
		if(strtolower($args[0]) == "help"){
			if(!isset($args[1]) || $args[1] == 1){
				$sender->sendMessage(TextFormat::BLUE . "§6Void§bFactions§cPE §dHelp §2[§51/7§2]" . TextFormat::RED . "\n§a/f about|info - §7Shows Plugin information\n§a/f accept|yes - §7Accepts an faction invitation\n§a/f claim|cl - §7Claims a faction plot!\n§a/f create|make <name> - §7Creates a faction.\n§a/f del|disband - Deletes a faction.\n§a/f demote <player> - §7Demotes a player from a faction.\n§a/f deny|no - §7Denies a player's invitation.");
				return true;
			}
			if($args[1] == 2){
				$sender->sendMessage(TextFormat::BLUE . "§6Void§bFactions§cPE §dHelp §2[§52/7§2]" . TextFormat::RED . "\n§a/f home|h - §7Teleports to your faction home.\n§a/f help <page> - §7Factions help.\n§a/f who - §7Your Faction info.\n§a/f who <faction> - §7Other faction info.\n§a/f invite|inv <player> - §7Invite a player to your faction.\n§a/f kick|k <player> - §7Kicks a player from your faction.\n§af/ leader <player> - §7Transfers leadership.\n§a/f leave|lv - §7Leaves a faction.");
				return true;
			}
			if($args[1] == 3){
				$sender->sendMessage(TextFormat::BLUE . "§6Void§bFactions§cPE §dHelp §2[§53/7§2]" . TextFormat::RED . "\n§a/f motd|desc - §7Set your faction Message of the day.\n§a/f promote <player> - §7Promote a player.\n§a/f sethome|shome - §7Set a faction home.\n§a/f unclaim|uncl - §7Unclaims a faction plot.\n§a/f unsethome|delhome - §7Deletes a faction home.\n§a/f top|lb - §7Checks top 10 BEST Factions on the server.\n§a/f war|wr <factionname|tp> - §7Starts a faction war / Requests a faction war.");
				return true;
			}
			if($args[1] == 4){
				$sender->sendMessage(TextFormat::BLUE . "§6Void§bFactions§cPE §dHelp §2[§54/7§2]" . TextFormat::RED . "\n§a/f enemy <faction> - §7Enemy with a faction\n§a/f ally <faction> - §7Ally a faction.\n§a/f allyok|allyaccept - §7Accepts a ally request.\n§a/f allydeny|no - §7Denies a ally request.\n§a/f unally|una - §7Un allies with a faction.\n§a/f allies - §7Checks a list of allies you currently have.\n§a/f say|bc <MESSAGE> - §7Broadcast a faction measage.");
				return true;
			}
			if($args[1] == 5){
				$sender->sendMessage(TextFormat::BLUE . "§6Void§bFactions§cPE §dHelp §2[§55/7§2]" . TextFormat::RED . "\n§a/f chat|c - §7Toggles faction chat.\n§a/f allychat|ac - §7Toggles Ally chat.\n§a/f plotinfo|pinfo - §7Checks if a specific area is claimed or not.\n§a/f power|pw - §7Checks to see how much power you have.\n§a/f seepower|sp <faction> - §7Sees power of another faction.");
				return true;
			}
			if($args[1] == 6){
				$sender->sendMessage(TextFormat::BLUE . "§6Void§bFactions§cPE §dHelp §2[§56/7§2]" . TextFormat::RED . "\n§a/f listleader <faction> - §7Checks who the leader is in a faction.\n§a/f listmembers <faction> - §7Checks who the members are in a faction.\n§a/f listofficers <faction> - §7Checks who the officers are in a faction.\n§a/f ourmembers - §7Checks who your faction members are.\n§a/f ourofficers - §7Checks who your faction officers are.\n§a/f ourleader - §7Checks to see who your leader is.");
				return true;
                        }
			if($args[1] == 7){
				$sender->sendMessage(TextFormat::BLUE . "§6Void§bFactions§cPE §dHelp §2[§57/7§2]" . TextFormat::RED . "\n§a/f donate|pay <amount> - §7Donate to a faction from your Eco Bank.\n§a/f withdraw|wd <amount> - §7With draw from your faction bank\n§a/f balance|bal - §7Checks your faction balance\n§a/f map|compass - §7Faction Map command\n§a/f overclaim|oc - §7Overclaims a plot.\n§a/f seebalance|sb - §7Checks other faction balances.\n§4§ldo /f help 8 to see OP Commands.");
				return true;
			}else{
				$sender->sendMessage(TextFormat::BLUE . "§6Void§bFactions§cPE §dHelp (OP Commands) §2[§51/1§2]" . TextFormat::RED . "\n§4/f addstrto <faction> <STR> - §cAdds Strength to a faction.\n§4/f addbalto <faction> <money> - §cAdds Money to a faction.\n§4/f forcedelete|fdisband <faction> - §cForce deletes a faction.\n§4/f forceunclaim|func <faction> - §cForce unclaims a plot / land.");
				return true;
		        }
                     }
                }
        } else {
            $this->plugin->getServer()->getLogger()->info($this->plugin->formatMessage("Please run this command in game"));
        }
        return true;
    }
    public function alphanum($string){
        if(function_exists('ctype_alnum')){
            $return = ctype_alnum($string);
        }else{
            $return = preg_match('/^[a-z0-9]+$/i', $string) > 0;
        }
        return $return;
    }
    public function getMap(Player $observer, int $width, int $height, int $inDegrees, int $size) { // No compass
		$to = (int)sqrt($size);
		$centerPs = new Vector3($observer->x >> $to, 0, $observer->z >> $to);
		$map = [];
		$centerFaction = $this->plugin->factionFromPoint($observer->getFloorX(), $observer->getFloorZ());
		$centerFaction = $centerFaction ? $centerFaction : "Wilderness";
		$head = TextFormat::DARK_GREEN . "§3________________." . TextFormat::DARK_GRAY . "[" .TextFormat::GREEN . " (" . $centerPs->getX() . "," . $centerPs->getZ() . ") " . $centerFaction . TextFormat::DARK_GRAY . "]" . TextFormat::DARK_GREEN . "§3.________________";
		$map[] = $head;
		$halfWidth = $width / 2;
		$halfHeight = $height / 2;
		$width = $halfWidth * 2 + 1;
		$height = $halfHeight * 2 + 1;
		$topLeftPs = new Vector3($centerPs->x + -$halfWidth, 0, $centerPs->z + -$halfHeight);
		// Get the compass
		$asciiCompass = self::getASCIICompass($inDegrees, TextFormat::RED, TextFormat::GOLD);
		// Make room for the list of names
		$height--;
		/** @var string[] $fList */
		$fList = array();
		$chrIdx = 0;
		$overflown = false;
		$chars = "-";
		// For each row
		for ($dz = 0; $dz < $height; $dz++) {
			// Draw and add that row
			$row = "";
			for ($dx = 0; $dx < $width; $dx++) {
				if ($dx == $halfWidth && $dz == $halfHeight) {
					$row .= "§b". "-";
					continue;
				}
				if (!$overflown && $chrIdx >= strlen($this->plugin->getMapBlock())) $overflown = true;
				$herePs = $topLeftPs->add($dx, 0, $dz);
				$hereFaction = $this->plugin->factionFromPoint($herePs->x << $to, $herePs->z << $to);
				$contains = in_array($hereFaction, $fList, true);
				if ($hereFaction === NULL) {
                    $SemClaim = "§7". "-";
					$row .= $SemClaim;
				} elseif (!$contains && $overflown) {
                    $Caverna = "§f"."-";
					$row .= $Caverna;
				} else {
					if (!$contains) $fList[$chars{$chrIdx++}] = $hereFaction;
					$fchar = "-";
					$row .= $this->getColorForTo($observer, $hereFaction) . $fchar;
				}
			}
			$line = $row; // ... ---------------
			// Add the compass
          $OPlayer = "§b". "-";
			if ($dz == 0) $line = substr($row, 0 * strlen($OPlayer))."  ".$asciiCompass[0];
			if ($dz == 1) $line = substr($row, 0 * strlen($OPlayer))."  ".$asciiCompass[1];
			if ($dz == 2) $line = substr($row, 0 * strlen($OPlayer))."  ". $asciiCompass[2];
          if ($dz == 4) $line = substr($row, 0 * strlen($OPlayer))."  §2". "-" . " §a Wilderness";
          if ($dz == 5) $line = substr($row, 0 * strlen($OPlayer)). "  §3". "-" . " §b Claimed Land";
         if ($dz == 6) $line = substr($row, 0 * strlen($OPlayer)). "  §4". "-" ." §c Warzone";
         if ($dz == 7) $line = substr($row, 0 * strlen($OPlayer)). "  §5". "-" ." §d You";
         if ($dz == 8) $line = substr($row, 0 * strlen($OPlayer));
         
			$map[] = $line;
		}
		$fRow = "";
		foreach ($fList as $char => $faction) {
			$fRow .= $this->getColorForTo($observer, $faction) . $this->plugin->getMapBlock() . ": " . $faction . " ";
		}
        if ($overflown) $fRow .= self::MAP_OVERFLOW_MESSAGE;
		$fRow = trim($fRow);
		$map[] = $fRow;
		return $map;
	}
	public function getColorForTo(Player $player, $faction) {
		if($this->plugin->getPlayerFaction($player->getName()) === $faction) {
			return "§6";
		}
		return "§c";
	}
	   const N = 'N';
    const NE = '/';
    const E = 'E';
    const SE = '\\';
    const S = 'S';
    const SW = '/';
    const W = 'W';
    const NW = '\\';
    public static function getASCIICompass($degrees, $colorActive, $colorDefault) : array
    {
        $ret = [];
        $point = self::getCompassPointForDirection($degrees);
        $row = "";
        $row .= ($point === self::NW ? $colorActive : $colorDefault) . self::NW;
        $row .= ($point === self::N ? $colorActive : $colorDefault) . self::N;
        $row .= ($point === self::NE ? $colorActive : $colorDefault) . self::NE;
        $ret[] = $row;
        $row = "";
        $row .= ($point === self::W ? $colorActive : $colorDefault) . self::W;
        $row .= $colorDefault . "+";
        $row .= ($point === self::E ? $colorActive : $colorDefault) . self::E;
        $ret[] = $row;
        $row = "";
        $row .= ($point === self::SW ? $colorActive : $colorDefault) . self::SW;
        $row .= ($point === self::S ? $colorActive : $colorDefault) . self::S;
        $row .= ($point === self::SE ? $colorActive : $colorDefault) . self::SE;
        $ret[] = $row;
        return $ret;
    }
    public static function getCompassPointForDirection($degrees)
    {
        $degrees = ($degrees - 180) % 360;
        if ($degrees < 0)
            $degrees += 360;
        if (0 <= $degrees && $degrees < 22.5)
            return self::N;
        elseif (22.5 <= $degrees && $degrees < 67.5)
            return self::NE;
        elseif (67.5 <= $degrees && $degrees < 112.5)
            return self::E;
        elseif (112.5 <= $degrees && $degrees < 157.5)
            return self::SE;
        elseif (157.5 <= $degrees && $degrees < 202.5)
            return self::S;
        elseif (202.5 <= $degrees && $degrees < 247.5)
            return self::SW;
        elseif (247.5 <= $degrees && $degrees < 292.5)
            return self::W;
        elseif (292.5 <= $degrees && $degrees < 337.5)
            return self::NW;
        elseif (337.5 <= $degrees && $degrees < 360.0)
            return self::N;
        else
            return null;
    }
}
