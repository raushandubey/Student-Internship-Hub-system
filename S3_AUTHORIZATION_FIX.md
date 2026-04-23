# S3 Authorization Error Fix

## Issue Description

Users were experiencing "InvalidArgument" and "Authorization" errors when trying to access resume files from S3 storage. The error appeared as an XML response in the browser when clicking on resume links.

## Root Cause

The application was using `Storage::disk('s3')->url()` which generates basic S3 URLs. For private S3 buckets (which is the secure default), these URLs require authentication and were failing with authorization errors.

## Solution Implemented

Updated the `Profile::getResumeUrl()` method to use **temporary signed URLs** with a 1-hour expiration. This provides:

1. **Better Security**: Files remain private in S3, only accessible via time-limited signed URLs
2. **Automatic Fallback**: If signed URLs fail (e.g., for public buckets), the method falls back to regular URLs
3. **Longer Validity**: 1-hour expiration ensures users can access files without frequent re-authentication

## Code Changes

### File: `app/Models/Profile.php`

**Before:**
```php
if ($disk === 's3') {
    if (\Illuminate\Support\Facades\Storage::disk('s3')->exists($this->resume_path)) {
        return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->resume_path);
    }
}
```

**After:**
```php
if ($disk === 's3') {
    if (\Illuminate\Support\Facades\Storage::disk('s3')->exists($this->resume_path)) {
        // Try to generate a temporary signed URL with 1 hour expiration
        // This provides better security and handles private buckets
        try {
            return \Illuminate\Support\Facades\Storage::disk('s3')
                ->temporaryUrl($this->resume_path, now()->addHour());
        } catch (\Exception $e) {
            // If temporaryUrl fails (e.g., bucket is public), fall back to regular URL
            \Log::debug('Falling back to regular S3 URL', [
                'profile_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->resume_path);
        }
    }
}
```

## Configuration Requirements

### Option A: Private Bucket with Signed URLs (Recommended)

**IAM Permissions Required:**
```json
{
  "Effect": "Allow",
  "Action": [
    "s3:PutObject",
    "s3:GetObject",
    "s3:DeleteObject",
    "s3:ListBucket"
  ],
  "Resource": [
    "arn:aws:s3:::your-bucket-name/*",
    "arn:aws:s3:::your-bucket-name"
  ]
}
```

**Bucket Configuration:**
- Keep bucket private (Block all public access: ON)
- No bucket policy needed
- Application generates signed URLs automatically

### Option B: Public Bucket (Less Secure)

If you prefer public access (not recommended for sensitive files):

**Bucket Policy:**
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadGetObject",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::your-bucket-name/*"
    }
  ]
}
```

**CORS Configuration:**
```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedOrigins": ["*"],
    "ExposeHeaders": []
  }
]
```

## Testing

### Verify the Fix

1. **Clear application cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Test URL generation:**
   ```bash
   php artisan tinker
   >>> $profile = App\Models\Profile::first();
   >>> $url = $profile->getResumeUrl();
   >>> echo $url;
   ```
   
   The URL should contain query parameters like `X-Amz-Algorithm`, `X-Amz-Credential`, `X-Amz-Signature` (indicating it's a signed URL).

3. **Test in browser:**
   - Log in as a student
   - View profile page
   - Click on resume link
   - File should download/display correctly

### Run Tests

```bash
# Run Profile model tests
php artisan test tests/Unit/Models/ProfileTest.php

# Run S3 integration tests
php artisan test tests/Feature/S3StorageIntegrationTest.php

# Run all tests
php artisan test
```

## Benefits

1. **Enhanced Security**: Files remain private, accessible only via time-limited signed URLs
2. **Better User Experience**: Users can access files without authentication errors
3. **Flexible Configuration**: Works with both private and public buckets
4. **Automatic Fallback**: Gracefully handles different S3 configurations

## Troubleshooting

### If signed URLs still fail:

1. **Check IAM permissions** - Ensure `s3:GetObject` is granted
2. **Verify AWS credentials** - Ensure they're correctly configured in `.env`
3. **Check bucket region** - Must match `AWS_DEFAULT_REGION`
4. **Review logs** - Check `storage/logs/laravel.log` for detailed errors

### If you see "Signature does not match" errors:

- Verify AWS credentials are correct
- Ensure system time is synchronized (signed URLs are time-sensitive)
- Check that `AWS_DEFAULT_REGION` matches your bucket's region

## Documentation Updates

Updated `S3_SETUP.md` with:
- New troubleshooting section for authorization errors
- Explanation of signed URLs vs regular URLs
- Configuration options for private vs public buckets
- CORS configuration guidance

## Related Files

- `app/Models/Profile.php` - Updated `getResumeUrl()` method
- `S3_SETUP.md` - Updated troubleshooting guide
- `tests/Unit/Models/ProfileTest.php` - Tests verify the fix works correctly

## Deployment Notes

This fix is backward compatible and requires no database changes. Simply deploy the updated code and clear the application cache.

For Laravel Cloud deployments, the signed URLs will work automatically once the S3 bucket is attached and credentials are configured.
