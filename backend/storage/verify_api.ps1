# Phase 2 API verification script (dev only).
$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8000/api/v1'
$results = @()
$script:last = $null
$actions = @()

function Api {
    param([string]$Method, [string]$Url, [string]$Token, [object]$Body)
    $req = [System.Net.HttpWebRequest]::Create($Url)
    $req.Method = $Method
    $req.ContentType = 'application/json'
    $req.Accept = 'application/json'
    $req.Timeout = 30000
    if ($Token) { $req.Headers.Add('Authorization', "Bearer $Token") }
    if ($Body -ne $null) {
        $bytes = [System.Text.Encoding]::UTF8.GetBytes(($Body | ConvertTo-Json -Depth 5 -Compress))
        $req.ContentLength = $bytes.Length
        $s = $req.GetRequestStream()
        $s.Write($bytes, 0, $bytes.Length)
        $s.Close()
    }
    try { $resp = $req.GetResponse() } catch [System.Net.WebException] { $resp = $_.Exception.Response }
    $status = [int]$resp.StatusCode
    $raw = (New-Object System.IO.StreamReader($resp.GetResponseStream())).ReadToEnd()
    if ($status -eq 404 -and $raw -match 'Endpoint tidak ditemukan') { $json = $raw | ConvertFrom-Json } else { $json = if ($raw) { $raw | ConvertFrom-Json } else { $null } }
    $script:last = @{ status = $status; json = $json; raw = $raw }
    return $script:last
}

function Check {
    param([string]$Name, [int]$Actual, [int]$Expected, [string]$Extra = '')
    $ok = ($Actual -eq $Expected)
    if ($ok -and $Extra) {
        $ok = (Invoke-Expression $Extra)
    }
    if (-not $ok) {
        Write-Output "   DETAIL [$Name]: status=$Actual raw=$(($script:last.raw | Out-String).Substring(0, [Math]::Min(400, ($script:last.raw | Out-String).Length)))"
    }
    $script:results += [pscustomobject]@{
        Test = $Name; Status = $Actual; Expected = $Expected; Result = $(if ($ok) { 'PASS' } else { 'FAIL' })
    }
}

# --- a. login superadmin
$r = Api 'POST' "$base/auth/login" $null (@{ email='superadmin@simora.test'; password='password'; device_name='verify' })
Check 'a. login superadmin' $r.status 200 '$r.json.data.token -ne $null'
$admin = $r.json.data.token

# --- b. GET /users
$r = Api 'GET' "$base/users?page=1" $admin $null
Check 'b. GET /users 200 + meta(6 users)' $r.status 200 '$r.json.meta.total -eq 6 -and $r.json.data.Count -le 15'
$usersTotal = $r.json.meta.total

# --- c. POST /users create dosen user
$newEmail = "dosenbaru.$(Get-Random).@simora.test".Replace('..', '.')
$stamp = Get-Date -Format 'Hmmss'
$stamp = Get-Date -Format 'HHmmss'
$newEmail = "dosenbaru$stamp@simora.test"
$r = Api 'POST' "$base/users" $admin (@{ name='Dosen Baru'; email=$newEmail; password='password123'; phone='0812345678'; is_active=$true; roles=@('dosen') })
Check 'c. POST /users 201 (+ roles dosen)' $r.status 201 '$r.json.data.roles[0].name -eq "dosen"'
$newUserId = $r.json.data.id
$r = Api 'POST' "$base/auth/login" $null (@{ email=$newEmail; password='password123' })
Check 'c2. new user can login' $r.status 200 '$r.json.data.token -ne $null'

# --- d. keuangan cannot GET /users
$r = Api 'POST' "$base/auth/login" $null (@{ email='keuangan@simora.test'; password='password'; device_name='verify' })
$keu = $r.json.data.token
$r = Api 'GET' "$base/users" $keu $null
Check 'd. keuangan GET /users -> 403 envelope' $r.status 403 '$r.json.success -eq $false'

# --- e. deactivate -> login forbidden
$r = Api 'PATCH' "$base/users/$newUserId/deactivate" $admin $null
Check 'e. deactivate user 200 is_active=false' $r.status 200 '$r.json.data.is_active -eq $false'
$r = Api 'POST' "$base/auth/login" $null (@{ email=$newEmail; password='password123' })
Check 'e2. deactivated user login -> 403' $r.status 403 '$r.json.success -eq $false'
$r = Api 'PATCH' "$base/users/$newUserId/activate" $admin $null
Check 'e3. re-activate user 200' $r.status 200 '$r.json.data.is_active -eq $true'

# --- f. PATCH /users/{id} sync roles (admin_akademik -> keuangan), lalu revert
$r = Api 'GET' "$base/users?per_page=100" $admin $null
$adminAkademikUser = ($r.json.data | Where-Object { $_.email -eq 'admin@simora.test' })
$adminAkademikId = $adminAkademikUser.id
$r = Api 'PATCH' "$base/users/$adminAkademikId" $admin (@{ roles=@('keuangan') })
Check 'f. sync role admin_akademik -> keuangan 200' $r.status 200 '$r.json.data.roles[0].name -eq "keuangan"'
$r = Api 'GET' "$base/users/$adminAkademikId" $admin $null
Check 'f2. role change persisted' $r.status 200 '$r.json.data.roles[0].name -eq "keuangan"'
$r = Api 'PATCH' "$base/users/$adminAkademikId" $admin (@{ roles=@('admin_akademik') })
Check 'f3. role reverted' $r.status 200 '$r.json.data.roles[0].name -eq "admin_akademik"'

