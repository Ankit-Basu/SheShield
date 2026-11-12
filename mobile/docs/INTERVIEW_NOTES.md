# SheShield Mobile: interview notes

Read this alongside the code. Every answer below points at the file that does it, so you can open it in an interview and walk through it.

---

## 1. The 30-second pitch

> "SheShield is a campus-safety app for women at LPU. I built the web version in PHP and MySQL, then a React Native app with Expo on the same backend. The headline feature is a hold-to-send SOS: it grabs the GPS fix, alerts the backend, and offers to call 112 or your emergency contact. If the network fails, the call and SMS fallbacks still work. It also does escorted walk requests, incident reports with photos that queue offline, and a satellite map of campus. It's TypeScript, Expo Router, TanStack Query for server state and Zustand for client state, with Reanimated for animations. I build the APK in the cloud with EAS."

---

## 2. React → React Native, in one table

You already know React. Most of it carries over; this is what changes.

| React (web) | React Native | Where in this app |
|---|---|---|
| `<div>` | `<View>` | everywhere |
| `<p>`, `<span>` | `<Text>`. All text **must** be inside `<Text>` | `components/Text.tsx` wraps it as `<T variant=…>` |
| `<input>` | `<TextInput>` | `components/Field.tsx` |
| `<button onClick>` | `<Pressable onPress>` (also `onPressIn` / `onPressOut`) | `components/Button.tsx`, `HoldToSend.tsx` |
| CSS files, classes | `StyleSheet.create({...})` JS objects, no cascade | bottom of every component |
| Flexbox defaults to row | Flexbox defaults to **column**; everything is flex | `styles.row` uses `flexDirection: 'row'` |
| `px`, `rem` | Unitless density-independent pixels | |
| `<img>` | `<Image>` (here `expo-image` for caching) | `components/bits.tsx` Avatar |
| scrolling `<div>` | `<ScrollView>`, or `<FlatList>` for long lists (virtualised) | `components/Screen.tsx`, `app/reports.tsx` |
| React Router | Expo Router (file-based, like Next.js) | `src/app/` |
| `localStorage` | AsyncStorage (plain) / SecureStore (encrypted) | `lib/theme.ts`, `lib/auth.ts` |
| `fetch` | same `fetch` | `lib/api.ts` |
| CSS animations | Reanimated (runs on the UI thread) | `Aurora.tsx`, `HoldToSend.tsx`, `GlassTabBar.tsx` |

**Platform files:** `CampusMap.tsx` is the native version, and `CampusMap.web.tsx` is picked automatically for the web build. The bundler chooses by file extension.

---

## 3. Architecture

```
UI (screens in src/app, components in src/components)
   │ hooks
   ▼
TanStack Query (lib/queries.ts)      Zustand stores (auth, theme, toast, offline queue)
   │ queryFn / mutationFn                 │
   ▼                                      ▼
lib/api.ts  ── fetch + Bearer token ──►  PHP API on Render (via Vercel)
```

- **Server state vs client state.**
  - **Server state:** data that lives on the server (profile, reports, walks). TanStack Query owns it, with caching, background refetch, pull-to-refresh, retries and loading or error flags.
  - **Client state:** auth token, theme, toasts, offline queue. Small Zustand stores, so no Redux boilerplate.
  - **Say:** "I keep server data in a query cache instead of copying it into global state, so there's one source of truth."
- **One API layer:** `lib/api.ts` adds the base URL and token and applies a timeout with `AbortController`.
  - **Errors:** it turns failures into an `ApiError` with an `offline` flag, so screens can tell "no signal" apart from "server said no".
  - **401:** on a 401 it signs the user out automatically.
- **Auth-guarded navigation:** `app/_layout.tsx` uses `Stack.Protected guard={!!token}`.
  - Signed out, only welcome, login and signup exist. Signed in, only the tabs and modals exist.
  - Signing in or out just changes the token, and the router redirects by itself.

---

## 4. Feature deep dives (what to say, where to point)

