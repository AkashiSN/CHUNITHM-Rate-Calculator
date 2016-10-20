<?php
/*
  +---------------------------------------------------+
  | CHUNITHM Rate Calculator             [common.php] |
  +---------------------------------------------------+
  | Copyright (c) 2015-2016 Akashi_SN                 |
  +---------------------------------------------------+
  | This software is released under the MIT License.  |
  | http://opensource.org/licenses/mit-license.php    |
  +---------------------------------------------------+
  | Author: Akashi_SN <info@akashisn.info>            |
  +---------------------------------------------------+
*/


//-----------------------------------------------------
// ヘッダー
//-----------------------------------------------------

  require('define.php');

//-----------------------------------------------------
//  切り捨て
//-----------------------------------------------------

  function Truncation($num,$n){
    $z = pow( 10 , $n );
    return ( floor(  $num* $z ) / $z );
  }

//-----------------------------------------------------
//  UserID取得
//-----------------------------------------------------

  function userid_get($u){
    $a = explode(';',$u);
    $b = '';
    for($i = 0;$i< count($a);$i++){
      $pos = strpos($a[$i],'userId');
      if ($pos !== false) {
        $b = $i;
      }
    }
    $c = explode('=',$a[$b]);
    if(isset($c[1])){
      return $c[1];
    }
    else{
      return null;
    }
  }

//-----------------------------------------------------
//  楽曲のベストスコア取得
//-----------------------------------------------------

  //level = 19902:expert,level = 19903:master
  function BestScore_get($userid,$level){
    $url = 'https://chunithm-net.com/ChuniNet/GetUserMusicApi';
    $data = array(
      'level' => $level,
      'userId' => $userid,
    );
    $options = array(
    'http' => array(
      'method'  => 'POST',
      'content' => json_encode( $data ),
      'header'=>  "Content-Type: application/json\r\n" .
                  "Accept: application/json\r\n"
      )
    );
    $context  = stream_context_create( $options );
    $score = file_get_contents( $url, false, $context );
    return json_decode( $score , true );
  }

//-----------------------------------------------------
//  最近のプレイ履歴の取得
//-----------------------------------------------------

  function Recent_score_get($userid){
    $url = 'https://chunithm-net.com/ChuniNet/GetUserPlaylogApi';
    $data = array(
      'userId' => $userid,
    );
    $options = array(
    'http' => array(
      'method'  => 'POST',
      'content' => json_encode( $data ),
      'header'=>  "Content-Type: application/json\r\n" .
                  "Accept: application/json\r\n"
      )
    );
    $context  = stream_context_create( $options );
    $score = file_get_contents( $url, false, $context );
    return json_decode( $score , true );
  }

//-----------------------------------------------------
//  ユーザーデータの詳細取得
//-----------------------------------------------------

  function Rate_get($userid){
    $url = 'https://chunithm-net.com/ChuniNet/GetUserInfoApi';
    $data = array(
      'userId' => $userid,
      'friendCode' => 0,
      'fileLevel' => 1,
    );
    $options = array(
    'http' => array(
      'method'  => 'POST',
      'content' => json_encode( $data ),
      'header'=>  "Content-Type: application/json\r\n" .
                  "Accept: application/json\r\n"
      )
    );
    $context  = stream_context_create( $options );
    $rate = file_get_contents( $url, false, $context );
    return json_decode( $rate , true );
  }

//-----------------------------------------------------
//  フレンドコード取得
//-----------------------------------------------------

  function friendCode_get($userid){
    $url = 'https://chunithm-net.com/ChuniNet/GetUserFriendlistApi';
    $data = array(
      'userId' => $userid,
      'state' => 4,
    );
    $options = array(
    'http' => array(
      'method'  => 'POST',
      'content' => json_encode( $data ),
      'header'=>  "Content-Type: application/json\r\n" .
                  "Accept: application/json\r\n"
      )
    );
    $context  = stream_context_create( $options );
    $rate = file_get_contents( $url, false, $context );
    return json_decode( $rate , true );
  }