# --- g. profile flow
$r = Api 'GET' "$base/profile" $admin $null
Check 'g. GET /profile 200' $r.status 200 '$r.json.data.name -ne $null'
$r = Api 'PATCH' "$base/profile" $admin (@{ name='Super Admin SIMORA v2'; password='password123'; current_password='password' })
Check 'g2. PATCH /profile rename+password 200' $r.status 200 '$r.json.data.name -eq "Super Admin SIMORA v2"'
$r = Api 'POST' "$base/auth/login" $null (@{ email='superadmin@simora.test'; password='password123' })
$admin = $r.json.data.token
Check 'g3. new password works' $r.status 200 '$r.json.data.token -ne $null'
$r = Api 'PATCH' "$base/profile" $admin (@{ name='Super Admin SIMORA'; password='passwordXXX'; current_password='wrongpass' })
Check 'g4. wrong current_password -> 422' $r.status 422 '$r.json.success -eq $false -and $r.json.errors.current_password -ne $null'
# kembalikan
$r = Api 'PATCH' "$base/profile" $admin (@{ name='Super Admin SIMORA'; password='password'; current_password='password123' })
Check 'g5. profile restored' $r.status 200 '$r.json.data.name -eq "Super Admin SIMORA"'

# --- h. GET /roles
$r = Api 'GET' "$base/roles" $admin $null
Check 'h. GET /roles 200 + 6 roles + permissions' $r.status 200 '$r.json.data.Count -eq 6 -and $r.json.data[0].permissions.Count -gt 0'
$roles = $r.json.data
$mahasiswaRole = $roles | Where-Object { $_.name -eq 'mahasiswa' }
$originalPerms = $mahasiswaRole.permissions | ForEach-Object { $_.name }

# --- i. PUT /roles/{id}/permissions lalu revert
$r = Api 'PUT' "$base/roles/$($mahasiswaRole.id)/permissions" $admin (@{ permissions=@('study_plans.read','study_plans.create','study_plans.update','grades.read','tuition_bills.read','payments.create','payments.read') })
$iCount = 0
if ($r.json.data) { $iCount = @($r.json.data.permissions).Count }
Check 'i. PUT /roles/{id}/permissions 200 (7 perms)' $r.status 200 "$iCount -eq 7"
$r = Api 'PUT' "$base/roles/$($mahasiswaRole.id)/permissions" $admin (@{ permissions=$originalPerms })
$i2Count = 0
if ($r.json.data) { $i2Count = @($r.json.data.permissions).Count }
Check 'i2. permissions reverted (6 perms)' $r.status 200 "$i2Count -eq 6"

# --- j. reset password + audit logs
$r = Api 'GET' "$base/users?search=dosen%40simora.test" $admin $null
$dosenUserId = ($r.json.data | Where-Object { $_.email -eq 'dosen@simora.test' }).id
$r = Api 'PATCH' "$base/users/$dosenUserId/reset-password" $admin (@{ password='password' })
Check 'j0. PATCH /users/{id}/reset-password 200' $r.status 200

$r = Api 'GET' "$base/audit-logs?per_page=100" $admin $null
$actions = @($r.json.data | ForEach-Object { $_.action })
$withIp = @($r.json.data | Where-Object { $_.ip_address -ne $null }).Count
$auditContains = ($actions -contains 'user_created') -and ($actions -contains 'user_deactivated') -and ($actions -contains 'user_password_reset') -and ($actions -contains 'profile_updated') -and ($actions -contains 'role_permissions_updated')
Check 'j. audit-logs contains create/deactivate/reset' $r.status 200 '$auditContains'
Write-Output "   audit entries with ip_address: $withIp of total $($r.json.meta.total)"

# --- k. duplicate email -> 422
$r = Api 'POST' "$base/users" $admin (@{ name='Dup'; email='mahasiswa@simora.test'; password='password123' })
Check 'k. duplicate email -> 422' $r.status 422 '$r.json.errors.email.Count -ge 1'

# --- l. self protections
$r = Api 'DELETE' "$base/users/1" $admin $null
Check 'l1. cannot delete own locker? (self) -> 422' $r.status 422
$r = Api 'PATCH' "$base/users/1" $admin (@{ roles=@('dosen') })
Check 'l2. cannot remove last super_admin roles -> 422' $r.status 422

# --- m. delete new user (cleanup)
$r = Api 'DELETE' "$base/users/$newUserId" $admin $null
Check 'm. DELETE /users 200' $r.status 200
$r = Api 'GET' "$base/users" $admin $null
Write-Output "   users total after delete: $($r.json.meta.total) (was $usersTotal)"

$results | Format-Table -AutoSize | Out-String -Width 200
$failed = ($results | Where-Object { $_.Result -eq 'FAIL' }).Count
Write-Output "TOTAL: $($results.Count), FAILED: $failed"
