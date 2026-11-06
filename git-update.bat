@echo off
title HiNoida - sending latest changes to GitHub
cd /d "%~dp0"
echo Started %date% %time% > git-update-log.txt
git rm -r -q --cached --ignore-unmatch writable/maillog add-rent-samples.php git-update-log.txt >> git-update-log.txt 2>&1
git add -A >> git-update-log.txt 2>&1
git commit -m "Stop tracking local mail copies and test scripts" -m "Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>" -m "Claude-Session: https://claude.ai/code/session_012AMytZeojagZcfxreCwxH4" >> git-update-log.txt 2>&1
git pull --rebase -X theirs origin main >> git-update-log.txt 2>&1
git push origin main >> git-update-log.txt 2>&1
if errorlevel 1 (echo PUSH_FAILED >> git-update-log.txt & echo Upload did not finish - tell Claude.) else (echo PUSH_OK >> git-update-log.txt & echo Uploaded! Railway will now rebuild.)
git status --short >> git-update-log.txt 2>&1
timeout /t 15 > nul
