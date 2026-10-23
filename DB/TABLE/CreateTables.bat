@echo off
chcp 932 > nul
setlocal EnableExtensions EnableDelayedExpansion

REM ============================================================
REM MySQL / MariaDB テーブル作成専用バッチ
REM 配置先: DB\TABLE または DB
REM 対象:   DB\TABLE\CREATE\*.sql
REM バックアップなし
REM SQLファイル: UTF-8
REM ============================================================

set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"
set "DB_HOST=localhost"
set "DB_PORT=3306"
set "DB_USER=root"
set "DB_PASS="
set "DB_NAME=nishijuku"

set "ROOT=%~dp0"

REM DB\TABLEに置いた場合とDB直下に置いた場合の両方に対応
set "SQL_DIR=%ROOT%CREATE"
if not exist "%SQL_DIR%\" set "SQL_DIR=%ROOT%TABLE\CREATE"

set "LOG_FILE=%ROOT%createTables.log"
set "PASS_OPT="
if defined DB_PASS set "PASS_OPT=-p%DB_PASS%"

echo.
echo DB        : %DB_NAME%
echo SQL DIR   : %SQL_DIR%
echo BACKUP    : なし
echo.

if not exist "%MYSQL_EXE%" (
    echo [ERROR] mysql.exe が見つかりません。
    echo "%MYSQL_EXE%"
    pause
    exit /b 1
)

if not exist "%SQL_DIR%\" (
    echo [ERROR] テーブル作成SQLフォルダが存在しません。
    echo "%SQL_DIR%"
    pause
    exit /b 1
)

dir /b "%SQL_DIR%\*.sql" > nul 2>&1
if errorlevel 1 (
    echo [ERROR] CREATEフォルダ内にSQLファイルがありません。
    echo "%SQL_DIR%"
    pause
    exit /b 1
)

"%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" %PASS_OPT% --default-character-set=utf8mb4 --batch --skip-column-names -e "SELECT 1;" "%DB_NAME%" > nul 2>&1
if errorlevel 1 (
    echo [ERROR] DBへの接続に失敗しました。
    pause
    exit /b 1
)

echo [注意] SQLにDROP TABLEがある場合、既存テーブルとデータが削除されます。
set "ANS="
set /p "ANS=テーブル作成SQLを実行しますか？ (y/n) : "
if /I not "%ANS%"=="y" (
    echo 処理を中止しました。
    pause
    exit /b 0
)

> "%LOG_FILE%" echo テーブル作成開始（バックアップなし）
>> "%LOG_FILE%" echo 開始日時: %DATE% %TIME%
>> "%LOG_FILE%" echo 対象: %SQL_DIR%
>> "%LOG_FILE%" echo.

set "FOUND=0"
for /f "delims=" %%F in ('dir /b /on "%SQL_DIR%\*.sql" 2^>nul') do (
    set "FOUND=1"
    call :RUN_SQL "%SQL_DIR%\%%F"
    if errorlevel 1 goto :ERROR_END
)

if "!FOUND!"=="0" goto :ERROR_END

echo.
echo ========================================
echo テーブル作成完了
echo ========================================
echo ログ: "%LOG_FILE%"
>> "%LOG_FILE%" echo.
>> "%LOG_FILE%" echo [DONE] 終了日時: %DATE% %TIME%
pause
exit /b 0

:RUN_SQL
set "TABLE_NAME=%~n1"
echo [RUN] %~nx1
>> "%LOG_FILE%" echo [RUN] %~nx1

REM SQLファイル名と同名の既存テーブルを削除
REM 例: users.sql → nishijuku.users
echo [DROP] %TABLE_NAME%
>> "%LOG_FILE%" echo [DROP] %TABLE_NAME%
"%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" %PASS_OPT% --default-character-set=utf8mb4 -e "SET FOREIGN_KEY_CHECKS=0; DROP TABLE IF EXISTS `%DB_NAME%`.`%TABLE_NAME%`; SET FOREIGN_KEY_CHECKS=1;" >> "%LOG_FILE%" 2>&1
if errorlevel 1 (
    echo [ERROR] 既存テーブル削除失敗: %TABLE_NAME%
    >> "%LOG_FILE%" echo [ERROR] 既存テーブル削除失敗: %TABLE_NAME%
    exit /b 1
)

REM テーブル作成SQLを実行
"%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" %PASS_OPT% --default-character-set=utf8mb4 "%DB_NAME%" < "%~1" >> "%LOG_FILE%" 2>&1
if errorlevel 1 (
    echo [ERROR] SQL実行失敗: %~nx1
    >> "%LOG_FILE%" echo [ERROR] SQL実行失敗: %~nx1
    exit /b 1
)

echo [OK] %~nx1
>> "%LOG_FILE%" echo [OK] %~nx1
exit /b 0

:ERROR_END
echo.
echo ========================================
echo テーブル作成中にエラーが発生しました
echo ========================================
echo ログ: "%LOG_FILE%"
>> "%LOG_FILE%" echo [ERROR] 終了日時: %DATE% %TIME%
pause
exit /b 1