//-----------------------------------------------------
//  データーベースに登録
//-----------------------------------------------------

  function UserData_set($FriendCode,$UserName,$Json){
    try {
      $count = 0;
      $hash =  hash_hmac('sha256', $FriendCode, false);
      $pdo = new PDO(DNS,USER,PASS,array(PDO::ATTR_EMULATE_PREPARES => false));
	    $sql = 'SELECT * FROM `User` WHERE `Hash` = :Hash';
	    $stmt = $pdo -> prepare($sql);
	    $stmt->bindParam(':Hash', $hash, PDO::PARAM_STR);
	   	$stmt->execute();
      foreach ($stmt as $row) {
        $count++;
      }
      if($count === 0){
        $sql = 'INSERT INTO `User`(`Hash`, `FriendCode`, `UserName`, `Json`) VALUES (:Hash, :FriendCode, :UserName, :Json)';
        $stmt = $pdo -> prepare($sql);
        $stmt->bindParam(':Hash', $hash, PDO::PARAM_STR);
        $stmt->bindParam(':FriendCode', $FriendCode, PDO::PARAM_STR);
        $stmt->bindParam(':UserName', $UserName, PDO::PARAM_STR);
        $stmt->bindParam(':Json', $Json, PDO::PARAM_STR);
        $stmt->execute();
      }else{
        $sql = 'DELETE FROM `User` WHERE `Hash` =:Hash;';
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':Hash', $hash, PDO::PARAM_STR);
        $stmt->execute();

        $sql = 'INSERT INTO `User`(`Hash`, `FriendCode`, `UserName`, `Json`)  VALUES (:Hash, :FriendCode, :UserName, :Json)';
        $stmt = $pdo -> prepare($sql);
        $stmt->bindParam(':Hash', $hash, PDO::PARAM_STR);
        $stmt->bindParam(':FriendCode', $FriendCode, PDO::PARAM_STR);
        $stmt->bindParam(':UserName', $UserName, PDO::PARAM_STR);
        $stmt->bindParam(':Json', $Json, PDO::PARAM_STR);
        $stmt->execute();
      }
    } catch (PDOException $e) {
      exit('データベース接続失敗。'.$e->getMessage());
    }
  }

//-----------------------------------------------------
//  データーベースから参照
//-----------------------------------------------------

  function UserData_show($Hash){
    $pdo = new PDO(DNS,USER,PASS,array(PDO::ATTR_EMULATE_PREPARES => false));
    $sql = 'SELECT * FROM `User` WHERE `Hash` = :Hash';
    $stmt = $pdo -> prepare($sql);
    $stmt->bindParam(':Hash', $Hash, PDO::PARAM_STR);
   	$stmt->execute();
    foreach ($stmt as $row) {
      return $row['Json'];
    }
    return null;
  }

//-----------------------------------------------------
//  スコアからランク
//-----------------------------------------------------

  function Score_to_rank($score){
    if($score >= 1007500){
      return 'sss';
    }
    else if($score >= 1000000){
      return 'ss';
    }
    else if($score >= 975000){
      return 's';
    }
    else if($score >= 950000){
      return 'aaa';
    }
    else if($score >= 925000){
      return 'aa';
    }
    else if($score >= 900000){
      return 'a';
    }
    else if($score >= 800000){
      return 'bbb';
    }
    else if($score >= 700000){
      return 'bb';
    }
    else if($score >= 600000){
      return 'b';
    }
    else if($score >= 500000){
      return 'c';
    }
    else if($score >= 0){
      return 'd';
    }
    else{
      return null;
    }
  }

//-----------------------------------------------------
//  スコアからレート
//-----------------------------------------------------

  function score_to_rate($score,$base_rate){
    if($score >= 1007500){
      return (double)($base_rate+2);
    }
    else if($score >= 1005000){
      return (double)($base_rate+1.5+($score-1005000)*10/50000);
    }
    else if($score >= 1000000){
      return (double)($base_rate+1+($score-1000000)*5/50000);
    }
    else if($score >= 975000){
      return (double)($base_rate+($score-975000)*2/50000);
    }
    else if($score >= 950000){
      return (double)($base_rate-1.5+($score-950000)*3/50000);
    }
    else if($score >= 925000){
      return (double)($base_rate-3+($score-925000)*3/50000);
    }
    else if($score >= 900000){
      return (double)($base_rate-5+($score-900000)*4/50000);
    }
    else if($score >= 800000){
      return (double)($base_rate-7.5+($score-800000)*1.25/50000);
    }
    else if($score >= 700000){
      return (double)($base_rate-8.5+($score-700000)*0.5/50000);
    }
    else if($score >= 600000){
      return (double)($base_rate-9+($score-600000)*0.25/50000);
    }
    else if($score >= 500000){
      return (double)($base_rate-13.7+($score-500000)*2.35/50000);
    }
    else{
      return null;
    }
  }

