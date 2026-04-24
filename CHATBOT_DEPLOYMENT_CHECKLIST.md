# 🚀 Chatbot Production Deployment Checklist

## ✅ Pre-Deployment Verification

### 1. Verify Local Build
```bash
# Run verification script
php verify-chatbot-assets.php
```

**Expected Output**:
- ✅ Vite manifest exists
- ✅ Chatbot JS built: `public/build/js/chatbot.min2.js`
- ✅ Chatbot CSS built: `public/build/css/chatbot.min.css`
- ✅ Blade component uses `@vite()` directive

### 2. Test Locally
```bash
# Start development server
php artisan serve

# Visit in browser
http://localhost:8000
```

**Browser Console Checks**:
- [ ] No 404 errors for chatbot assets
- [ ] `window.ShreeRamChatbot` object exists
- [ ] `window.chatbotUserProfile` object exists (when logged in)
- [ ] Chatbot button visible in bottom-right corner

**Functional Tests**:
- [ ] Click Om (🕉️) button → Chat window opens
- [ ] Type "help" → Bot responds with help topics
- [ ] Type "apply" → Bot shows application guidance
- [ ] Type "profile" → Bot shows profile completion advice
- [ ] Quick reply buttons work
- [ ] Links in responses work

---

## 📦 Deployment Steps

### Step 1: Commit Changes
```bash
git add resources/views/components/chatbot.blade.php
git add CHATBOT_PRODUCTION_FIX.md
git add verify-chatbot-assets.php
git add CHATBOT_DEPLOYMENT_CHECKLIST.md
git commit -m "Fix chatbot production asset loading with @vite directive"
```

### Step 2: Push to Repository
```bash
git push origin main
```

### Step 3: Deploy to Laravel Cloud
Laravel Cloud will automatically:
1. Pull latest code
2. Run `composer install`
3. Run `npm install`
4. Run `npm run build` (generates fresh manifest)
5. Deploy application

**Monitor deployment logs for**:
- ✅ `npm run build` completes successfully
- ✅ No Vite build errors
- ✅ Assets generated in `public/build/`

---

## 🧪 Post-Deployment Verification

### 1. Check Production URL
Visit your Laravel Cloud URL (e.g., `https://your-app.laravel.cloud`)

### 2. Browser Console Checks
Open Developer Tools (F12) → Console tab:

**Expected**:
- ✅ No 404 errors
- ✅ No JavaScript errors
- ✅ `ShreeRamChatbot initialized successfully` message

**If you see errors**:
```
❌ GET https://your-app.laravel.cloud/build/js/chatbot.min.js 404
```
→ Assets not built correctly. Check deployment logs.

```
❌ Uncaught ReferenceError: ShreeRamChatbot is not defined
```
→ JavaScript file not loading. Check network tab.

### 3. Network Tab Checks
Developer Tools → Network tab → Reload page:

**Check these requests**:
- [ ] `/build/js/chatbot.min2.js` → Status: 200 OK
- [ ] `/build/css/chatbot.min.css` → Status: 200 OK

**File sizes should be**:
- JS: ~30-50 KB (minified)
- CSS: ~5-10 KB (minified)

### 4. Functional Testing
- [ ] Chatbot button visible and animated
- [ ] Click button → Window opens smoothly
- [ ] Type "help" → Receives response
- [ ] Personalized responses work (when logged in)
- [ ] Quick replies clickable
- [ ] Links navigate correctly
- [ ] Close button works
- [ ] Responsive on mobile

---

## 🐛 Troubleshooting

### Issue: 404 for chatbot assets

**Symptoms**:
```
GET /build/js/chatbot.min.js 404 (Not Found)
```

**Solutions**:
1. Check if `npm run build` ran during deployment
2. Verify `vite.config.js` includes chatbot files in input array
3. Check `public/build/manifest.json` exists in production
4. Ensure `.gitignore` doesn't exclude `public/build/`

**Quick Fix**:
```bash
# Locally rebuild and commit build directory
npm run build
git add public/build/ -f
git commit -m "Add built assets"
git push
```

---

### Issue: Chatbot button visible but doesn't open

**Symptoms**:
- Button appears
- Click does nothing
- No console errors

**Solutions**:
1. Check if JavaScript loaded: `console.log(window.ShreeRamChatbot)`
2. Check event listeners: `document.getElementById('chatbot-toggle-btn')`
3. Verify `defer` attribute on script tag (should be present)

---

### Issue: Chatbot opens but doesn't respond

**Symptoms**:
- Window opens
- Type message → No response
- No console errors

**Solutions**:
1. Check `window.chatbotUserProfile` exists (for logged-in users)
2. Verify knowledge base loaded: `ShreeRamChatbot.knowledgeBase`
3. Check message processing: Type "help" and inspect console

---

### Issue: Personalized responses not working

**Symptoms**:
- Generic responses only
- No user-specific advice

**Solutions**:
1. Verify user is logged in: `@auth` block in Blade
2. Check profile data injection: `console.log(window.chatbotUserProfile)`
3. Ensure profile exists: User must have completed profile

---

## 📊 Success Criteria

### ✅ Deployment Successful When:
- [ ] No 404 errors in browser console
- [ ] No JavaScript errors in console
- [ ] Chatbot button visible and animated
- [ ] Chat window opens on click
- [ ] Bot responds to messages
- [ ] Personalized responses work (when logged in)
- [ ] Quick replies functional
- [ ] Links navigate correctly
- [ ] Works on desktop and mobile
- [ ] Performance: First interaction < 1 second

---

## 🔄 Rollback Plan

If deployment fails:

### Option 1: Revert Commit
```bash
git revert HEAD
git push origin main
```

### Option 2: Use Fallback Asset Loading
Update `resources/views/components/chatbot.blade.php`:

```blade
{{-- Fallback: Manual manifest reading --}}
@if(app()->environment('production'))
    @php
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $chatbotJs = $manifest['public/js/chatbot.js']['file'] ?? 'js/chatbot.min.js';
        $chatbotCss = $manifest['public/css/chatbot.css']['file'] ?? 'css/chatbot.min.css';
    @endphp
    <link rel="stylesheet" href="{{ asset('build/' . $chatbotCss) }}">
    <script src="{{ asset('build/' . $chatbotJs) }}" defer></script>
@else
    <link rel="stylesheet" href="{{ asset('css/chatbot.css') }}">
    <script src="{{ asset('js/chatbot.js') }}" defer></script>
@endif
```

---

## 📞 Support

If issues persist:
1. Check Laravel Cloud deployment logs
2. Review Vite build output
3. Inspect browser network tab
4. Check server error logs: `storage/logs/laravel.log`

---

**Last Updated**: 2026-04-25
**Status**: Ready for deployment ✅
