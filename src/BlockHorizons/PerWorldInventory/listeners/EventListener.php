<?php

declare(strict_types = 1);

namespace BlockHorizons\PerWorldInventory\listeners;

use BlockHorizons\PerWorldInventory\PerWorldInventory;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\Player;

class EventListener implements Listener {

	/** @var PerWorldInventory */
	private $plugin;

	public function __construct(PerWorldInventory $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @return PerWorldInventory
	 */
	public function getPlugin(): PerWorldInventory {
		return $this->plugin;
	}

	/**
	 * @param EntityLevelChangeEvent $event
	 *
	 * @priority HIGHEST
	 */
	public function onLevelChange(EntityLevelChangeEvent $event) : void {
		$player = $event->getEntity();
		if(!($player instanceof Player) or $player->isCreative()) {
			return;
		}

		$origin = $event->getOrigin();
		$target = $event->getTarget();

		$this->getPlugin()->storeInventory($player, $origin);
		if($player->hasPermission("per-world-inventory.bypass")){
			return;
		}

		$config = $this->getPlugin()->getConfig();
		$origin_name = $origin->getFolderName();
		$target_name = $target->getFolderName();

		if(in_array($target_name, $config->getNested("Bundled-Worlds." . $origin_name, [])) or in_array($origin_name, $config->getNested("Bundled-Worlds." . $target_name, []))) {
			return;
		}

		$player->getInventory()->setContents($this->getPlugin()->getInventory($player, $target));
	}

	/**
	 * @param PlayerQuitEvent $event
	 *
	 * @priority MONITOR
	 */
	public function onQuit(PlayerQuitEvent $event) : void {
		$player = $event->getPlayer();
		$this->getPlugin()->save($player, true);
	}

	/**
	 * @param PlayerLoginEvent $event
	 *
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onPlayerLogin(PlayerLoginEvent $event) : void {
		$player = $event->getPlayer();
		if($player->isCreative() or $player->hasPermission("per-world-inventory.bypass")) {
			return;
		}

		$this->getPlugin()->load($player);
	}

	public function onInventoryTransaction(InventoryTransactionEvent $event) : void {
		$player = $event->getTransaction()->getSource();
		if($this->getPlugin()->isLoading($player)){
			$event->setCancelled();
		}
	}
}