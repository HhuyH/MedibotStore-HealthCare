@echo off
echo ========================================
echo        FIX GIT ISSUES SCRIPT
echo ========================================
echo.

echo [1/6] Checking current Git configuration...
git config --global core.autocrlf
git config --global core.safecrlf
echo.

echo [2/6] Setting recommended Git configuration...
git config --global core.autocrlf true
git config --global core.safecrlf warn
git config --global core.editor "code --wait"
git config --global init.defaultBranch main
echo Git configuration updated!
echo.

echo [3/6] Checking for whitespace issues...
git diff --check
echo.

echo [4/6] Checking line endings...
git ls-files --eol | findstr /C:"crlf" | head -10
echo.

echo [5/6] Creating backup branch...
git branch backup-before-fix
echo Backup branch created: backup-before-fix
echo.

echo [6/6] Do you want to normalize line endings? (y/n)
set /p normalize="Enter choice: "
if /i "%normalize%"=="y" (
    echo Normalizing line endings...
    git rm --cached -r .
    git add .
    echo Files re-added with normalized line endings
    echo.
    echo Ready to commit. Use:
    echo git commit -m "Fix line endings and encoding issues"
    echo git push origin main
) else (
    echo Skipping normalization.
)

echo.
echo ========================================
echo            SCRIPT COMPLETED
echo ========================================
echo.
echo Next steps:
echo 1. Review changes: git status
echo 2. Check diff: git diff --staged
echo 3. Commit if satisfied: git commit -m "Fix line endings"
echo 4. Push: git push origin main
echo.
echo If issues persist, check GIT_ISSUES_README.md
pause 