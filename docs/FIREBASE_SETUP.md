# Firebase Google Sign-In — Setup Guide

## 1. Create a Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click **"Add project"**
3. Enter project name: `SheShield`
4. Disable Google Analytics (or enable, optional)
5. Click **Create Project**

## 2. Enable Google Sign-In

1. In Firebase Console → **Authentication** → **Sign-in method**
2. Click **Google** provider
3. Toggle **Enable**
4. Set project support email
5. Click **Save**

## 3. Register Your Web App

1. Go to **Project Settings** (gear icon) → **General**
2. Under "Your apps", click the web icon `</>`
3. Enter app nickname: `SheShield Web`
4. **Do NOT** check "Firebase Hosting" (we use our own)
5. Click **Register App**
6. Copy the config object:

```javascript
const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_PROJECT_ID.appspot.com",
  messagingSenderId: "YOUR_SENDER_ID",
  appId: "YOUR_APP_ID"
};
```

## 4. Update Project Files

### File: `js/firebase-config.js`
Replace the placeholder values with your actual config from Step 3.

### File: `pro/login.html`
The Google Sign-In button is already wired up with `id="googleSignIn"`. Add this script at the bottom of `login.html` (before `</body>`):

```html
<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>
<script>
  // Initialize Firebase (paste YOUR config)
  firebase.initializeApp({
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_PROJECT_ID.appspot.com",
    messagingSenderId: "YOUR_SENDER_ID",
    appId: "YOUR_APP_ID"
  });

  document.getElementById('googleSignIn').addEventListener('click', async () => {
    try {
      const provider = new firebase.auth.GoogleAuthProvider();
      const result = await firebase.auth().signInWithPopup(provider);
      const idToken = await result.user.getIdToken();

      // Send token to your PHP backend
      const res = await fetch('../api/auth/firebase-login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idToken })
      });
      const data = await res.json();
      if (data.success) {
        window.location.href = data.redirect || '../dashboard.php';
      } else {
        alert('Google Sign-In failed: ' + (data.message || 'Unknown error'));
      }
    } catch (err) {
      console.error('Google Sign-In Error:', err);
      alert('Google Sign-In cancelled or failed');
    }
  });
</script>
```

## 5. Backend Verification

The file `api/auth/firebase-login.php` already exists and handles:
- Receiving the Firebase ID token
- Verifying it via Google's API
- Creating/finding the user in MySQL
- Starting a PHP session

> [!IMPORTANT]
> Ensure `extension=curl` is enabled in your `php.ini` (XAMPP → `php/php.ini`). Restart Apache after enabling.

## 6. Add Authorized Domains

1. Firebase Console → **Authentication** → **Settings** → **Authorized domains**
2. Add your domains:
   - `localhost` (for development)
   - `your-production-domain.com`

## 7. Test

1. Start XAMPP (Apache + MySQL)
2. Open `http://localhost/sheshield/pro/login.html`
3. Click the **Google** button
4. Sign in with a Google account
5. You should be redirected to the dashboard

> [!TIP]
> If the popup gets blocked, try using `signInWithRedirect` instead of `signInWithPopup`.
