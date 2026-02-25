@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

REM ==========================================================
REM  Nishijuku SQL Runner (完全版)
REM
REM   DB\       : DB作成などのSQL（.txt）
REM   TABLE\    : テーブル作成などのSQL（.txt）
REM   VALES\    : INSERT/UPDATE/DELETE などのSQL（.txt）
REM
REM   実行モードは run_memo.txt で指定
REM ==========================================================

REM ===================== 設定 =====================

REM mysql.exe の場所
set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"

REM DB接続情報
set "DB_HOST=localhost"
set "DB_USER=root"
set "DB_PASS="

REM batのある場所
set "ROOT=%~dp0"

REM 実行指示ファイル
set "MEMO_FILE=%ROOT%run_memo.txt"

REM =================================================


REM =================================================
REM mysql.exe存在チェック
REM =================================================
if not exist "%MYSQL_EXE%" (
 echo [ERROR] mysql.exe が見つかりません
 echo %MYSQL_EXE%
 pause
 exit /b 1
)


REM =================================================
REM run_memo.txt存在チェック
REM =================================================
if not exist "%MEMO_FILE%" (
 echo [ERROR] run_memo.txt が見つかりません
 echo %MEMO_FILE%
 pause
 exit /b 1
)


REM =================================================
REM run_memo.txt読み込み
REM =================================================

set "MODE="
set "ARG="

for /f "usebackq tokens=1,* delims==" %%A in ("%MEMO_FILE%") do (

 if /i "%%A"=="MODE" set "MODE=%%B"
 if /i "%%A"=="ARG"  set "ARG=%%B"

)

echo MODE=%MODE%
echo ARG=%ARG%
echo.


REM =================================================
REM MODE別実行
REM =================================================

if /i "%MODE%"=="all" (

 call :RUN_FOLDER "DB"
 if errorlevel 1 goto END

 call :RUN_FOLDER "TABLE"
 if errorlevel 1 goto END

 call :RUN_FOLDER "VALES"
 goto END

)

if /i "%MODE%"=="folder" (

 call :RUN_FOLDER "%ARG%"
 goto END

)

if /i "%MODE%"=="file" (

 call :RUN_FILE "%ROOT%%ARG%"
 goto END

)

if /i "%MODE%"=="path" (

 call :RUN_FILE "%ARG%"
 goto END

)

if /i "%MODE%"=="pattern" (

 call :RUN_PATTERN "%ROOT%%ARG%"
 goto END

)

echo [ERROR] MODE が不正です
echo all / folder / file / pattern / path
pause
exit /b 1



REM =================================================
REM フォルダ実行
REM =================================================
:RUN_FOLDER

set "FOLDER=%~1"
set "TARGET=%ROOT%%FOLDER%"

if "%FOLDER%"=="" (

 echo [ERROR] フォルダ指定なし
 exit /b 1

)

if not exist "%TARGET%" (

 echo [ERROR] フォルダなし
 echo %TARGET%
 exit /b 1

)

echo --------------------------
echo [FOLDER] %FOLDER%
echo --------------------------

set "FOUND=0"

for /f "delims=" %%F in ('dir /b /on "%TARGET%\*.txt" 2^>nul') do (

 set "FOUND=1"

 call :RUN_FILE "%TARGET%\%%F"

 if errorlevel 1 exit /b 1

)

if "!FOUND!"=="0" (

 echo [WARN] %FOLDER% に txtなし

)

exit /b 0



REM =================================================
REM パターン実行
REM =================================================
:RUN_PATTERN

set "PATT=%~1"

echo --------------------------
echo [PATTERN]
echo %PATT%
echo --------------------------

set "FOUND=0"

for /f "delims=" %%F in ('dir /b /on "%PATT%" 2^>nul') do (

 set "FOUND=1"

 call :RUN_FILE "%%~fF"

 if errorlevel 1 exit /b 1

)

if "!FOUND!"=="0" (

 echo [WARN] 対象なし

)

exit /b 0



REM =================================================
REM 1ファイル実行
REM =================================================
:RUN_FILE

set "FILE=%~1"

if not exist "%FILE%" (

 echo [ERROR] ファイルなし
 echo %FILE%
 exit /b 1

)

echo.
echo [RUN ] %FILE%

REM UTF-8対応
REM SQLファイルもUTF-8保存必須

if "%DB_PASS%"=="" (

 "%MYSQL_EXE%" ^
 -h "%DB_HOST%" ^
 -u "%DB_USER%" ^
 --default-character-set=utf8mb4 ^
 < "%FILE%" 2>&1

) else (

 "%MYSQL_EXE%" ^
 -h "%DB_HOST%" ^
 -u "%DB_USER%" ^
 -p%DB_PASS% ^
 --default-character-set=utf8mb4 ^
 < "%FILE%" 2>&1

)

if errorlevel 1 (

 echo.
 echo [ERROR] SQL失敗
 echo %FILE%
 pause
 exit /b 1

)

echo [OK  ] 完了

exit /b 0



REM =================================================
REM 終了
REM =================================================
:END

echo.
echo [DONE] 実行終了
pause

exit /b 0