@echo off
chcp 932 > nul
setlocal EnableExtensions EnableDelayedExpansion

REM ============================================================
REM MySQL / MariaDB 初期データ投入バッチ（バックアップなし）
REM バッチ本体: Shift_JIS / CRLF
REM 投入SQLファイル: Shift_JIS
REM ============================================================

REM 読み込むフォルダ名は、この1か所だけ変更してください。
set "DATA_FOLDER_NAME=SQL"

REM DB接続情報
set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"
set "DB_HOST=localhost"
set "DB_PORT=3306"
set "DB_USER=root"
set "DB_PASS="
set "DB_NAME=nishijuku"

set "ROOT=%~dp0"
set "SQL_DIR=%ROOT%%DATA_FOLDER_NAME%"
set "LOG_FILE=%ROOT%initSql_%DATA_FOLDER_NAME%_BKなし.log"
set "COLUMN_FILE=%ROOT%mysql_columns_tmp.txt"
set "PASS_OPT="
if defined DB_PASS set "PASS_OPT=-p%DB_PASS%"

echo.
echo SERVER   : %DB_HOST%
echo PORT     : %DB_PORT%
echo DB       : %DB_NAME%
echo USER     : %DB_USER%
echo DATA DIR : %SQL_DIR%
echo BACKUP   : なし
echo.

if not exist "%MYSQL_EXE%" (
    echo [ERROR] mysql.exe が見つかりません。
    echo "%MYSQL_EXE%"
    pause
    exit /b 1
)

if not exist "%SQL_DIR%\" (
    echo [ERROR] %DATA_FOLDER_NAME%フォルダが存在しません。
    echo "%SQL_DIR%"
    pause
    exit /b 1
)

dir /b "%SQL_DIR%\*.sql" > nul 2>&1
if errorlevel 1 (
    echo [ERROR] %DATA_FOLDER_NAME%フォルダ内にSQLファイルがありません。
    echo "%SQL_DIR%"
    pause
    exit /b 1
)

set "ANS="
set /p "ANS=%DATA_FOLDER_NAME%のデータ登録を実行しますか？ (y/n) : "
if /I not "%ANS%"=="y" (
    echo 処理を中止しました。
    pause
    exit /b 0
)

"%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" %PASS_OPT% --default-character-set=sjis --batch --skip-column-names -e "SELECT 1;" "%DB_NAME%" > nul 2>&1
if errorlevel 1 (
    echo [ERROR] DBへの接続に失敗しました。
    echo MySQL/MariaDBの起動状態と接続情報を確認してください。
    pause
    exit /b 1
)

> "%LOG_FILE%" echo %DATA_FOLDER_NAME%登録開始（バックアップなし）
>> "%LOG_FILE%" echo 開始日時: %DATE% %TIME%
>> "%LOG_FILE%" echo.

for %%F in ("%SQL_DIR%\*.sql") do (
    call :PROCESS_FILE "%%~fF"
    if errorlevel 1 goto :ERROR_END
)

echo.
echo ========================================
echo %DATA_FOLDER_NAME%のデータ投入完了
echo ========================================
echo ログ: "%LOG_FILE%"
>> "%LOG_FILE%" echo.
>> "%LOG_FILE%" echo [DONE] 終了日時: %DATE% %TIME%
if exist "%COLUMN_FILE%" del /q "%COLUMN_FILE%"
pause
exit /b 0

:PROCESS_FILE
set "SQL_FILE=%~1"
set "TABLE=%~n1"

echo.
echo [TABLE] %TABLE%
>> "%LOG_FILE%" echo.
>> "%LOG_FILE%" echo [TABLE] %TABLE%

echo [TRUNCATE] %TABLE%
"%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" %PASS_OPT% --default-character-set=sjis -e "TRUNCATE TABLE `%DB_NAME%`.`%TABLE%`;" >> "%LOG_FILE%" 2>&1
if errorlevel 1 (
    echo [ERROR] TRUNCATE失敗: %TABLE%
    exit /b 1
)

