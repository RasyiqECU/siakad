$base = 'http://127.0.0.1:8000/api/v1'
function Api {
    param([string]$Method, [string]$Url, [string]$Token, [object]$Body)
    $req = [System.Net.HttpWebRequest]::Create($Url)
    $req.Method = $Method; $req.ContentType = 'application/json'; $req.Accept = 'application/json'
    if ($Token) { $req.Headers.Add('Authorization', "Bearer $Token") }
    if ($Body) {
        $b = [Text.Encoding]::UTF8.GetBytes(($Body | ConvertTo-Json -Depth 5 -Compress))
        $req.ContentLength = $b.Length; $s = $req.GetRequestStream(); $s.Write($b,0,$b.Length); $s.Close()
    }
    try { $r = $req.GetResponse() } catch [WebException] { $r = $_.Exception.Response }
    $st = [int]$r.StatusCode
    $raw = (New-Object IO.StreamReader($r.GetResponseStream())).ReadToEnd()
    @{ status = $st; raw = $raw }
}
$r = Api 'POST' "$base/auth/login" $null (@{ email='superadmin@simora.test'; password='password'; device_name='final' })
Write-Output "login: $($r.status) -> $($r.raw.Substring(0,120))"
$t = (($r.raw | ConvertFrom-Json).data.token)
$r = Api 'GET' "$base/users?per_page=100" $t $null
$j = $r.raw | ConvertFrom-Json
Write-Output "GET /users: $($r.status), total=$($j.meta.total), first=$($j.data[0].roles[0].display_name)"
$r = Api 'GET' "$base/roles" $t $null
$j = $r.raw | ConvertFrom-Json
Write-Output "GET /roles: $($r.status), roles=$($j.data.Count), super_admin perms=$(@(($j.data | Where-Object name -eq 'super_admin').permissions).Count)"
$r = Api 'GET' "$base/audit-logs?per_page=3" $t $null
Write-Output "GET /audit-logs: $($r.status) -> $($r.raw.Substring(0,160))"
