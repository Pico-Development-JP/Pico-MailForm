<?php
/**
 * Pico MailForm
 * メールフォームプラグイン
 *
 * @author TakamiChie
 * @link http://onpu-tamago.net/
 * @license http://opensource.org/licenses/MIT
 * @version 1.0
 */
class Pico_MailForm {

  private $config;
  
  // ref. http://qiita.com/mpyw/items/b4dc02ed8aa3ba7c5b5c

  /**
   * HTML特殊文字をエスケープする関数
   */
  private function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
  }

  /**
   * RuntimeExceptionを生成する関数
   * http://qiita.com/mpyw/items/6bd99ff62571c02feaa1
   */
  function e($msg, Exception &$previous = null) {
    return new RuntimeException($msg, 0, $previous);
  }

  /**
   * 例外スタックを配列に変換する関数
   * http://qiita.com/mpyw/items/6bd99ff62571c02feaa1
   */
  function exception_to_array(Exception $e) {
    do {
      $msgs[] = $e->getMessage();
    } while ($e = $e->getPrevious());
    return array_reverse($msgs);
  }

  /**
   * クライアントのデフォルト言語を取得する
   */
  private function get_default_language()
  {
    $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    return reset($languages);
  }
  
  /**
   * ランゲージ用ファイルを読み込む
   */
  private function load_language()
  {
    $l = $this->get_default_language();
    $n = __DIR__ . "/wordings.json";
    if(file_exists(__DIR__ . "/wordings.$l.json")) $n = __DIR__ . "/wordings.$l.json";
    return json_decode(file_get_contents($n), TRUE);
  }
  
  public function plugins_loaded()
  {
    session_name('ContactForm');
    @session_start();
    if (!isset($_SESSION['token'])) {
      $_SESSION['token'] = array();
    }
  }

  public function config_loaded(&$settings)
  {
    $this->config = array(
      'To'      => 'mail@example.com',
      'Title'   => 'Inquiry Mail',
      'Charset' => 'ISO-2022-JP-MS',
    );
    if(isset($settings['mailform'])){
      $this->config = $settings['mailform'] + $this->config;
    }
  }
  
  public function before_render(&$twig_vars, &$twig, &$template)
  {
    $form = array();
    /* 変数の初期化 */
    foreach (array('name', 'email', 'body', 'token') as $v) {
      $$v = trim(filter_input(INPUT_POST, $v));
    }
    $execute = false;
    if($_SERVER["REQUEST_METHOD"] == "POST"){
      $w = $this->load_language();
      // 投稿処理
      $execute = true;
      try{
        // トークンをチェック
        if (!isset($_SESSION['token'][$token])) {
          throw $this->e($w['error_expires_form'], $e);
        }
        // トークンを消費させる
        unset($_SESSION['token'][$token]);
        // 各項目チェック
        if ($name === '') {
          $e = $this->e($w['error_name_is_required'], $e);
        }
        if ($email !== ''){
          if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $e = $this->e($w['error_mail_is_invalid'], $e);
          }
        }
        if ($body === '') {
          $e = $this->e($w['error_content_is_required'], $e);
        }
        // Twig_Vers
        $form["name"] = $this->h($name);
        $form["email"] = $this->h($email);
        $form["body"] = $this->h($body);
        // 例外がここまでに1つでも発生していればスローする
        if (!empty($e)) {
          throw $e;
        }
        // 送信
        $body = $body . "\n\n-- \n" . $_SERVER['HTTP_USER_AGENT'] . "\nLanguage: " . $this->get_default_language();
        $c = $this->config["Charset"];
        if(!mb_internal_encoding('utf-8') ||
          !mail(
            $this->config["To"],
            mb_encode_mimeheader($this->config["Title"], $c),
            mb_convert_encoding($body, $c),
            implode("\r\n", array(
              "Content-Type: text/plain; charset= $c",
              "From: " . mb_encode_mimeheader($name, $c) . " <$email>"
            )),
            '-f ' . $this->config["To"]
          )
        ) {
          throw $this->e($w["error_sendmail_failed"], $e);
        }
        $form["sended"] = true;
        $form["body"] = ""; // 送信成功してるのにbodyがそのままってのはおかしい？
      }catch(Exception $e){
        $errors = array();
        foreach( $this->exception_to_array($e) as $msg ){
          array_push($errors, $this->h($msg));
        }
        $form["errors"] = $errors;
        $execute = false;
      }
    }
    
    // 表示処理
    $_SESSION['token'] = array_slice(
      array($token = sha1(mt_rand()) => true) + $_SESSION['token'], 0, 10);

    $form["token"] = $token;
    $twig_vars["mailform"] = $form;
  }

}
?>