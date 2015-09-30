# pico-mailform
Pico Plugin:メールフォームを作成、メールを送信する

以下サイトの記事を参考にしました。
 * http://qiita.com/mpyw/items/b4dc02ed8aa3ba7c5b5c

## 利用条件

 * PHPのmail()関数が利用できる状態であること(sendmailが使える状態であれば、問題ないはず)

## 使用方法

 1. テンプレートとして、お問い合わせフォームを作成する。actionを空欄に設定すること。
 2. コンフィグオプションの値を設定する(Toだけは必須です)。
 3. enjoy
 
### メールフォームテンプレートの例

```php
<section class="card" id="inquiry">
  {% if not mailform.errors is empty %}
    <div class="errorinfo">
      <p>入力内容に誤りがあります。お手数ですが再度フォームを入力してください。</p>
      <ul>
        {% for err in mailform.errors %}
          <li>{{err}}</li>
        {% endfor %}
      </ul>
    </div>
  {% endif %}
  {% if mailform.sended %}
    <div class="successinfo">
      <p>お問い合わせを承りました。</p>
    </div>
  {% endif %}
  <form action="" method="post" enctype="application/x-www-form-urlencoded">
    <div class="formctrl">
      <label for="mf_name">あなたのお名前</label>
      <input type="text" id="mf_name" name="name" value="{{mailform.name}}" required />
    </div>
    <div class="formctrl">
      <label for="mf_email">あなたのメールアドレス</label>
      <input type="email" id="mf_email" name="email" value="{{mailform.email}}" />
    </div>
    <div class="formctrl">
      <label for="mf_body">内容</label>
      <textarea id="mf_body" name="body" required>{{mailform.body}}</textarea>
    </div>
    <div class="formsubmit">
      <input type="submit" value="送信"></input>
    </div>
    <input type="hidden" name="token" value="{{mailform.token}}" /> <!-- 重要 -->
  </form>
</section>
```

メールフォームには基本的に制限がありませんが、最後の`<input type="hidden" name="token" value="{{mailform.token}}" />`だけは必須です。かならずフォーム内に指定してください。

## 記事に追加する値
なし
 
##  追加するTwig変数
 * mailform:プラグインに関する情報
  * token: メールフォーム送信時のトークン。必ずフォームのhidden要素などで指定してください。
  * name: メールフォーム送信時の送信者名。メールフォームのvalue等に指定します。
  * email: メールフォーム送信時の送信者メールアドレス。
  * body: メールフォーム送信時のメール本文。メール送信に成功した場合は空白となります。
  * errors: 送信エラーがあった場合、それに関する文言が配列で格納されます。
  * sended: 送信に成功した場合、trueが設定されます。

##  コンフィグオプション
 * $config['mailform']['To']:送信先のメールアドレスを指定します。初期値が「mail@example.com」になっているため、必ず指定してください。
 * $config['mailform']['Title']:送信されたメールのタイトルです。初期値は「Inquiry Mail」です。
 * $config['mailform']['Charset']:メールのキャラクタセット。原則変更の必要はありません(初期値は「ISO-2022-JP-MS」)。
 * $config['mailform']['webhook']:オプション。ここにWebhookのURLを指定すると、フォーム送信時にWebhookが呼び出されます。主にSlack用。

# エラーメッセージのローカライズ

エラー時の文言は、付属の「wording.[言語コード].json」で読み込んでいます。訪問者閲覧環境の言語コードより適宜判断しています。

もし該当する言語コードのファイルが存在しなかった場合は、「wording.json」を読み込みます。

このJSONファイルの読み込みには、PHP標準のjson_decode()関数を使用しています。よって以下の制約が生じますのでご注意ください。

 * 名前および値はシングルクオートではなくダブルクオートで括る必要があります
 * 最後の項目のあとに、カンマ(,)を含めることはできません。
