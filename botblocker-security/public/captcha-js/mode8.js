/* CAPTCHA Mode 8 JS (silent auto-verify - no user interaction required) */

function renderMode8Captcha(params) {
    var deniedText = (params && params.deniedText) ? params.deniedText : 'Access denied.';
    var silentHash = (params && params.silentHash) ? params.silentHash : '';

    if (!silentHash || typeof bbcsJsData === 'undefined' || !bbcsJsData.checkFunctionName) {
        bbcsMode8ShowDenied(deniedText);
        return;
    }

    var retries = 0;
    try { retries = parseInt(sessionStorage.getItem('bbcsMode8Retries') || '0', 10); } catch(e) {}

    if (retries < 2) {
        try { sessionStorage.setItem('bbcsMode8Retries', String(retries + 1)); } catch(e) {}
        window[bbcsJsData.checkFunctionName]('post', window.data, silentHash);
    } else {
        try { sessionStorage.removeItem('bbcsMode8Retries'); } catch(e) {}
        bbcsMode8ShowDenied(deniedText);
    }
}

function bbcsMode8ShowDenied(text) {
    var c = document.getElementById("content");
    if (c) { c.innerHTML = '<p style="text-align:center;color:#c0392b;">' + text + '</p>'; }
}

window.renderMode8Captcha = renderMode8Captcha;
