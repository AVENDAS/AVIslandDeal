<?php

namespace avenda;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Server;
use ifteam\SimpleArea\database\user\UserProperties;
use ifteam\SimpleArea\database\minefarm\MineFarmManager;
use ifteam\SimpleArea\database\area\AreaManager;
use onebone\economyapi\EconomyAPI;

class Main extends PluginBase implements Listener {
  public $islands = [];
  public $accept = [];
  public $tag = "§l§b[AVIslandDeal]§f ";
  public function onEnable() {
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
  }
  public function onCommand (CommandSender $sender, Command $command, string $label, array $args) : bool {
    $name = strtolower($sender->getName());
    if ($label == "섬거래") {
      if (! isset ($args[0])) {
        $sender->sendMessage($this->tag . "거래 [구매자닉네임] [섬번호] [가격]");
        $sender->sendMessage($this->tag . "거래를 할땐 판매를 할 섬에서 진행해주세요! 그렇지 않으면 거래가 정상적으로 진행되지 않습니다.");
        return true;
      }
      $buyer = Server::getInstance()->getPlayerExact($args[0]);
      if ($buyer == NULL){
        $sender->sendMessage($this->tag . "해당 닉네임은 접속해 있지 않네요.");
        return false;
      }
      $island = UserProperties::getInstance()->getUserProperties($sender->getName(), 'island');
      foreach ($island as $num => $array) {//보유한 섬번호을 저장
        $this->islands[$name] = [];
        array_push($this->islands[$name], $num);
      }
      if(in_array($args[1], $this->islands[$name])) {//해당 번호가 자신의 섬인지 체크 & 거래 메세지 보내기
        $buyer = Server::getInstance()->getPlayerExact($args[0]);
        $buyer->sendMessage($this->tag . "거래를 할땐 판매를 할 섬에서 진행해주세요! 그렇지 않으면 거래가 정상적으로 진행되지 않습니다.");
        $buyer->sendMessage($this->tag . $sender->getName() . "님이 섬거래({$args[1]}번 섬)을(를) 신청하셨습니다. 구매 하시겠나요? 구매하시려면 채팅에 '네'를 쳐주세요");
        $buyer->sendMessage($this->tag . "판매가격: " . $args[2]);
        $sender->sendMessage($this->tag . "거래를 할땐 판매를 할 섬에서 진행해주세요! 그렇지 않으면 거래가 정상적으로 진행되지 않습니다.");
        $this->accpet [strtolower($args[0])] = [$args[1], $args[2], $name];
      } else {
          $sender->sendMessage($this->tag . "해당번호의 섬을 보유하고있지 않습니다.");
      }
    }
    return true;
  }
  public function Accept(PlayerChatEvent $event) {
    $player = $event->getPlayer();
    $name = strtolower($player->getName());
    $message = $event->getMessage();
    if ($message =="네") {
        $event->setCancelled(true);
      if (isset ($this->accpet[$name])) {
        $price = $this->accpet[$name][1];
        $seller = $this->accpet[$name][2];
        if (EconomyAPI::getInstance()->myMoney($player) >= $price) {
          $player->sendMessage ($this->tag . "수락하셨습니다. 거래가 성사되었습니다.");
          $player->sendMessage ($this->tag . "임대공간이라 떳다고 당황하지마세요, 섬은 제대로 양도되었습니다.");
          EconomyAPI::getInstance()->addmoney($seller, $price);
          EconomyAPI::getInstance()->reducemoney($player, $price);
          AreaManager::getInstance()->give($this->getServer()->getPlayerExact($seller), $name);//$name에게 섬을 양도함
          unset($this->accpet[$name]);
        } else {
            $player->sendMessage($this->tag . "돈이 부족합니다. 거래가 취소 되었습니다.");
            unset($this->accpet[$name]);
        }
      }
    }
  }
}
?>
