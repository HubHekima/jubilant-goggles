import { Html5QrcodeScanner } from "html5-qrcode";

// We force it onto the window object so the browser sees it globally
window.Html5QrcodeScanner = Html5QrcodeScanner;

console.log("Scanner library is now attached to the window!");
