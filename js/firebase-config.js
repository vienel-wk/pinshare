const firebaseConfig = {
  apiKey: "AIzaSyCY310hn7bai5V4H0hhmZ21Y55M2OwlT_A",
  authDomain: "pinshare-3aeac.firebaseapp.com",
  projectId: "pinshare-3aeac",
  storageBucket: "pinshare-3aeac.firebasestorage.app",
  messagingSenderId: "125654365913",
  appId: "1:125654365913:web:0edba873e27f97396a70c8"
};

firebase.initializeApp(firebaseConfig);

window.pinshareFirebase = {
  db: firebase.firestore(),
  storage: firebase.storage()
};
