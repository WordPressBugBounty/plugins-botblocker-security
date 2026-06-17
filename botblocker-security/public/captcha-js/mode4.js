/* CAPTCHA Mode 4 JS (recaptcha w/o button) */

function renderMode4Captcha(params) {
    const { confirmText, recaptchaKey, hash, loadingText } = params;
    
    var script = document.createElement("script");
    script.src = "https://www.google.com/recaptcha/api.js";
    document.body.appendChild(script);
    
    script.onload = function() {
        document.getElementById("content").innerHTML = `
            <div style="max-width: 302px; text-align: center;margin: 0 auto;">
                <p>${confirmText}</p>
                <p class="g-recaptcha" style="display: inline-block;" data-sitekey="${recaptchaKey}" data-callback="onRecaptchaSuccess">${loadingText}</p>
            </div>
        `;
    };

    window.onRecaptchaSuccess = function(token) {
        window.data += "&g-recaptcha-response=" + token;
        document.getElementById("content").innerHTML = loadingText;
        window[bbcsJsData.checkFunctionName]('post', window.data, hash);
    };
}
 
window.renderMode4Captcha = renderMode4Captcha;
