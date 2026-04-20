$filePath = "templates\admin\events.html.twig"
$content = Get-Content -Path $filePath -Raw -Encoding UTF8

# Count and replace
$count1 = ($content | Select-String -Pattern '⏳' -AllMatches).Matches.Count
$count2 = ($content | Select-String -Pattern '⚠️' -AllMatches).Matches.Count
$count3 = ($content | Select-String -Pattern '─' -AllMatches).Matches.Count
$count4 = ($content | Select-String -Pattern '│' -AllMatches).Matches.Count
$count5 = ($content | Select-String -Pattern '┌' -AllMatches).Matches.Count
$count6 = ($content | Select-String -Pattern '└' -AllMatches).Matches.Count
$count7 = ($content | Select-String -Pattern '╔' -AllMatches).Matches.Count
$count8 = ($content | Select-String -Pattern '║' -AllMatches).Matches.Count
$count9 = ($content | Select-String -Pattern '╚' -AllMatches).Matches.Count

Write-Host "═══════════════════════════════════"
Write-Host "Character counts found:"
Write-Host "⏳ (hourglass): $count1 occurrences"
Write-Host "⚠️ (warning): $count2 occurrences"
Write-Host "─ (dash): $count3 occurrences"
Write-Host "│ (pipe): $count4 occurrences"
Write-Host "┌ (top-left corner): $count5 occurrences"
Write-Host "└ (bottom-left corner): $count6 occurrences"
Write-Host "╔ (box top-left): $count7 occurrences"
Write-Host "║ (box pipe): $count8 occurrences"
Write-Host "╚ (box bottom-left): $count9 occurrences"

$totalBefore = $count1 + $count2 + $count3 + $count4 + $count5 + $count6 + $count7 + $count8 + $count9
Write-Host "═══════════════════════════════════"
Write-Host "Total problematic chars: $totalBefore"

# Perform replacements
$content = $content -replace '⏳', '[Loading]'
$content = $content -replace '⚠️', '[Warning]'
$content = $content -replace '─', '-'
$content = $content -replace '│', '|'
$content = $content -replace '┌', '+'
$content = $content -replace '└', '+'
$content = $content -replace '╔', '+'
$content = $content -replace '║', '|'
$content = $content -replace '╚', '+'

# Save the file
Set-Content -Path $filePath -Value $content -Encoding UTF8

Write-Host "File saved with replacements!"
Write-Host "═══════════════════════════════════"
Write-Host "Replacements made: $totalBefore"
