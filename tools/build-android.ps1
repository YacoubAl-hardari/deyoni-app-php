param([switch]$DebugBuild)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot
$sdkPath = if ($env:ANDROID_HOME) { $env:ANDROID_HOME } else { Join-Path $env:LOCALAPPDATA 'Android\Sdk' }
$jdkPath = if ($env:JAVA_HOME) { $env:JAVA_HOME } else { 'C:\Program Files\Android\Android Studio\jbr' }
$herdBin = Join-Path $env:USERPROFILE '.config\herd\bin'
$env:Path = "$projectRoot\nativephp\android;$sdkPath\platform-tools;$jdkPath\bin;$herdBin\php84;$herdBin;" + $env:Path
$env:JAVA_HOME = $jdkPath
$env:NATIVEPHP_ANDROID_SDK_LOCATION = $sdkPath
$env:NATIVEPHP_GRADLE_PATH = $jdkPath
$phpPath = (Get-Command php -ErrorAction Stop).Source
if ($DebugBuild) {
    & $phpPath artisan native:run android --no-interaction --no-tty
    exit $LASTEXITCODE
}
$signingDirectory = Join-Path $projectRoot '.signing'
[System.IO.Directory]::CreateDirectory($signingDirectory) | Out-Null
$passwordPath = Join-Path $signingDirectory 'password.txt'
$keystorePath = Join-Path $signingDirectory 'deyoni-release.p12'
if (-not (Test-Path -LiteralPath $passwordPath)) {
    if (Test-Path -LiteralPath $keystorePath) { throw 'Signing password is missing. Restore it before building.' }
    $randomBytes = New-Object byte[] 48
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $rng.GetBytes($randomBytes)
    $rng.Dispose()
    [System.IO.File]::WriteAllText($passwordPath, [Convert]::ToBase64String($randomBytes))
}
$env:ANDROID_KEYSTORE_FILE = $keystorePath
$env:ANDROID_KEYSTORE_PASSWORD = [System.IO.File]::ReadAllText($passwordPath).Trim()
$env:ANDROID_KEY_PASSWORD = $env:ANDROID_KEYSTORE_PASSWORD
$env:ANDROID_KEY_ALIAS = 'deyoni'
try {
    if (-not (Test-Path -LiteralPath $keystorePath)) {
        & "$jdkPath\bin\keytool.exe" -genkeypair -storetype PKCS12 -keystore $keystorePath -storepass:env ANDROID_KEYSTORE_PASSWORD -keypass:env ANDROID_KEY_PASSWORD -alias deyoni -keyalg RSA -keysize 3072 -validity 10000 -dname 'CN=Deyoni, O=Deyoni, C=YE'
        if ($LASTEXITCODE -ne 0) { throw 'Could not create signing key.' }
    }
    $env:APP_ENV = 'production'
    $env:APP_DEBUG = 'false'
    & $phpPath artisan native:package android --build-type=release --output=dist --no-interaction --no-tty
    if ($LASTEXITCODE -ne 0) { throw 'Android release build failed. See nativephp/android-build.log.' }
} finally {
    Remove-Item Env:ANDROID_KEYSTORE_PASSWORD -ErrorAction SilentlyContinue
    Remove-Item Env:ANDROID_KEY_PASSWORD -ErrorAction SilentlyContinue
    Remove-Item Env:APP_ENV -ErrorAction SilentlyContinue
    Remove-Item Env:APP_DEBUG -ErrorAction SilentlyContinue
}
