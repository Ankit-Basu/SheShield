// js/firebase-config.js — SheShield Firebase Configuration
// Uses Firebase Compat SDK for simplicity (works via <script> tags)

const firebaseConfig = {
  apiKey: "AIzaSyBqcmBEvAKhllpbqYiFP8dlrpeAyw8vjHM",
  authDomain: "sheshield-fd94e.firebaseapp.com",
  projectId: "sheshield-fd94e",
  storageBucket: "sheshield-fd94e.firebasestorage.app",
  messagingSenderId: "864016319268",
  appId: "1:864016319268:web:618726d72da750644a69b1",
  measurementId: "G-LSR2EWKQHP"
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);
const auth = firebase.auth();
const googleProvider = new firebase.auth.GoogleAuthProvider();
