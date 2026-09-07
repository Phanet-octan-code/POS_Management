// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyBc5n8S7mzPA99K6TmuKVT7n7whLRYCFKg",
  authDomain: "pos-management-88866.firebaseapp.com",
  projectId: "pos-management-88866",
  storageBucket: "pos-management-88866.firebasestorage.app",
  messagingSenderId: "1089593768258",
  appId: "1:1089593768258:web:7a6428fcb624a812e530db",
  measurementId: "G-Q021Y54GX7"
};

// Initialize Firebase
export const app = initializeApp(firebaseConfig);
export const analytics = typeof window !== 'undefined' ? getAnalytics(app) : null;

if (typeof window !== 'undefined') {
  window.firebaseApp = app;
  window.firebaseAnalytics = analytics;
  console.log('[Firebase] Connected successfully to pos-management-88866');
}

export default app;
