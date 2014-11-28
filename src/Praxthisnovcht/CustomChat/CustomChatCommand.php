<?php

namespace Praxthisnovcht\CustomChat;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\level\Explosion;
use pocketmine\event\block\BlockEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3 as Vector3;
use pocketmine\math\Vector2 as Vector2;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\network\protocol\AddMobPacket;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\block\Block;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\LoginPacket;
use pocketmine\entity\FallingBlock;
use pocketmine\command\defaults\TeleportCommand;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\level\generator\Flat;
use pocketmine\level\generator\Normal;
use pocketmine\level\generator\Generator;

/**
 * Command
 *
 */
class CustomChatCommand {
	private $pgin;
	/**
	 *
	 * @param
	 *        	$pg
	 */
	public function __construct(CustomChat $pg) {
		$this->pgin = $pg;
	}

	
	/**
	 * onCommand
	 *
	 * @param CommandSender $sender        	
	 * @param Command $command        	
	 * @param unknown $label        	
	 * @param array $args        	
	 * @return boolean
	 */
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		// disable chat for all players
		if ((strtolower ( $command->getName () ) == "disablechat")) {
			$this->pgin->getConfig ()->set ( "disablechat", true ); // config.yml
			$this->pgin->getConfig ()->save ();
			$sender->sendMessage ( "disable chat for all players" );
			$this->log ( "disable chat for all players" );
			return;
		}
		// enable chat for all players
		if ((strtolower ( $command->getName () ) == "enablechat")) {
			$this->pgin->getConfig ()->set ( "disablechat", false ); // config.yml
			$this->pgin->getConfig ()->save ();
			$sender->sendMessage ( "enable chat for all players" );
			$this->log ( "enable chat for all players" );
			return;
		}
		
		// sets default prefix for new players
		if ((strtolower ( $command->getName () ) == "defprefix") && isset ( $args [0] )) {
			$playerName = $args [0];
			$p = $sender->getServer ()->getPlayerExact ( $playerName );
			if ($p == null) {
				$sender->sendMessage ( "player " . $playerName . " is not online!" );
				exit ();
			}
			$prefix = $args [1];
			$this->pgin->getConfig ()->set ( "default-player-prefix", $prefix );
			$this->pgin->getConfig ()->save ();
			$sender->sendMessage ( " all players default prefix set to " . $args [1] );
			return;
		}
		
		// sets prefix for player
		if ((strtolower ( $command->getName () ) == "setprefix") && isset ( $args [0] ) && isset ( $args [1] )) {
			$playerName = $args [0];
			$p = $sender->getServer ()->getPlayerExact ( $playerName );
			if ($p == null) {
				$sender->sendMessage ( "player " . $playerName . " is not online!" );
				exit ();
			}
			$prefix = $args [1];
			$this->pgin->getConfig ()->set ( $p->getName ().".prefix", $prefix );
			$this->pgin->getConfig ()->save ();
			
			// $p->setDisplayName($prefix.":".$name);
			$this->pgin->formatterPlayerDisplayName ( $p );
			$sender->sendMessage ( $p->getName () . " prefix set to " . $args [1] );
			return;
		}
		
		// set player's prefix to default.
		if ((strtolower ( $command->getName () ) == "delprefix") && isset ( $args [0] )) {
			$playerName = $args [0];
			$p = $sender->getServer ()->getPlayerExact ( $playerName );
			if ($p == null) {
				$sender->sendMessage ( "player " . $playerName . " is not online!" );
				exit ();
			}
			$this->pgin->getConfig ()->remove ( $p->getName () . ".prefix" );
			$this->pgin->getConfig ()->save ();
			$sender->sendMessage ( $p->getName () . " prefix set to default" );
			return;
		}
		
		// sets nick for player
		if ((strtolower ( $command->getName () ) == "setnick") && isset ( $args [0] ) && isset ( $args [1] )) {
			$playerName = $args [0];
			$p = $sender->getServer ()->getPlayerExact ( $playerName );
			if ($p == null) {
				$sender->sendMessage ( "player " . $playerName . " is not online!" );
				exit ();
			}
			$nick = $args [1];
			$this->pgin->getConfig ()->set ( $p->getName () . ".nick", $nick );
			$this->pgin->getConfig ()->save ();
			
			$this->pgin->formatterPlayerDisplayName ( $p );
			$sender->sendMessage ( $p->getName () . " nick name set to " . $args [1] );
			return;
		}
		// sets nick for player
		if ((strtolower ( $command->getName () ) == "delnick") && isset ( $args [0] ) && isset ( $args [1] )) {
			$playerName = $args [0];
			$p = $sender->getServer ()->getPlayerExact ( $playerName );
			if ($p == null) {
				$sender->sendMessage ( "player " . $playerName . " is not online!" );
				exit ();
			}
			$nick = $args [1];
			$this->pgin->getConfig ()->remove ( $p->getName () . ".nick" );
			$this->pgin->getConfig ()->save ();
			
			$this->pgin->formatterPlayerDisplayName ( $p );
			$sender->sendMessage ( $p->getName () . " nick removed " );
			return;
		}
		
		// mute player from chat
		if ((strtolower ( $command->getName () ) == "mute") && isset ( $args [0] )) {
			$playerName = $args [0];
			// check if the player exist
			$p = $sender->getServer ()->getPlayerExact ( $playerName );
			if ($p == null) {
				$sender->sendMessage ( "player " . $playerName . " is not online!" );
				exit ();
			}
			$perm = "chatmute";
			$p->addAttachment ( $this->pgin, $perm, true );
			$sender->sendMessage ( $p->getName () . " chat muted" );
			// $this->log ( "isPermissionSet " . $p->isPermissionSet ( $perm ) );
			return;
		}
		// - unmute player from chat
		if ((strtolower ( $command->getName () ) == "unmute") && isset ( $args [0] )) {
			$playerName = $args [0];
			// check if the player exist
			$p = $sender->getServer ()->getPlayerExact ( $playerName );
			if ($p == null) {
				$sender->sendMessage ( "player " . $playerName . " is not online!" );
				exit ();
			}
			$perm = "chatmute";
			foreach ( $p->getEffectivePermissions () as $pm ) {
				if ($pm->getPermission () == $perm) {
					// $this->log ( "remove attachements " . $pm->getValue () );
					$p->removeAttachment ( $pm->getAttachment () );
					$sender->sendMessage ( $p->getName () . " chat unmuted" );
					return;
				}
			}
			$sender->sendMessage ( $p->getName () . " already unmuted" );
			// $this->log ( "isPermissionSet " . $p->isPermissionSet ( $perm ) );
			return;
		}
	}
	
	
	private function hasCommandAccess(CommandSender $sender) {
		if ($sender->getName () == "CONSOLE") {
			return true;
		} elseif ($sender->isOp ()) {
			return true;
		}
		return false;
	}
	
	/**
	 * Logging util function
	 *
	 * @param unknown $msg        	
	 */
	private function log($msg) {
		$this->pgin->getLogger ()->info ( $msg );
	}
}
