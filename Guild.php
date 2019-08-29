<?php

/**
 * @name Guild
 * @main Securti\guild\Guild
 * @author ["Securti"]
 * @version 0.1
 * @api 3.9.0
 * @description This plugin is made by Securti. :3
 */

namespace Securti\guild;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;

use pocketmine\Player;
use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\item\Item;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Guild extends PluginBase implements Listener {

  private $data;

  public static $instance;

  public static function getInstance(){

    return self::$instance;
  }
  public function onLoad(){
    
    self::$instance = $this;
  }
  public function onEnable(){

    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    
    $a = new PluginCommand("길드", $this);
    $a->setPermission("");
    $a->setUsage("/길드");
    $a->setDescription("길드 관련 명령어입니다");
    $this->getServer()->getCommandMap()->register($this->getDescription()->getName(), $a);
    
    $a = new PluginCommand("초대", $this);
    $a->setPermission("");
    $a->setUsage("/초대");
    $a->setDescription("길드 초대 관련 명령어입니다");
    $this->getServer()->getCommandMap()->register($this->getDescription()->getName(), $a);
    
    @mkdir($this->getDataFolder());
    $this->TeamData = new Config($this->getDataFolder() . "TeamData.yml", Config::YAML);
    $this->data = $this->TeamData->getAll();
  }
  public function onCommand(CommandSender $sender, Command $command, string $label, array $array) :bool{
  
    $command = $command->getName();
    $player = $sender;
    $name = strtolower($player->getName());
    
    $prefix = "§l§e[§f알림§e] §f";
    
    if($command === "초대"){
    
      if(count($array) === 1){
      
        if($array[0] === "수락"){
        
          if($this->data[$name]["초대"] !== "미존재"){
          
            $this->data[$name]["등급"] = "길드원";
            $this->data[$name]["여부"] = "가입";
            $this->data[$name]["팀명"] = $this->data[$name]["초대"];
            
            $this->data["server"][$this->data[$name]["초대"]]["인원"] += 1;
            $this->data["server"][$this->data[$name]["초대"]]["전체인원"] += 1;
            
            $this->data[$name]["개인번호"] = $this->data["server"][$this->data[$name]["초대"]]["인원"];
            $this->data["server"][$this->data[$name]["초대"]]["목록"][$this->data[$name]["개인번호"]] = $name;
            //$this->data["server"][$this->data[$name]["초대"]]["여부"][$this->data[$name]["개인번호"]] = "가입";
            
            $this->data[$name]["초대"] = "미존재";
            
            $player->sendMessage($prefix."길드 초대를 수락했습니다");
          }
          else{
          
            $player->sendMessage($prefix."길드의 초대를 받지 않았습니다");
          }
        }
        elseif($array[0] === "거절"){
        
          if($this->data[$name]["초대"] !== "미존재"){
          
            $this->data[$name]["초대"] = "미존재";
            
            $player->sendMessage($prefix."길드 초대를 거절했습니다");
          }
          else{
          
            $player->sendMessage($prefix."길드의 초대를 받지 않았습니다");
          }
        }
        else{
        
          $player->sendMessage($prefix."/초대 <수락, 거절> - 길드 초대를 관리합니다");
        }
      }
      else{
      
        $player->sendMessage($prefix."/초대 <수락, 거절> - 길드 초대를 관리합니다");
      }
    }
    elseif($command === "길드"){
      
      if($this->data[$name]["여부"] !== "가입"){
      	
        $this->UI1($player);
      }
      else{
      
        if($this->data[$name]["등급"] === "길드장"){
          	
          $this->UI2($player);
        }
        elseif($this->data[$name]["등급"] === "부길드장"){
          
          $this->UI3($player);
        }
        elseif($this->data[$name]["등급"] === "길드원"){
          
          $this->UI4($player);
        }
      }
    }
    return true;
  }
  public function getUI(DataPacketReceiveEvent $e){

    $pack = $e->getPacket();
    $player = $e->getPlayer();
    
    $name = strtolower($player->getName());
    
    $prefix = "§l§e[§f알림§e] §f";

    if($pack instanceof ModalFormResponsePacket and $pack->formId == 8890){

      $button = json_decode($pack->formData, true);
      
      if($button == 1){
        
        if($this->data[$name]["초대"] = "미존재"){
        
          $this->UI5($player);
        }
        else{
        
          $player->sendMessage($prefix."길드를 생성하려면 이전에 온 초대를 수락또는 거절해야합니다");
        }
      }
    }
    elseif($pack instanceof ModalFormResponsePacket and $pack->formId == 8891){

      $button = json_decode($pack->formData, true);
      
      if($button == 1){
      
        $this->UI6($player);
      }
      elseif($button == 2){
      
        $this->UI7($player);
      }
      elseif($button == 3){
      
        $this->UI8($player);
      }
      elseif($button == 4){
      
        $this->UI9($player);
      }
      elseif($button == 5){
      
        $team = $this->data[$name]["팀명"];
        
        if(isset($this->data["server"][$team]["여부"])){
        
          if($this->data["server"][$team]["여부"] === "존재"){
          
            if($this->data["server"][$team]["전체인원"] === 1){
            
              $this->data[$name]["등급"] = "무소속";
              $this->data[$name]["여부"] = "무소속";
              $this->data[$name]["개인번호"] = 0;
              $this->data[$name]["팀명"] = "무소속";
            
              $this->data["server"][$team]["여부"] = "삭제";
              $this->data["server"][$team]["전체인원"] = 0;
              
              $player->sendMessage($prefix."성공적으로 길드를 해체하였습니다");
            }
            else{
        
              $player->sendMessage($prefix."길드를 해체하기 위해서는 길드원이 없어야 합니다");
            }
          }
        }
      }
    }
    elseif($pack instanceof ModalFormResponsePacket and $pack->formId == 8892){

      $button = json_decode($pack->formData, true);
      
      if($button == 1){
      
        $this->UI6($player);
      }
      elseif($button == 2){
      
        $this->UI7($player);
      }
      elseif($button == 3){
      
        $thus->UI8($player);
      }
      elseif($button == 4){
      
        $player->sendMessage($prefix."성공적으로 길드에서 탈퇴하였습니다");
        
        $count = $this->data[$name]["개인번호"];
        $team = $this->data[$name]["팀명"];

        $this->data["server"][$team]["전체인원"] -= 1;
                
        $this->data[$name]["팀명"] = "무소속";
        $this->data[$name]["여부"] = "무소속";
        $this->data[$name]["초대"] = "미존재";
        $this->data[$name]["등급"] = "무소속";
        $this->data[$name]["개인번호"] = 0;
      }
    }
    elseif($pack instanceof ModalFormResponsePacket and $pack->formId == 8893){

      $button = json_decode($pack->formData, true);
      
      if($button == 1){
      
        $this->UI8($player);
      }
      elseif($button == 2){
      
        $player->sendMessage($prefix."성공적으로 길드에서 탈퇴하였습니다");
        
        $count = $this->data[$name]["개인번호"];
        $team = $this->data[$name]["팀명"];

        $this->data["server"][$team]["전체인원"] -= 1;
                
        $this->data[$name]["팀명"] = "무소속";
        $this->data[$name]["여부"] = "무소속";
        $this->data[$name]["초대"] = "미존재";
        $this->data[$name]["등급"] = "무소속";
        $this->data[$name]["개인번호"] = 0;
      }
    }
    elseif($pack instanceof ModalFormResponsePacket and $pack->formId == 8894){

      $button = json_decode($pack->formData, true);
     
      if($button[0] == null){
      
        $player->sendMessage($prefix."모든 정보를 정확히 입력해주세요");
      }
      else{
        
        $text = TextFormat::clean($button[0]);
        /*$text = str_replace("§0", "", $text);
        $text = str_replace("§1", "", $text);
        $text = str_replace("§2", "", $text);
        $text = str_replace("§3", "", $text);
        $text = str_replace("§4", "", $text);
        $text = str_replace("§5", "", $text);
        $text = str_replace("§6", "", $text);
        $text = str_replace("§7", "", $text);
        $text = str_replace("§8", "", $text);
        $text = str_replace("§9", "", $text);
        $text = str_replace("§e", "", $text);
        $text = str_replace("§r", "", $text);
        $text = str_replace("§o", "", $text);
        $text = str_replace("§a", "", $text);
        $text = str_replace("§d", "", $text);
        $text = str_replace("§f", "", $text);
        $text = str_replace("§k", "", $text);
        $text = str_replace("§l", "", $text);
        $text = str_replace("§c", "", $text);
        $text = str_replace("§b", "", $text);
        $text = str_replace("§n", "", $text);
        $text = str_replace("§m", "", $text);*/
        
        if(mb_strlen($text, "utf-8") > 10 or mb_strlen($text, "utf-8") < 1){
        
          $player->sendMessage($prefix."길드 이름이 너무 길거나 짧습니다");
        }
        else{
          
          if(isset($this->data["server"][$button[0]]["여부"])){
          
            if($this->data["server"][$button[0]]["여부"] === "존재"){
            	
              $player->sendMessage($prefix.$button."§f은(는) 이미 존재하는 길드 입니다.");
              
              return true;
            }
          }
          
          $item = Item::get(399, 1, 20);
          
          if($player->getInventory()->contains($item)){
            
            $this->data[$name]["등급"] = "길드장";
            $this->data[$name]["여부"] = "가입";
            $this->data[$name]["개인번호"] = 1;
            $this->data[$name]["팀명"] = $button[0];
            
            $this->data["server"][$button[0]]["여부"] = "존재";
            $this->data["server"][$button[0]]["인원"] = 1;
            $this->data["server"][$button[0]]["전체인원"] = 1;
            $this->data["server"][$button[0]]["목록"]["1"] = $name;
            //$this->data["server"][$button[0]]["여부"]["1"] = "가입";
            
            $player->getInventory()->removeItem($item);
            $player->sendMessage($prefix."길드를 생성하였습니다");
           }
           else{
        
            $player->sendMessage($prefix."길드를 생성하기 위한 아이템이 부족합니다!");
          }
        }
      }
    }
    elseif($pack instanceof ModalFormResponsePacket and $pack->formId == 8895){

      $button = json_decode($pack->formData, true);
      
      if($this->data[$name]["등급"] === "길드장" or $this->data[$name]["등급"] === "부길드장"){
      	
        if($button[0] == null){
        
          $player->sendMessage($prefix."모든 정보를 정확히 입력해주세요");
        }
        else{
        
          $input = strtolower($button[0]);
          
          if(!isset($this->data[$input]["저장"])){
          
            $player->sendMessage($prefix."§e".$input."§f은(는) 접속기록이 없는 유저입니다");
          }
          else{
          
            $input2 = $this->getServer()->getPlayer($button[0]);
          	
            if($this->data[$input]["팀명"] === $this->data[$name]["팀명"]){
          	
              $player->sendMessage($prefix."§e".$input."§f은(는) 이미 같은 길드에 속해있습니다");
            }
            else{
          
              if($this->data[$input]["여부"] === "가입"){
            
                $player->sendMessage($prefix."§e".$input."§f은(는) 이미 다른 길드에 가입되어 있습니다");
              }
              else{
                  
                $input = $this->getServer()->getPlayer($button[0]);
                  
                if(is_null($input2)){
                
                  $player->sendMessage($prefix."§e".$input."§f은(는) 오프라인 상태입니다");
                }
                else{
                	
                  $input = strtolower($this->getServer()->getPlayer($button[0])->getName());
                  $input2 = $this->getServer()->getPlayer($button[0]);
              
                  if($this->data[$input]["초대"] === "미존재"){
                
                    $team = $this->data[$name]["팀명"];
                  
                    $this->data[$input]["초대"] = $team;
                  
                    $player->sendMessage($prefix."§e".$input."§f님 에게 길드 초대를 보냈습니다");
                    $input2->sendMessage($prefix.$name."님의 ".$team." §r§f길드에서 초대가 왔습니다");
                    $input2->sendMessage($prefix."초대를 수락하려면 </초대 수락> 을 거절하려면 </초대 거절> 을 입력해주세요"); 
                  }
                  else{
                
                    $player->sendMessage($prefix."§e".$input."§f은(는) 이미 다른 길드의 초대를 받았습니다");
                    $player->sendMessage($prefix."길드에 초대하기 위해서는 이전 초대를 거절해야합니다");
                  }
                }
              }
            }
          }
        }
      }
      else{
      
        $player->sendMessage($prefix."해당 기능을 사용할 권한이 없습니다");
      }
    }
    elseif($pack instanceof ModalFormResponsePacket and $pack->formId == 8896){

      $button = json_decode($pack->formData, true);
      
      if($this->data[$name]["등급"] === "길드장" or $this->data[$name]["등급"] === "부길드장"){
      	
        if($button[0] == null){
        
          $player->sendMessage_($prefix."모든 정보를 정확히 입력해주세요");
        }
        else{
        
          $input = strtolower($button[0]);

          if(!isset($this->data[$input]["저장"])){

            $player->sendMessage($prefix."§e".$input."§f은(는) 접속기록이 없는 유저입니다");
          }
          else{
          
            if($this->data[$input]["팀명"] !== $this->data[$name]["팀명"]){
          	
              $player->sendMessage($prefix."§e".$input."§f은(는) 같은 길드에 속해있지 않습니다");
            }
            else{
          
              if($this->data[$input]["등급"] === "길드장"){
            
              $player->sendMessage($prefix."길드장은 강퇴가 불가능합니다");
              }
              else{
            
                if($name === $input){
                
                  $player->sendMessage($prefix."자기 자신은 강퇴가 불가능합니다");
                }
                else{
                
                  $count = $this->data[$input]["개인번호"];
                  $team = $this->data[$name]["팀명"];

                  $this->data["server"][$team]["전체인원"] -= 1;
                
                  $this->data[$input]["팀명"] = "무소속";
                  $this->data[$input]["여부"] = "무소속";
                  $this->data[$input]["초대"] = "미존재";
                  $this->data[$input]["등급"] = "무소속";
                  $this->data[$input]["개인번호"] = 0;
                
                  $player->sendMessage($prefix."§e".$input."§f님을 길드에서 내보냈습니다");
               }
              }
            }
          }
        }
      }
      else{
      
        $player->sendMessage($prefix."해당 기능을 사용할 권한이 없습니다");
      }
    }
    elseif($pack instanceof ModalFormResponsePacket and $pack->formId == 8898){

      $button = json_decode($pack->formData, true);
      
      if($this->data[$name]["등급"] === "길드장" or $this->data[$name]["등급"] === "부길드장"){
      	
        if($button[0] == null){
        
          $player->sendMessage($prefix."모든 정보를 정확히 입력해주세요");
        }
        else{
        
          $input = strtolower($button[0]);
          
          if(!isset($this->data[$input]["저장"])){
          
            $player->sendMessage($prefix."§e".$input."§f은(는) 접속기록이 없는 유저입니다");
            
            return true;
          }
          
          if($this->data[$input]["팀명"] !== $this->data[$name]["팀명"]){
          	
            $player->sendMessage($prefix."§e".$input."§f은(는) 같은 길드에 속해있지 않습니다");
          }
          else{
          
            if($this->data[$input]["등급"] === "길드장"){
            
            $player->sendMessage($prefix."길드장은 부길드장 선택이 불가능합니다");
            }
            else{
            
              if($this->data[$input]["등급"] === "부길드장"){
                
                $player->sendMessage($prefix."§e".$input."은(는) 이미 부길드장입니다");
              }
              else{
              
                $this->data[$input]["등급"] = "부길드장";
                
                $player->sendMessage($prefix."§e".$input."§f님을 부길드장으로 임명했습니다");
              }
            }
          }
        }
      }
      else{
      
        $player->sendMessage($prefix."해당 기능을 사용할 권한이 없습니다");
      }
    }
  }
  public function onJoin(PlayerJoinEvent $e){
    
    $player = $e->getPlayer();
    $name = strtolower($player->getName());
    
    if(!isset($this->data[$name]["저장"])){
    
      $this->data[$name]["저장"] = "활성화";
      $this->data[$name]["등급"] = "무소속";
      $this->data[$name]["초대"] = "미존재"; 
      $this->data[$name]["여부"] = "무소속";
      $this->data[$name]["개인번호"] = 0;
      $this->data[$name]["팀명"] = "무소속";
      
    }
  }
  public function onDamage(EntityDamageEvent $e){

    $prefix = "§l§e[§f알림§e] §f";

    if($e instanceof EntityDamageByEntityEvent){

      $damager = $e->getDamager();
      $entity = $e->getEntity();

      if($damager instanceof Player and $entity instanceof Player){

        $name1 = strtolower($damager->getName());
        $name2 = strtolower($entity->getName());

        if($this->data[$name1]["여부"] === "가입" and $this->data[$name2]["여부"] === "가입"){

          if($this->data[$name1]["팀명"] === $this->data[$name2]["팀명"]){

            $damager->sendTip($prefix."같은 길드원은 공격이 불가능합니다");

            $e->setCancelled();
          }
        }
      }
    }
  }
  public function UI1(Player $player){

    $prefix = "§l§e· §f";

    $encode = json_encode([

      "type" => "form",   
      "title" => "§l§e[ §f길드 UI §e]",    
      "content" => $prefix."원하시는 작업을 골라주세요",
      "buttons" => [
              [
                  "text" => $prefix."창 닫기"
              ],
              [
                  "text" => $prefix."길드 생성하기"
              ]
          ]
      ]);
    
    $pack = new ModalFormRequestPacket();
    $pack->formId = 8890;
    $pack->formData = $encode;
    $player->dataPacket($pack);
  }
  public function UI2(Player $player){

    $prefix = "§l§e· §f";

    $encode = json_encode([

      "type" => "form",   
      "title" => "§l§e[ §f길드 관리 §e]",    
      "content" => $prefix."원하시는 작업을 골라주세요",
      "buttons" => [
              [
                  "text" => $prefix."창 닫기"
              ],
              [
                  "text" => $prefix."길드원 초대"
              ],
              [
                  "text" => $prefix."길드원 강퇴"
              ],
              [
                  "text" => $prefix."길드원 목록"
              ],
              [
                  "text" => $prefix."부길드장 선택"
              ],
              [
                  "text" => $prefix."길드 해체"
              ]
          ]
      ]);
    
    $pack = new ModalFormRequestPacket();
    $pack->formId = 8891;
    $pack->formData = $encode;
    $player->dataPacket($pack);
  }
  public function UI3(Player $player){

    $prefix = "§l§e· §f";

    $encode = json_encode([

      "type" => "form",   
      "title" => "§l§e[ §f길드 관리 §e]",    
      "content" => $prefix."원하시는 작업을 골라주세요",
      "buttons" => [
              [
                  "text" => $prefix."창 닫기"
              ],
              [
                  "text" => $prefix."길드원 초대"
              ],
              [
                  "text" => $prefix."길드원 강퇴"
              ],
              [
                  "text" => $prefix."길드원 목록"
              ],
              [
                  "text" => $prefix."길드 나가기"
              ]
          ]
      ]);
    
    $pack = new ModalFormRequestPacket();
    $pack->formId = 8892;
    $pack->formData = $encode;
    $player->dataPacket($pack);
  }
  public function UI4(Player $player){

    $prefix = "§l§e· §f";

    $encode = json_encode([

      "type" => "form",   
      "title" => "§l§e[ §f길드 메뉴 §e]",    
      "content" => $prefix."원하시는 작업을 골라주세요",
      "buttons" => [
              [
                  "text" => $prefix."창 닫기"
              ],
              [
                  "text" => $prefix."길드원 목록"
              ],
              [
                  "text" => $prefix."길드 나가기"
              ]
          ]
      ]);
    
    $pack = new ModalFormRequestPacket();
    $pack->formId = 8893;
    $pack->formData = $encode;
    $player->dataPacket($pack);
  }
  public function UI5(Player $player){

    $prefix = "§l§e· §f";

    $encode = json_encode([

      "type" => "custom_form",   
      "title" => "§l§e[ §f길드 생성 §e]",
      "content" => [
              [
                  "type" => "input",
                  "text" => $prefix."길드의 이름을 적어주세요\n글자 수 제한 : 10 (색코드 미포함)",
                  "default" => ""
              ]
          ]
      ]);
    
    $pack = new ModalFormRequestPacket();
    $pack->formId = 8894;
    $pack->formData = $encode;
    $player->dataPacket($pack);
  }
  public function UI6(Player $player){

    $prefix = "§l§e· §f";

    $encode = json_encode([

      "type" => "custom_form",   
      "title" => "§l§e[ §f길드원 초대 §e]",
      "content" => [
              [
                  "type" => "input",
                  "text" => $prefix."초대할 길드원의 이름을 적어주세요",
                  "default" => ""
              ]
          ]
      ]);
    
    $pack = new ModalFormRequestPacket();
    $pack->formId = 8895;
    $pack->formData = $encode;
    $player->dataPacket($pack);
  }
  public function UI7(Player $player){

    $prefix = "§l§e· §f";

    $encode = json_encode([

      "type" => "custom_form",   
      "title" => "§l§e[ §f길드원 강퇴 §e]",
      "content" => [
              [
                  "type" => "input",
                  "text" => $prefix."강퇴할 길드원의 이름을 적어주세요",
                  "default" => ""
              ]
          ]
      ]);
    
    $pack = new ModalFormRequestPacket();
    $pack->formId = 8896;
    $pack->formData = $encode;
    $player->dataPacket($pack);
  }
  public function UI8(Player $player){

    $prefix = "§l§e· §f";
    $text = "";
    
    $name = strtolower($player->getName());
    
    $team = $this->data[$name]["팀명"];
    $count = $this->data["server"][$team]["인원"];
    
    for($i = 1; $i < $count +1; $i++){
    
      if(isset($this->data["server"][$team]["목록"][$i])){

        $name2 = $this->data["server"][$team]["목록"][$i];
      
        if($this->data[$name2]["여부"] === "가입" and $this->data[$name2]["팀명"] === $team){
        
          $input = $this->data["server"][$team]["목록"][$i];
          
          $text = $text."\n".$prefix.$input." §e| §f".$this->data[$name2]["등급"];
        }
      }
    }

    $encode = json_encode([

      "type" => "custom_form",   
      "title" => "§l§e[ §f길드원 목록 §e]",
      "content" => [
              [
                  "type" => "label",
                  "text" => $text,
              ]
          ]
      ]);
    
    $pack = new ModalFormRequestPacket();
    $pack->formId = 8897;
    $pack->formData = $encode;
    $player->dataPacket($pack);
  }
  public function UI9(Player $player){

    $prefix = "§l§e· §f";

    $encode = json_encode([

      "type" => "custom_form",   
      "title" => "§l§e[ §f부길드장 임명 §e]",
      "content" => [
              [
                  "type" => "input",
                  "text" => $prefix."부길드장으로 임명할 길드원의 이름을 적어주세요",
                  "default" => ""
              ]
          ]
      ]);
    
    $pack = new ModalFormRequestPacket();
    $pack->formId = 8898;
    $pack->formData = $encode;
    $player->dataPacket($pack);
  }
  public function getTeam(Player $player){

    $name = strtolower($player->getName());

    if($this->data[$name]["여부"] === "가입"){

      return $this->data[$name]["팀명"];
    }
    else{

      return "무소속";
    }
  }
  public function getGuild(Player $player){
  
    $name = strtolower($player->getName());
    
    if(!isset($this->data[$name]["팀명"])){
    
      return "Undefined";
    }
    else{
    
      return $this->data[$name]["팀명"];
    }
  }
  public function save(){
  
    $this->TeamData->setAll($this->data); 
    $this->TeamData->save();
  }
  public function onDisable(){
  	
    $this->save();
  }
}