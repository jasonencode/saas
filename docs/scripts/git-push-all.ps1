param(
    [Parameter(Position = 0)]
    [string]$Branch = (git rev-parse --abbrev-ref HEAD),
    [switch]$Force
)

$pushArgs = @("push", "origin", $Branch)
if ($Force) { $pushArgs += "--force" }

Write-Host "Pushing to origin..." -ForegroundColor Cyan
git @pushArgs
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

$pushArgs[1] = "github"
Write-Host "Pushing to github..." -ForegroundColor Cyan
git @pushArgs
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "Done. Pushed to both origin and github." -ForegroundColor Green
