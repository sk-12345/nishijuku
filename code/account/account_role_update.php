<?php
declare(strict_types=1);

http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
echo '権限IDの付け替えは廃止されました。権限フラグをオン・オフしてください。';
