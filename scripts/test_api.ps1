<#
PowerShell API smoke test for FarmerLookup
Usage:
  Open PowerShell (as normal user) and run:
    .\scripts\test_api.ps1
  Optionally pass a base URL:
    .\scripts\test_api.ps1 -BaseUrl 'http://localhost/FarmerLookup'
#>
param(
    [string]$BaseUrl = 'http://localhost/FarmerLookup'
)

function Try-Req {
    param($Method, $Path, $Body = $null)
    $url = $BaseUrl.TrimEnd('/') + '/' + $Path.TrimStart('/')
    Write-Host "\n> $Method $url" -ForegroundColor Cyan
    try {
        if ($Body -ne $null) {
            $json = $Body | ConvertTo-Json -Depth 10
            $res = Invoke-RestMethod -Uri $url -Method $Method -ContentType 'application/json' -Body $json -ErrorAction Stop
        } else {
            $res = Invoke-RestMethod -Uri $url -Method $Method -ErrorAction Stop
        }
        Write-Host "OK" -ForegroundColor Green
        return $res
    } catch {
        Write-Host "ERROR:" -ForegroundColor Red
        if ($_.Exception.Response -ne $null) {
            try { $status = $_.Exception.Response.StatusCode.Value__ } catch { $status = 'unknown' }
            Write-Host "HTTP status: $status"
            try { $text = (New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())).ReadToEnd(); Write-Host $text } catch {}
        }
        Write-Host $_.Exception.Message
        return $null
    }
}

Write-Host "Running FarmerLookup API smoke tests against: $BaseUrl" -ForegroundColor Yellow

# 1) Test server connection
$test = Try-Req GET 'api/test_connection.php'
if ($test -eq $null) { Write-Host 'test_connection failed — ensure Apache/MySQL are running and BaseUrl is correct.' -ForegroundColor Red; exit 1 }
Write-Host (ConvertTo-Json $test -Depth 5)

# 2) Run seed script locally (PHP CLI) — returns JSON
Write-Host "\n> Running local seed script via PHP CLI" -ForegroundColor Cyan
try {
    $seedJson = php .\scripts\seed.php 2>&1
    $seed = $seedJson | ConvertFrom-Json
    if (-not $seed.success) { Write-Host "Seed failed: $($seed.error)" -ForegroundColor Red; exit 1 }
    Write-Host (ConvertTo-Json $seed -Depth 5)
    $farmerId = $seed.farmer_id
    $buyerId = $seed.buyer_id
    $productId = $seed.product_id
} catch {
    Write-Host 'Seed script execution failed. Ensure PHP is in PATH.' -ForegroundColor Red; exit 1
}

# 3) Try login with seeded account
$login = Try-Req POST 'api/auth/login.php' @{ email = 'farmer@example.com'; password = 'Password123'; user_type = 'farmer' }
Write-Host (ConvertTo-Json $login -Depth 5)

# 4) Product search
$search = Try-Req GET "api/products/search.php?q=Tomatoes"
Write-Host (ConvertTo-Json $search -Depth 5)

# 5) Create a product (as seeded farmer)
$newProd = Try-Req POST 'api/products.php' @{
    farmer_id = $farmerId
    category_id = $seed.category_id
    title = 'Smoke Test - Cucumber'
    description = 'Automated test product'
    price = 2.50
    unit = 'kg'
    quantity = 25
    image_url = 'assets/images/placeholder-product.jpg'
    farming_method = 'sustainable'
    is_active = 1
}
if ($newProd -ne $null) { Write-Host "New product id: $($newProd.id)" }

# 6) Create an order (as buyer)
$order = Try-Req POST 'api/orders.php' @{
    buyer_id = $buyerId
    items = @(@{ product_id = $productId; quantity = 2; unit_price = 3.5 })
    shipping_address = '123 Test St'
    city = 'Testville'
    state = 'TS'
    zip_code = '00000'
}
Write-Host (ConvertTo-Json $order -Depth 5)

# 7) Send a message
$msg = Try-Req POST 'api/messages/send.php' @{ sender_id = $buyerId; recipient_id = $farmerId; content = 'Hello from smoke test' }
Write-Host (ConvertTo-Json $msg -Depth 5)

# 8) Add a review
$review = Try-Req POST 'api/reviews.php' @{ reviewer_id = $buyerId; reviewed_user_id = $farmerId; product_id = $productId; rating = 5; comment = 'Great!' }
Write-Host (ConvertTo-Json $review -Depth 5)

Write-Host '\nSmoke tests completed. If all steps returned JSON with success=true or OK responses, your DB and APIs are storing data.' -ForegroundColor Yellow

# End of script
