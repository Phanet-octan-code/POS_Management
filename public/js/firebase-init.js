// Import the functions you need from the SDKs you need
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js";
import { getAnalytics } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-analytics.js";
import { 
    getFirestore, 
    collection, 
    doc, 
    setDoc, 
    addDoc, 
    getDocs, 
    serverTimestamp,
    query,
    orderBy,
    limit
} from "https://www.gstatic.com/firebasejs/10.12.0/firebase-firestore.js";

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

// Initialize Firebase App, Analytics & Firestore
let app = null;
let analytics = null;
let db = null;
let isConnected = false;

try {
    app = initializeApp(firebaseConfig);
    analytics = typeof window !== 'undefined' ? getAnalytics(app) : null;
    db = getFirestore(app);
    isConnected = true;

    window.firebaseApp = app;
    window.firebaseAnalytics = analytics;
    window.firebaseDb = db;

    console.log('[Firebase] Connected to Firebase Project:', firebaseConfig.projectId);
} catch (error) {
    console.warn('[Firebase] Initialization error:', error.message);
}

/**
 * Firebase Firestore Cloud Data Storage and Synchronization Service
 */
const FirebaseSync = {
    config: firebaseConfig,
    isConnected: () => isConnected && db !== null,

    /**
     * Store POS Sale Transaction into Firestore 'sales' collection
     */
    async storeSale(saleData) {
        if (!this.isConnected()) {
            console.warn('[Firebase] Firestore not initialized, skipping sale cloud save.');
            return null;
        }

        try {
            const docId = saleData.invoice_no ? saleData.invoice_no.replace(/[^a-zA-Z0-9_-]/g, '_') : `sale_${saleData.sale_id || Date.now()}`;
            const saleRef = doc(db, 'sales', docId);

            const payload = {
                sale_id: saleData.sale_id || null,
                invoice_no: saleData.invoice_no || '',
                sale_date: saleData.sale_date || new Date().toISOString(),
                cashier_name: saleData.cashier_name || 'Cashier',
                customer_name: saleData.customer_name || 'Walk-in Customer',
                subtotal: parseFloat(saleData.subtotal || 0),
                tax_amount: parseFloat(saleData.tax_amount || 0),
                discount_amount: parseFloat(saleData.discount_amount || 0),
                total_amount: parseFloat(saleData.total_amount || 0),
                paid_amount: parseFloat(saleData.paid_amount || 0),
                change_amount: parseFloat(saleData.change_amount || 0),
                due_amount: parseFloat(saleData.due_amount || 0),
                payment_method: saleData.payment_method || 'cash',
                payment_status: (saleData.payment_status || 'PAID').toUpperCase(),
                items: (saleData.items || []).map(item => ({
                    product_id: item.product_id || null,
                    name: item.name || '',
                    sku: item.sku || '',
                    quantity: parseInt(item.quantity || 1),
                    unit_price: parseFloat(item.unit_price || 0),
                    subtotal: parseFloat(item.subtotal || 0),
                })),
                synced_at: serverTimestamp(),
                source: 'web_pos_terminal'
            };

            await setDoc(saleRef, payload, { merge: true });
            console.log(`[Firebase] Stored sale ${saleData.invoice_no || docId} in Firestore.`);
            return { success: true, docId };
        } catch (error) {
            console.error('[Firebase] Failed to store sale in Firestore:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * Store or update Product in Firestore 'products' collection
     */
    async storeProduct(product) {
        if (!this.isConnected()) return null;

        try {
            const docId = `product_${product.id}`;
            const prodRef = doc(db, 'products', docId);

            const payload = {
                id: product.id,
                name: product.name,
                sku: product.sku || '',
                barcode: product.barcode || '',
                selling_price: parseFloat(product.selling_price || 0),
                purchase_price: parseFloat(product.purchase_price || 0),
                stock_quantity: parseInt(product.stock_quantity || 0),
                category: product.category || '',
                brand: product.brand || '',
                is_active: product.is_active !== undefined ? Boolean(product.is_active) : true,
                synced_at: serverTimestamp()
            };

            await setDoc(prodRef, payload, { merge: true });
            return { success: true, docId };
        } catch (error) {
            console.error('[Firebase] Failed to store product:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * Store Notification in Firestore 'notifications' collection
     */
    async storeNotification(notif) {
        if (!this.isConnected()) return null;

        try {
            const notifRef = collection(db, 'notifications');
            const docRef = await addDoc(notifRef, {
                title: notif.title || 'System Notification',
                message: notif.message || '',
                type: notif.type || 'info',
                created_at: serverTimestamp()
            });
            return { success: true, id: docRef.id };
        } catch (error) {
            console.error('[Firebase] Failed to store notification:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * Test read & write connection to Firestore
     */
    async testConnection() {
        if (!this.isConnected()) {
            return { success: false, message: 'Firebase SDK could not be initialized.' };
        }

        try {
            const pingRef = doc(db, '_health', 'ping');
            await setDoc(pingRef, {
                status: 'online',
                timestamp: serverTimestamp(),
                project_id: firebaseConfig.projectId
            }, { merge: true });

            return {
                success: true,
                project: firebaseConfig.projectId,
                message: `Successfully connected to Firebase Project (${firebaseConfig.projectId}) and wrote health ping document to Firestore!`
            };
        } catch (error) {
            return {
                success: false,
                project: firebaseConfig.projectId,
                message: `Firebase Firestore write test error: ${error.message}`
            };
        }
    },

    /**
     * Pull full dataset from server and bulk sync all products, sales & customers to Firestore
     */
    async syncAllFromBackend() {
        if (!this.isConnected()) {
            throw new Error('Firebase Firestore is not initialized.');
        }

        const res = await fetch('/firebase/export-payload', {
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!res.ok) {
            throw new Error(`Server returned HTTP ${res.status}`);
        }

        const data = await res.json();
        const results = { products: 0, sales: 0, customers: 0 };

        // 1. Sync Products
        if (Array.isArray(data.products)) {
            for (const prod of data.products) {
                await this.storeProduct(prod);
                results.products++;
            }
        }

        // 2. Sync Sales
        if (Array.isArray(data.sales)) {
            for (const sale of data.sales) {
                const saleRef = doc(db, 'sales', sale.invoice_no ? sale.invoice_no.replace(/[^a-zA-Z0-9_-]/g, '_') : `sale_${sale.id}`);
                await setDoc(saleRef, {
                    ...sale,
                    synced_at: serverTimestamp()
                }, { merge: true });
                results.sales++;
            }
        }

        // 3. Sync Customers
        if (Array.isArray(data.customers)) {
            for (const cust of data.customers) {
                const custRef = doc(db, 'customers', `customer_${cust.id}`);
                await setDoc(custRef, {
                    ...cust,
                    synced_at: serverTimestamp()
                }, { merge: true });
                results.customers++;
            }
        }

        return results;
    }
};

// Expose globally to window
window.FirebaseSync = FirebaseSync;
window.FirebaseService = FirebaseSync;

// Global Event Listener for POS checkout completion
window.addEventListener('pos:sale-completed', async (event) => {
    if (event.detail) {
        console.log('[Firebase] Auto-storing completed POS sale in Firestore:', event.detail.invoice_no);
        await FirebaseSync.storeSale(event.detail);
    }
});
