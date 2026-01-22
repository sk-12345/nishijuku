<?php
declare(strict_types=1);

/**
 * ⚠️ ここにGmail設定を書く（公開しない）
 * 送信に使うGmailは「アプリパスワード」を設定済みのもの
 */

return [
  // 送信元（Gmail）
  'GMAIL_USER' => 'hatujukai2419@gmail.com',
  
  // アプリパスワード（16桁）※スペース無し推奨
  'GMAIL_APP'  => 'ekxskyrxmpjuonqy',

  // ✅ 受信先（Toで複数）
  'TO_MAIL' => [
    '346kou3110@gmail.com',
  ],

  // 差出人表示名
  'FROM_NAME'  => '西塾柔道応募情報',
];

