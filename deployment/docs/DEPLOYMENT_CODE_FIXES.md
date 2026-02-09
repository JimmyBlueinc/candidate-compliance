# 🔧 Code Fixes for Render Deployment

This document lists all code changes required to make the application compatible with Render's free tier (no persistent filesystem, S3 storage required).

---

## 📝 Required Code Changes

### 1. Update Storage Disk Usage

**Problem**: Code uses `Storage::disk('public')` which won't work with S3. Need to use the default disk (which will be S3 in production).

**Files to Update**:

#### `backend/app/Http/Controllers/Api/AuthController.php`

**Line 218** - Delete old avatar:
```php
// OLD:
\Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_path);

// NEW:
\Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->delete($user->avatar_path);
```

**Line 221** - Store new avatar:
```php
// OLD:
$path = $file->store('avatars', 'public');

// NEW:
$path = $file->store('avatars', config('filesystems.default'));
```

#### `backend/app/Http/Controllers/Api/CredentialController.php`

**Line 145** - Store document:
```php
// OLD:
$path = $request->file('document')->store('credentials', 'public');

// NEW:
$path = $request->file('document')->store('credentials', config('filesystems.default'));
```

**Find and replace** all instances of:
- `Storage::disk('public')->delete(...)` → `Storage::disk(config('filesystems.default'))->delete(...)`
- `->store(..., 'public')` → `->store(..., config('filesystems.default'))`

#### `backend/app/Http/Controllers/Api/BackgroundCheckController.php`
- Line 141: `Storage::disk('public')->delete(...)` → `Storage::disk(config('filesystems.default'))->delete(...)`
- Line 179: Same change

#### `backend/app/Http/Controllers/Api/HealthRecordController.php`
- Line 145: `Storage::disk('public')->delete(...)` → `Storage::disk(config('filesystems.default'))->delete(...)`
- Line 172: Same change

#### `backend/app/Http/Controllers/Api/WorkAuthorizationController.php`
- Line 143: `Storage::disk('public')->delete(...)` → `Storage::disk(config('filesystems.default'))->delete(...)`
- Line 181: Same change

---

### 2. Update Model URL Accessors

**Problem**: Models construct URLs manually, which won't work with S3. Need to use `Storage::url()`.

#### `backend/app/Models/User.php`

**Replace `getAvatarUrlAttribute()` method** (lines 55-93):

```php
public function getAvatarUrlAttribute(): ?string
{
    if (!$this->avatar_path) {
        return null;
    }
    
    // Use Storage::url() which handles S3 automatically
    $url = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->avatar_path);
    
    // Add cache-busting parameter using updated_at timestamp
    if ($this->updated_at) {
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . 'v=' . $this->updated_at->timestamp;
    }
    
    return $url;
}
```

#### `backend/app/Models/Credential.php`

**Update `getDocumentUrlAttribute()` method** (around line 108):

```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

#### `backend/app/Models/BackgroundCheck.php`

**Update `getDocumentUrlAttribute()` method** (around line 53):

```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

#### `backend/app/Models/HealthRecord.php`

**Update `getDocumentUrlAttribute()` method** (around line 48):

```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

#### `backend/app/Models/WorkAuthorization.php`

**Update `getDocumentUrlAttribute()` method** (around line 54):

```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

#### `backend/app/Models/TrainingRecord.php`

**Update `getDocumentUrlAttribute()` method** (around line 49):

```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

#### `backend/app/Models/PerformanceRecord.php`

**Update `getDocumentUrlAttribute()` method** (around line 50):

```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

---

### 3. Add Health Check Endpoint

**File**: `backend/routes/api.php`

**Add this route** (at the top, before auth middleware):

```php
// Health check endpoint (for Render to prevent sleep)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'environment' => app()->environment(),
    ]);
});
```

---

### 4. Remove storage:link from Deployment

**Files to check**:
- Any deployment scripts
- `backend/docker/start.sh` (already updated ✅)

**Action**: Ensure no `php artisan storage:link` commands are run. S3 doesn't need symlinks.

---

## 🚀 Quick Fix Script

You can use this find-and-replace approach:

### Find and Replace in All Files

1. **Storage disk usage**:
   - Find: `Storage::disk('public')`
   - Replace: `Storage::disk(config('filesystems.default'))`

2. **Store with public disk**:
   - Find: `->store('`, `'public')`
   - Replace: `->store('`, `config('filesystems.default'))`

3. **Store with public disk (alternative)**:
   - Find: `->store(`, `'public')`
   - Replace: `->store(`, `config('filesystems.default'))`

---

## ✅ Verification Checklist

After making changes, verify:

- [ ] All `Storage::disk('public')` replaced
- [ ] All `->store(..., 'public')` replaced
- [ ] All model URL accessors use `Storage::url()`
- [ ] Health check endpoint added
- [ ] No `storage:link` commands in deployment scripts
- [ ] Test file uploads work (should use S3)
- [ ] Test file URLs work (should be S3 URLs)

---

## 🧪 Testing

### Local Testing with S3

1. Set `.env`:
   ```env
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=your-key
   AWS_SECRET_ACCESS_KEY=your-secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=your-bucket
   ```

2. Test avatar upload
3. Test document upload
4. Verify URLs are S3 URLs

### Production Testing

1. Deploy to Render
2. Test file uploads
3. Verify files appear in S3 bucket
4. Verify URLs are accessible

---

## 📚 Additional Notes

- **S3 URL Format**: `Storage::url()` returns full S3 URLs like `https://bucket.s3.amazonaws.com/path/to/file`
- **Cache Busting**: Avatar URLs include `?v=timestamp` for cache busting
- **Permissions**: Ensure S3 bucket has public read access for files
- **CORS**: Configure S3 bucket CORS if needed for direct browser access

---

**Status**: Ready to apply fixes ✅