//-----------------------------------------------------
//  レートからスコア
//-----------------------------------------------------

  function rate_to_score($rate,$base_rate){
    if($rate-$base_rate >= 2){
      return 0;
    }
    else if($rate-$base_rate >= 1.5){
      return floor(-50000/10*($base_rate+1.5-$rate)+1005000);
    }
    else if($rate-$base_rate >= 1){
      return floor(-50000/5*($base_rate+1-$rate)+1000000);
    }
    else if($rate-$base_rate >= 0){
      return floor(-50000/2*($base_rate-$rate)+975000);
    }
    else if($rate-$base_rate >= -1.5){
      return floor(-50000/3*($base_rate-1.5-$rate)+950000);
    }
    else if($rate-$base_rate >= -3){
      return floor(-50000/3*($base_rate-3-$rate)+925000);
    }
    else if($rate-$base_rate >= -5){
      return floor(-50000/4*($base_rate-5-$rate)+900000);
    }
    else if($rate-$base_rate >= -7.5){
      return floor(-50000/1.25*($base_rate-7.5-$rate)+800000);
    }
    else if($rate-$base_rate >= -8.5){
      return floor(-50000/0.5*($base_rate-8.5-$rate)+700000);
    }
    else if($rate-$base_rate >= -9){
      return floor(-50000/0.25*($base_rate-9-$rate)+600000);
    }
    else if($rate-$base_rate >= -13.7){
      return floor(-50000/2.35*($base_rate-13.7-$rate)+500000);
    }
    else{
      return null;
    }
  }

//--------------------------------------------------------------------
// ユーザーデータの表示
//--------------------------------------------------------------------
/*
function UserRateDisp($UserData){
  var $UserRate = $UserData["User"];
  var $UserInfo = $UserData["Userinfo"];
  var $frame = ["normal", "copper", "silver", "gold", "platina"];
  var $characterFrame = ["normal", "copper", "silver", "gold", "gold", "platina"];
  var $elements = `
  <div class="w420 box_player clearfix">
    <div id="UserCharacter" class="player_chara" style='background-image:url("https://chunithm-net.com/mobile/common/images/charaframe_`.$characterFrame[parseInt(parseInt($UserInfo["characterLevel"])/5)].`.png");margin-top: 10px;'>
      <img id="characterFileName" src="https://chunithm-net.com/mobile/`.$UserInfo["characterFileName"].`">
    </div>
    <div class="box07 player_data">
      <div id="UserHonor" class="player_honor" style='background-image:url("https://chunithm-net.com/mobile/common/images/honor_bg_`.$frame[parseInt($UserInfo["trophyType"])]. `.png")'>
        <div class="player_honer_text_view">
          <div id="HonerText" class="player_honer_text">`.$UserInfo["trophyName"].`</div>
        </div>
      </div>`;
  if($UserInfo["reincarnationNum"] > 0){
    $elements += `
      <div id="UserReborn" class="player_reborn">`;
    $elements += $UserInfo["reincarnationNum"];
  }else{
    $elements += `
      <div id="UserReborn" class="player_reborn_0">`;                  
  }
  $elements += `
      </div>
      <div class="player_name">
        <div class="player_lv">
          <span class="font_small mr_5">Lv.</span><span id="UserLv">`.$UserInfo["level"].`</span></div><span id="UserName">`.$UserInfo["userName"].`</span>
        </div>
        <div class="player_rating" id="player_rating">BEST枠 : <span id="UserRating">`.$UserRate["BestRate"].toFixed(2).`</span> / <span>MAX</span> <span id="UserRating">`.$UserRate["MaxRate"].toFixed(2) + `</span><br><div style="margin-top:5px;">RECENT枠 :<span id="UserRating">`.$UserRate["RecentRate-1"].toFixed(2).`</span> / <span>表示レート</span><span id="UserRating">`.UserRate["DispRate"].toFixed(2).`</span></div>
      </div>
    </div>
    <div id="tweet" style="margin-top: 10px;"></div>
    <div style="margin-top: 0px" class="more w400" onclick="window.open('https://akashisn.info/?page_id=52#notice', '_blank');"><a href="JavaScript:void(0);">注意</a></div>
  </div>`;  
}

?>