echo [EXECUTE] %~nx1
"%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" %PASS_OPT% --default-character-set=sjis "%DB_NAME%" < "%SQL_FILE%" >> "%LOG_FILE%" 2>&1
if errorlevel 1 (
    echo [ERROR] SQL実行失敗: %~nx1
    exit /b 1
)

echo [AUDIT / FLAG UPDATE] %TABLE%
set "UPDATE_COUNT=0"
"%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" %PASS_OPT% --default-character-set=sjis --batch --skip-column-names -e "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='%TABLE%' ORDER BY ORDINAL_POSITION;" > "%COLUMN_FILE%" 2>> "%LOG_FILE%"
if errorlevel 1 exit /b 1
for /f "usebackq delims=" %%C in ("%COLUMN_FILE%") do (
    call :UPDATE_COLUMN "%%C"
    if errorlevel 1 exit /b 1
)

if "!UPDATE_COUNT!"=="0" (
    echo [SKIP] 更新対象列なし
    >> "%LOG_FILE%" echo [SKIP] 更新対象列なし
)
echo [COMPLETE] %TABLE%
>> "%LOG_FILE%" echo [COMPLETE] %TABLE%
exit /b 0

:UPDATE_COLUMN
set "COLUMN=%~1"
if /I "%COLUMN%"=="created_at" goto UPDATE_DATETIME
if /I "%COLUMN%"=="updated_at" goto UPDATE_DATETIME
if /I "%COLUMN%"=="append_datetime" goto UPDATE_DATETIME
if /I "%COLUMN%"=="update_datetime" goto UPDATE_DATETIME
if /I "%COLUMN%"=="lock_timestamp" goto UPDATE_DATETIME
if /I "%COLUMN:~-11%"=="_update_ymd" goto UPDATE_DATETIME
if /I "%COLUMN%"=="append_user_id" goto UPDATE_BATCH
if /I "%COLUMN%"=="append_func_id" goto UPDATE_BATCH
if /I "%COLUMN%"=="update_user_id" goto UPDATE_BATCH
if /I "%COLUMN%"=="update_func_id" goto UPDATE_BATCH
if /I "%COLUMN:~-15%"=="_update_user_id" goto UPDATE_BATCH
if /I "%COLUMN:~-3%"=="flg" goto UPDATE_FLAG
exit /b 0

:UPDATE_DATETIME
"%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" %PASS_OPT% --default-character-set=sjis -e "UPDATE `%DB_NAME%`.`%TABLE%` SET `%COLUMN%`=CURRENT_TIMESTAMP;" >> "%LOG_FILE%" 2>&1
goto UPDATE_RESULT

:UPDATE_BATCH
"%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" %PASS_OPT% --default-character-set=sjis -e "UPDATE `%DB_NAME%`.`%TABLE%` SET `%COLUMN%`='Batch';" >> "%LOG_FILE%" 2>&1
goto UPDATE_RESULT

:UPDATE_FLAG
"%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" %PASS_OPT% --default-character-set=sjis -e "UPDATE `%DB_NAME%`.`%TABLE%` SET `%COLUMN%`=0 WHERE `%COLUMN%` IS NULL;" >> "%LOG_FILE%" 2>&1

:UPDATE_RESULT
if errorlevel 1 (
    echo [ERROR] 更新失敗: %TABLE%.%COLUMN%
    >> "%LOG_FILE%" echo [ERROR] 更新失敗: %TABLE%.%COLUMN%
    exit /b 1
)
set /a UPDATE_COUNT+=1
exit /b 0

:ERROR_END
if exist "%COLUMN_FILE%" del /q "%COLUMN_FILE%"
echo.
echo ========================================
echo %DATA_FOLDER_NAME%のデータ投入中にエラーが発生しました
echo ========================================
echo ログ: "%LOG_FILE%"
>> "%LOG_FILE%" echo [ERROR] 終了日時: %DATE% %TIME%
pause
exit /b 1