### SOS: `app/sos.tsx`, `components/HoldToSend.tsx`
- **Hold, don't tap:** a 1.6 s hold fills an SVG ring. `strokeDashoffset` is animated with Reanimated's `useAnimatedProps`, so it runs on the UI thread and stays smooth even if JS is busy. Haptic ticks get stronger as it fills, and letting go early cancels and rewinds it.
- **Fast location:**
  - `getLastKnownPositionAsync` returns instantly.
  - It then races `getCurrentPositionAsync` against an 8-second timeout (`withTimeout`).
  - In an emergency you don't wait 30 s for GPS.
- **Graceful failure:** if the POST fails, the screen still offers Call 112, call or text your contact, and share. Those use `Linking` (`tel:`, `sms:`) and the native share sheet, which don't need the backend.

### Offline reports: `lib/offline.ts`, `app/report.tsx`
- **Saving:** if sending fails *because of the network* (`ApiError.offline`), the draft goes into a queue in AsyncStorage. Photo URIs are kept as local file paths.
- **Retrying:** `watchConnection` listens with NetInfo. When the phone is back online it flushes the queue, shows a toast, and invalidates queries so the screens refresh.
- **Rejected reports aren't retried:** a report the server rejects (validation error) is dropped from the queue, since it would never succeed.

### Saved and sample data when the server sleeps: `lib/fallback.ts`, `lib/sample.ts`
- **Problem:** Render's free plan sleeps when idle, and the first requests time out or get 502/503 while it wakes.
- **Fallback:** reads go through `withFallback`, which waits 10 s.
  - **Server answers:** it saves the result in AsyncStorage.
  - **Server unreachable or waking:** it returns the saved copy, or sample data on a first run, and records which (live, saved or sample) in a small Zustand store.
- **Banner and retry:** `SourceBanner` reads that store to explain what's on screen. React Query's `refetchInterval` retries every 15 s only while a screen shows fallback data.
- **Real errors still surface:** only network or gateway errors fall back, so "not signed in" isn't hidden. Saved data is cleared on sign-out, never at launch.

### Optimistic updates: `lib/queries.ts` → `useCancelWalk`
- **onMutate:** cancel the in-flight query, save the old list, and show the walk as cancelled immediately.
- **onError:** restore the saved list.
- **onSettled:** refetch, so the screen matches the server.

### Map: `components/CampusMap.tsx`, `components/map-html.ts`
- **Why a WebView with Leaflet instead of react-native-maps?**
  - Google Maps in a standalone Android build needs an API key, which means billing setup.
  - Leaflet with Esri tiles needs no key, and reuses the website's OSM campus outline.
- **Communication:**
  - React Native → page: `injectJavaScript("ss.user(lat,lng)")`.
  - Page → React Native: `window.ReactNativeWebView.postMessage(...)` → `onMessage`.
- **A bug you can mention:** the OSM boundary came as two ways. Drawing them as two rings made a straight line across campus, so I joined them into one closed ring. The website had the same bug, and I fixed it there too.

### Token auth (backend change I made): `SheShield/app/middleware/session_bootstrap.php`
- **Why tokens:** phones don't keep PHP session cookies reliably, and Render's free plan wipes session files on restart.
- **Login:** with `client: "mobile"`, login returns a random 64-character token. The DB stores only its SHA-256 hash, so a leaked database can't be used to sign in.
- **Reusing the endpoints:** each request with `Authorization: Bearer …` rebuilds the same `$_SESSION` fields a browser login sets, so every existing endpoint works unchanged.
- **Storage and sign-out:** on the phone, the token lives in SecureStore (Keychain / Keystore). Sign-out revokes it on the server.

### Theming: `lib/theme.ts`
- **Source:** three palettes converted from the website's OKLCH CSS variables to hex.
- **Persistence:** a Zustand store with the `persist` middleware and AsyncStorage remembers your choice.
- **Live switching:** all components read `useTheme()`, so switching re-renders everything instantly.

---

## 5. Performance choices (good to volunteer)

- **UI-thread animations:** all animations are Reanimated worklets. The aurora background drifts with `withRepeat` and costs zero JS per frame.
- **Respecting reduced motion:** `useReducedMotion()` turns the aurora off for users who enable it.
- **Long lists:** use `FlatList` (virtualised), not `.map()` inside a ScrollView (`app/reports.tsx`).
- **React Compiler is on:** it auto-memoises components. Lint enforces its rules, which is why shared values use `.set()` instead of `.value =`.
- **Fewer requests:** `api/user/me.php` returns profile, stats and recent reports in one request instead of five.
- **Cheaper images:** photos are picked at `quality: 0.6`.

