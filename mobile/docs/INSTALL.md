# Install SheShield on an Android phone

The app is an APK file attached to each GitHub Release. It isn't on the Play Store, so Android asks you to allow the install once.

## 1. Download the APK

1. On your phone, open **https://github.com/Ankit-Basu/SheShield_app/releases/latest**.
   - While the repository is private, sign in to GitHub in the phone's browser first (the GitHub app does not download release files).
2. Under **Assets**, tap the file named like **`SheShield-1.0.0-build1.apk`** (about 30–40 MB).
3. If Chrome warns that the file might be harmful, tap **Download anyway**. It says this for every APK that doesn't come from the Play Store.

## 2. Install it

1. When the download finishes, tap **Open** in the notification, or open **Files → Downloads** and tap the APK.
2. The first time, Android says installing from this source isn't allowed. Tap **Settings**, turn on **Allow from this source** for Chrome (or your browser), then go back.
3. Tap **Install**. If **Google Play Protect** asks about an unknown app, tap **More details → Install anyway**. This only happens because the app isn't on the Play Store.
4. Tap **Open**. The SheShield icon also appears in your app drawer.

## 3. First run

1. Tap **Create an account** and sign up with your email and a password (at least 8 characters).
   - An account made on the website with Google has no password, so create a new one here. Google sign-in isn't in the app yet.
   - The first request can take up to a minute if the server was asleep.
2. When asked, allow **location** (needed for SOS and the map) and **camera / photos** (only when you add evidence to a report).
3. Go to **You → Emergency contact**, add someone, and tap **Save**. SOS then offers to call or text them.

## Demo script for an interview (2 minutes)

1. **Home:** live campus clock, your numbers, quick actions.
2. **SOS** (red button in the middle): hold for 2 seconds and watch the ring fill and the phone vibrate. Show the location confirmation and the call, text and share buttons. *Tip: this sends a real alert to your backend; that's fine for a demo account.*
3. **Report:** pick a type, tap **Use my location**, add a photo, send. Then turn on airplane mode, send another and show it's saved. Turn airplane mode off and watch it send by itself.
4. **Walks:** request a walk from Library to Main Gate, then cancel it.
5. **Map:** satellite view of LPU with the campus outline; tap a landmark.
6. **You:** switch between the three themes.

## Updating the app

Install a newer APK over the old one the same way; your sign-in and settings stay.

To make a new build, open **Actions → Android APK → Run workflow** in the repository, or push a tag:

```bash
git tag v1.0.1
```

```bash
git push origin v1.0.1
```

## Troubleshooting

| Problem | Fix |
|---|---|
| "App not installed" | An older build signed differently is already installed. Uninstall SheShield first, then install again. |
| "There was a problem parsing the package" | The download didn't finish. Download it again on Wi-Fi. |
| Sign-up spins for a long time | The free server was asleep; wait up to a minute and try again. |
| SOS says location unavailable | Turn on location in quick settings, and allow it for SheShield in **Settings → Apps → SheShield → Permissions**. |
| Map is blank | The map loads from the internet; check your connection. |

The APK is built for 64-bit ARM phones, which covers virtually every Android phone from the last several years. iPhones can't install APKs; an iOS build needs an Apple developer account.