---

## 6. Build and release

- **Expo vs bare React Native:**
  - Expo is the framework the React Native docs recommend.
  - Native config lives in `app.json` and config plugins (permissions, icons, splash), and `npx expo prebuild` generates the `android/` and `ios/` folders when needed. This is called Continuous Native Generation.
- **EAS Build:** `eas.json` profiles.
  - **`preview`:** builds an **APK** you can install directly.
  - **`production`:** builds an **AAB** (Android App Bundle) for the Play Store, which then generates optimised APKs per device.
  - **Signing:** EAS creates and stores the Android signing keystore.
- **CI builds:** a GitHub Actions workflow (`.github/workflows/android-apk.yml`) does the same without an Expo account.
  - **Steps:** `expo prebuild` generates `android/`, then Gradle runs `assembleRelease` for `arm64-v8a` only (faster, smaller), and the APK is published as a GitHub Release.
  - **Signing:** that APK is signed with a debug key, which is fine for sideloading. The Play Store needs a real upload key, which EAS manages.
- **Permissions:**
  - Declared in `app.json`, with only foreground location; background location and microphone are explicitly blocked.
  - Requested at the moment they're needed (`requestForegroundPermissionsAsync`), not at launch.
- **Over-the-air updates:** JS-only changes can ship with `eas update`, without a new APK. Native changes need a new build.

---

## 7. Likely questions with short answers

1. **What is the difference between React Native and a WebView app?** React Native renders real native views (Android `View`, `TextView`); only the logic runs in JS. My map is the one place I deliberately use a WebView, for Leaflet.
2. **What is the New Architecture?**
   - **JSI** lets JS call native code directly instead of through the old async JSON bridge.
   - **Fabric** is the new renderer, and **TurboModules** load native modules lazily.
   - SDK 58 uses it by default.
3. **How does Reanimated stay smooth?** Animation code runs as worklets on the UI thread. JS sets a target (`withTiming`, `withSpring`) and the UI thread animates without waiting on JS.
4. **Why TanStack Query instead of `useEffect` + `useState` for fetching?** Caching, deduplication, background refetch, retries, loading and error states, optimistic updates and invalidation, without hand-writing each one.
5. **How do you store secrets on the device?** `expo-secure-store` uses the Android Keystore and iOS Keychain. Never AsyncStorage for tokens.
6. **How do you handle no internet?** API errors carry an `offline` flag. Reports are queued in AsyncStorage and flushed by a NetInfo listener. SOS falls back to phone calls and SMS.
7. **How would you add push notifications?** Use `expo-notifications` with FCM. Register the device token with the backend, which sends a push when a walker is assigned or a report's status changes.
8. **How do you test it?**
   - **What I did:** type checking, lint, and end-to-end runs of every flow (sign-up, report, walk request and cancel, SOS hold) against the real backend.
   - **What I'd add next:** Jest with React Native Testing Library for components, and Maestro for device flows.
9. **What was hardest?**
   - **SOS reliability:** fast location without hanging, and useful fallbacks when the network fails.
   - **Auth:** making the existing cookie-based PHP backend support token auth without breaking the website.
10. **Android vs iOS differences you handled:**
    - **Blur:** BlurView only on iOS; Android gets a translucent fill, because Android blur needs a target view and is costly before Android 12.
    - **Keyboard:** `KeyboardAvoidingView` behaviour differs between the two.
    - **Elevation vs shadow:** Android uses `elevation`; iOS uses the `shadow*` props.

---

## 8. What I would build next

- **Push notifications** when a walker is assigned.
- **Live location sharing** during a walk, using background location (needs Play Store justification).
- **Unit and end-to-end tests:** Jest, React Native Testing Library and Maestro.
- **Google sign-in** in the app (the website already has it).
- **Accessibility pass** with a screen reader. Labels and roles are already set on the main controls.
